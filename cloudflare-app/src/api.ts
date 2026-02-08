// API route handlers
import type { Env, Operator, ApiResponse, DashboardData, InterviewerStatus, RegisterStudentRequest, EnqueueRequest, Interviewee, Interview, Interviewer, CreateInterviewerRequest, UpdateInterviewerRequest } from './types';

import { verifyPassword, createSession, deleteSession, validateSession, getSessionFromCookies, createSessionCookie, createLogoutCookie } from './auth';

// Helper to create JSON responses
function json<T>(data: ApiResponse<T>, status = 200, headers: Record<string, string> = {}): Response {
    return new Response(JSON.stringify(data), {
        status,
        headers: {
            'Content-Type': 'application/json',
            ...headers
        }
    });
}

// DEV MODE: Set to true to skip all authentication
const DEV_MODE = true;

// Fake operator for dev mode - set as COMPANY with interviewer_id for testing
const DEV_OPERATOR: Operator = {
    id: 1,
    username: 'dev',
    password_hash: '',
    role: 'COMPANY',
    interviewer_id: 1,
    created_at: ''
};


// Authentication required middleware
async function requireAuth(request: Request, env: Env): Promise<{ operator: Operator } | Response> {
    // DEV MODE: Skip auth
    if (DEV_MODE) {
        return { operator: DEV_OPERATOR };
    }

    const sessionId = getSessionFromCookies(request);
    const auth = await validateSession(env.DB, sessionId);

    if (!auth) {
        return json({ success: false, error: 'Unauthorized' }, 401);
    }

    return { operator: auth.operator };
}


// Require SECRETARY role
async function requireSecretary(request: Request, env: Env): Promise<{ operator: Operator } | Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    // DEV MODE: Skip role check
    if (DEV_MODE) return auth;

    if (auth.operator.role !== 'SECRETARY') {
        return json({ success: false, error: 'Forbidden: Secretary access required' }, 403);
    }

    return auth;
}

// Require GATEKEEPER role
async function requireGatekeeper(request: Request, env: Env): Promise<{ operator: Operator } | Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    // DEV MODE: Skip role check
    if (DEV_MODE) return auth;

    if (auth.operator.role !== 'GATEKEEPER') {
        return json({ success: false, error: 'Forbidden: Gatekeeper access required' }, 403);
    }

    return auth;
}

// Require SECRETARY or GATEKEEPER role
async function requireSecretaryOrGatekeeper(request: Request, env: Env): Promise<{ operator: Operator } | Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    // DEV MODE: Skip role check
    if (DEV_MODE) return auth;

    if (auth.operator.role !== 'SECRETARY' && auth.operator.role !== 'GATEKEEPER') {
        return json({ success: false, error: 'Forbidden: Secretary or Gatekeeper access required' }, 403);
    }

    return auth;
}



// ============ AUTH ROUTES ============

export async function handleLogin(request: Request, env: Env): Promise<Response> {
    try {
        const body = await request.json() as { username?: string; password?: string };
        const { username, password } = body;

        if (!username || !password) {
            return json({ success: false, error: 'Username and password required' }, 400);
        }

        const operator = await env.DB.prepare(
            'SELECT * FROM operators WHERE username = ?'
        ).bind(username).first<Operator>();

        if (!operator) {
            return json({ success: false, error: 'Invalid credentials' }, 401);
        }

        const valid = await verifyPassword(password, operator.password_hash);
        if (!valid) {
            return json({ success: false, error: 'Invalid credentials' }, 401);
        }

        const sessionId = await createSession(env.DB, operator.id);

        return json({
            success: true,
            data: {
                username: operator.username,
                role: operator.role,
                interviewer_id: operator.interviewer_id
            }
        }, 200, {
            'Set-Cookie': createSessionCookie(sessionId)
        });
    } catch (e) {
        const errorMessage = e instanceof Error ? e.message : 'Unknown error';
        console.error('Login error:', errorMessage);
        return json({ success: false, error: `Login failed: ${errorMessage}` }, 500);
    }
}


export async function handleLogout(request: Request, env: Env): Promise<Response> {
    const sessionId = getSessionFromCookies(request);
    if (sessionId) {
        await deleteSession(env.DB, sessionId);
    }

    return json({ success: true }, 200, {
        'Set-Cookie': createLogoutCookie()
    });
}

export async function handleGetMe(request: Request, env: Env): Promise<Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    return json({
        success: true,
        data: {
            username: auth.operator.username,
            role: auth.operator.role,
            interviewer_id: auth.operator.interviewer_id
        }
    });
}

// ============ SECRETARY ROUTES ============

export async function handleRegisterStudent(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretary(request, env);
    if (auth instanceof Response) return auth;

    try {
        const body = await request.json() as RegisterStudentRequest;
        const { first_name, last_name, email, phone, major, graduation_year } = body;

        if (!first_name || !last_name) {
            return json({ success: false, error: 'First name and last name required' }, 400);
        }

        const result = await env.DB.prepare(`
      INSERT INTO interviewees (first_name, last_name, email, phone, major, graduation_year)
      VALUES (?, ?, ?, ?, ?, ?)
    `).bind(first_name, last_name, email || null, phone || null, major || null, graduation_year || null).run();

        return json({
            success: true,
            data: { id: result.meta.last_row_id }
        });
    } catch (e) {
        return json({ success: false, error: 'Failed to register student' }, 500);
    }
}

export async function handleEnqueueStudent(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretary(request, env);
    if (auth instanceof Response) return auth;

    try {
        const body = await request.json() as EnqueueRequest;
        const { interviewee_id, interviewer_id } = body;

        if (!interviewee_id || !interviewer_id) {
            return json({ success: false, error: 'Student and company IDs required' }, 400);
        }

        // Get the next queue position for this interviewer
        const lastPosition = await env.DB.prepare(`
      SELECT MAX(queue_position) as max_pos FROM interviews 
      WHERE interviewer_id = ? AND status IN ('ENQUEUED', 'CALLING')
    `).bind(interviewer_id).first<{ max_pos: number | null }>();

        const queuePosition = (lastPosition?.max_pos || 0) + 1;

        const result = await env.DB.prepare(`
      INSERT INTO interviews (interviewee_id, interviewer_id, status, queue_position)
      VALUES (?, ?, 'ENQUEUED', ?)
    `).bind(interviewee_id, interviewer_id, queuePosition).run();

        return json({
            success: true,
            data: { id: result.meta.last_row_id, queue_position: queuePosition }
        });
    } catch (e) {
        return json({ success: false, error: 'Failed to enqueue student' }, 500);
    }
}

export async function handleGetStudents(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretary(request, env);
    if (auth instanceof Response) return auth;

    const students = await env.DB.prepare(
        'SELECT * FROM interviewees ORDER BY created_at DESC'
    ).all<Interviewee>();

    return json({ success: true, data: students.results });
}

export async function handleGetInterviewers(request: Request, env: Env): Promise<Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    const interviewers = await env.DB.prepare(
        'SELECT * FROM interviewers WHERE is_active = 1 ORDER BY name'
    ).all<Interviewer>();

    return json({ success: true, data: interviewers.results });
}

export async function handleGetQueue(request: Request, env: Env): Promise<Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    const url = new URL(request.url);
    const interviewerId = url.searchParams.get('interviewer_id');

    let query = `
    SELECT i.*, 
           ie.first_name, ie.last_name, ie.major,
           ir.name as company_name
    FROM interviews i
    JOIN interviewees ie ON i.interviewee_id = ie.id
    JOIN interviewers ir ON i.interviewer_id = ir.id
    WHERE i.status IN ('ENQUEUED', 'CALLING', 'HAPPENING')
  `;

    const params: (string | number)[] = [];

    if (interviewerId) {
        query += ' AND i.interviewer_id = ?';
        params.push(parseInt(interviewerId));
    }

    query += ' ORDER BY i.interviewer_id, i.queue_position';

    const stmt = params.length > 0
        ? env.DB.prepare(query).bind(...params)
        : env.DB.prepare(query);

    const queue = await stmt.all();

    return json({ success: true, data: queue.results });
}

// ============ COMPANY ROUTES ============

export async function handleCallNext(request: Request, env: Env): Promise<Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    if (auth.operator.role !== 'COMPANY' || !auth.operator.interviewer_id) {
        return json({ success: false, error: 'Company access required' }, 403);
    }

    const interviewerId = auth.operator.interviewer_id;

    // Check if already calling someone
    const calling = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'CALLING'
  `).bind(interviewerId).first();

    if (calling) {
        return json({ success: false, error: 'Already calling a student. Complete or mark as no-show first.' }, 400);
    }

    // Check if currently interviewing
    const interviewing = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'HAPPENING'
  `).bind(interviewerId).first();

    if (interviewing) {
        return json({ success: false, error: 'Interview in progress. Complete it first.' }, 400);
    }

    // Get next in queue
    const next = await env.DB.prepare(`
    SELECT i.id, ie.first_name, ie.last_name
    FROM interviews i
    JOIN interviewees ie ON i.interviewee_id = ie.id
    WHERE i.interviewer_id = ? AND i.status = 'ENQUEUED'
    ORDER BY i.queue_position
    LIMIT 1
  `).bind(interviewerId).first<{ id: number; first_name: string; last_name: string }>();

    if (!next) {
        return json({ success: false, error: 'No students in queue' }, 404);
    }

    // Update status to CALLING
    await env.DB.prepare(`
    UPDATE interviews SET status = 'CALLING', called_at = datetime('now')
    WHERE id = ?
  `).bind(next.id).run();

    return json({
        success: true,
        data: {
            interview_id: next.id,
            student_name: `${next.first_name} ${next.last_name}`
        }
    });
}

export async function handleStartInterview(request: Request, env: Env): Promise<Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    if (auth.operator.role !== 'COMPANY' || !auth.operator.interviewer_id) {
        return json({ success: false, error: 'Company access required' }, 403);
    }

    const interviewerId = auth.operator.interviewer_id;

    // Get the currently calling interview
    const calling = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'CALLING'
  `).bind(interviewerId).first<{ id: number }>();

    if (!calling) {
        return json({ success: false, error: 'No student being called' }, 404);
    }

    // Update to HAPPENING
    await env.DB.prepare(`
    UPDATE interviews SET status = 'HAPPENING', started_at = datetime('now')
    WHERE id = ?
  `).bind(calling.id).run();

    return json({ success: true, data: { interview_id: calling.id } });
}

export async function handleCompleteInterview(request: Request, env: Env): Promise<Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    if (auth.operator.role !== 'COMPANY' || !auth.operator.interviewer_id) {
        return json({ success: false, error: 'Company access required' }, 403);
    }

    const interviewerId = auth.operator.interviewer_id;
    const body = await request.json() as { notes?: string };

    // Get the current interview (either CALLING or HAPPENING)
    const current = await env.DB.prepare(`
    SELECT id, status FROM interviews 
    WHERE interviewer_id = ? AND status IN ('CALLING', 'HAPPENING')
  `).bind(interviewerId).first<{ id: number; status: string }>();

    if (!current) {
        return json({ success: false, error: 'No active interview' }, 404);
    }

    // Complete the interview
    await env.DB.prepare(`
    UPDATE interviews 
    SET status = 'COMPLETED', completed_at = datetime('now'), notes = ?
    WHERE id = ?
  `).bind(body.notes || null, current.id).run();

    return json({ success: true, data: { interview_id: current.id } });
}

export async function handleNoShow(request: Request, env: Env): Promise<Response> {
    const auth = await requireAuth(request, env);
    if (auth instanceof Response) return auth;

    if (auth.operator.role !== 'COMPANY' || !auth.operator.interviewer_id) {
        return json({ success: false, error: 'Company access required' }, 403);
    }

    const interviewerId = auth.operator.interviewer_id;

    // Get the currently calling interview
    const calling = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'CALLING'
  `).bind(interviewerId).first<{ id: number }>();

    if (!calling) {
        return json({ success: false, error: 'No student being called' }, 404);
    }

    // Mark as NO_SHOW
    await env.DB.prepare(`
    UPDATE interviews SET status = 'NO_SHOW', completed_at = datetime('now')
    WHERE id = ?
  `).bind(calling.id).run();

    return json({ success: true, data: { interview_id: calling.id } });
}

// ============ PUBLIC ROUTES ============

export async function handleDashboard(env: Env): Promise<Response> {
    // Get all active interviewers with their current status
    const interviewers = await env.DB.prepare(`
    SELECT 
      ir.id, ir.name, ir.table_number, ir.is_paused,
      (SELECT COUNT(*) FROM interviews i WHERE i.interviewer_id = ir.id AND i.status = 'ENQUEUED') as queue_length,
      (SELECT i.status FROM interviews i WHERE i.interviewer_id = ir.id AND i.status IN ('CALLING', 'HAPPENING') LIMIT 1) as current_status,
      (SELECT ie.first_name || ' ' || ie.last_name 
       FROM interviews i 
       JOIN interviewees ie ON i.interviewee_id = ie.id 
       WHERE i.interviewer_id = ir.id AND i.status IN ('CALLING', 'HAPPENING') 
       LIMIT 1) as current_student
    FROM interviewers ir
    WHERE ir.is_active = 1
    ORDER BY ir.name
  `).all<{
        id: number;
        name: string;
        table_number: string | null;
        is_paused: number;
        queue_length: number;
        current_status: string | null;
        current_student: string | null;
    }>();

    const interviewerStatuses: InterviewerStatus[] = interviewers.results.map(ir => ({
        id: ir.id,
        name: ir.name,
        table_number: ir.table_number,
        status: ir.is_paused ? 'PAUSED' :
            ir.current_status === 'CALLING' ? 'CALLING' :
                ir.current_status === 'HAPPENING' ? 'INTERVIEWING' : 'IDLE',
        current_student: ir.current_student,
        queue_length: ir.queue_length,
        is_paused: ir.is_paused
    }));


    // Get recent calls
    const recentCalls = await env.DB.prepare(`
    SELECT 
      ir.name as company,
      ie.first_name || ' ' || ie.last_name as student,
      i.called_at
    FROM interviews i
    JOIN interviewers ir ON i.interviewer_id = ir.id
    JOIN interviewees ie ON i.interviewee_id = ie.id
    WHERE i.called_at IS NOT NULL
    ORDER BY i.called_at DESC
    LIMIT 10
  `).all<{ company: string; student: string; called_at: string }>();

    const dashboardData: DashboardData = {
        interviewers: interviewerStatuses,
        recent_calls: recentCalls.results
    };

    return json({ success: true, data: dashboardData });
}

// ============ INTERVIEWER CRUD (Secretary) ============

export async function handleCreateInterviewer(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretary(request, env);
    if (auth instanceof Response) return auth;

    try {
        const body = await request.json() as CreateInterviewerRequest;
        const { name, table_number } = body;

        if (!name) {
            return json({ success: false, error: 'Company name required' }, 400);
        }

        const result = await env.DB.prepare(`
      INSERT INTO interviewers (name, table_number, is_active, is_paused)
      VALUES (?, ?, 1, 0)
    `).bind(name, table_number || null).run();

        return json({
            success: true,
            data: { id: result.meta.last_row_id }
        });
    } catch (e) {
        return json({ success: false, error: 'Failed to create company' }, 500);
    }
}

export async function handleUpdateInterviewer(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretaryOrGatekeeper(request, env);
    if (auth instanceof Response) return auth;

    try {
        const url = new URL(request.url);
        const id = url.searchParams.get('id');
        if (!id) {
            return json({ success: false, error: 'Interviewer ID required' }, 400);
        }

        const body = await request.json() as UpdateInterviewerRequest;
        const { name, table_number, is_paused } = body;

        // Build dynamic update query
        const updates: string[] = [];
        const values: (string | number)[] = [];

        if (name !== undefined) {
            updates.push('name = ?');
            values.push(name);
        }
        if (table_number !== undefined) {
            updates.push('table_number = ?');
            values.push(table_number);
        }
        if (is_paused !== undefined) {
            updates.push('is_paused = ?');
            values.push(is_paused ? 1 : 0);
        }

        if (updates.length === 0) {
            return json({ success: false, error: 'No fields to update' }, 400);
        }

        values.push(parseInt(id));

        await env.DB.prepare(`
      UPDATE interviewers SET ${updates.join(', ')} WHERE id = ?
    `).bind(...values).run();

        return json({ success: true });
    } catch (e) {
        return json({ success: false, error: 'Failed to update company' }, 500);
    }
}

export async function handleDeleteInterviewer(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretary(request, env);
    if (auth instanceof Response) return auth;

    try {
        const url = new URL(request.url);
        const id = url.searchParams.get('id');
        if (!id) {
            return json({ success: false, error: 'Interviewer ID required' }, 400);
        }

        // Soft delete by setting is_active to 0
        await env.DB.prepare(`
      UPDATE interviewers SET is_active = 0 WHERE id = ?
    `).bind(parseInt(id)).run();

        return json({ success: true });
    } catch (e) {
        return json({ success: false, error: 'Failed to delete company' }, 500);
    }
}

// ============ STUDENT CRUD EXTENSIONS ============

export async function handleDeleteStudent(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretary(request, env);
    if (auth instanceof Response) return auth;

    try {
        const url = new URL(request.url);
        const id = url.searchParams.get('id');
        if (!id) {
            return json({ success: false, error: 'Student ID required' }, 400);
        }

        // Delete any pending interviews first
        await env.DB.prepare(`
      DELETE FROM interviews WHERE interviewee_id = ? AND status = 'ENQUEUED'
    `).bind(parseInt(id)).run();

        // Delete the student
        await env.DB.prepare(`
      DELETE FROM interviewees WHERE id = ?
    `).bind(parseInt(id)).run();

        return json({ success: true });
    } catch (e) {
        return json({ success: false, error: 'Failed to delete student' }, 500);
    }
}

export async function handleToggleStudentPause(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretaryOrGatekeeper(request, env);
    if (auth instanceof Response) return auth;

    try {
        const url = new URL(request.url);
        const id = url.searchParams.get('id');
        if (!id) {
            return json({ success: false, error: 'Student ID required' }, 400);
        }

        // Toggle is_paused
        await env.DB.prepare(`
      UPDATE interviewees SET is_paused = 1 - is_paused WHERE id = ?
    `).bind(parseInt(id)).run();

        return json({ success: true });
    } catch (e) {
        return json({ success: false, error: 'Failed to toggle pause' }, 500);
    }
}

export async function handleToggleInterviewerPause(request: Request, env: Env): Promise<Response> {
    const auth = await requireSecretaryOrGatekeeper(request, env);
    if (auth instanceof Response) return auth;

    try {
        const url = new URL(request.url);
        const id = url.searchParams.get('id');
        if (!id) {
            return json({ success: false, error: 'Interviewer ID required' }, 400);
        }

        // Toggle is_paused
        await env.DB.prepare(`
      UPDATE interviewers SET is_paused = 1 - is_paused WHERE id = ?
    `).bind(parseInt(id)).run();

        return json({ success: true });
    } catch (e) {
        return json({ success: false, error: 'Failed to toggle pause' }, 500);
    }
}

// ============ GATEKEEPER ROUTES ============

export async function handleGatekeeperCallNext(request: Request, env: Env): Promise<Response> {
    const auth = await requireGatekeeper(request, env);
    if (auth instanceof Response) return auth;

    try {
        const body = await request.json() as { interviewer_id: number };
        const interviewerId = body.interviewer_id;

        if (!interviewerId) {
            return json({ success: false, error: 'Interviewer ID required' }, 400);
        }

        // Check if interviewer is paused
        const interviewer = await env.DB.prepare(`
      SELECT is_paused FROM interviewers WHERE id = ?
    `).bind(interviewerId).first<{ is_paused: number }>();

        if (interviewer?.is_paused) {
            return json({ success: false, error: 'Interviewer is paused' }, 400);
        }

        // Check if already calling someone
        const calling = await env.DB.prepare(`
      SELECT id FROM interviews WHERE interviewer_id = ? AND status = 'CALLING'
    `).bind(interviewerId).first();

        if (calling) {
            return json({ success: false, error: 'Already calling a student' }, 400);
        }

        // Get next available (non-paused) student in queue
        const next = await env.DB.prepare(`
      SELECT i.id, ie.first_name, ie.last_name
      FROM interviews i
      JOIN interviewees ie ON i.interviewee_id = ie.id
      WHERE i.interviewer_id = ? AND i.status = 'ENQUEUED' AND ie.is_paused = 0
      ORDER BY i.queue_position
      LIMIT 1
    `).bind(interviewerId).first<{ id: number; first_name: string; last_name: string }>();

        if (!next) {
            return json({ success: false, error: 'No available students in queue' }, 404);
        }

        await env.DB.prepare(`
      UPDATE interviews SET status = 'CALLING', called_at = datetime('now') WHERE id = ?
    `).bind(next.id).run();

        return json({
            success: true,
            data: { interview_id: next.id, student_name: `${next.first_name} ${next.last_name}` }
        });
    } catch (e) {
        return json({ success: false, error: 'Failed to call next' }, 500);
    }
}

export async function handleGatekeeperAction(request: Request, env: Env): Promise<Response> {
    const auth = await requireGatekeeper(request, env);
    if (auth instanceof Response) return auth;

    try {
        const body = await request.json() as { interview_id: number; action: 'start' | 'complete' | 'no_show' | 'dequeue' };
        const { interview_id, action } = body;

        if (!interview_id || !action) {
            return json({ success: false, error: 'Interview ID and action required' }, 400);
        }

        if (action === 'start') {
            await env.DB.prepare(`
        UPDATE interviews SET status = 'HAPPENING', started_at = datetime('now') WHERE id = ?
      `).bind(interview_id).run();
        } else if (action === 'complete') {
            await env.DB.prepare(`
        UPDATE interviews SET status = 'COMPLETED', completed_at = datetime('now') WHERE id = ?
      `).bind(interview_id).run();
        } else if (action === 'no_show') {
            await env.DB.prepare(`
        UPDATE interviews SET status = 'NO_SHOW', completed_at = datetime('now') WHERE id = ?
      `).bind(interview_id).run();
        } else if (action === 'dequeue') {
            await env.DB.prepare(`
        DELETE FROM interviews WHERE id = ?
      `).bind(interview_id).run();
        }

        return json({ success: true });
    } catch (e) {
        return json({ success: false, error: 'Failed to perform action' }, 500);
    }
}

export async function handleGatekeeperQueue(request: Request, env: Env): Promise<Response> {
    const auth = await requireGatekeeper(request, env);
    if (auth instanceof Response) return auth;

    // Get all active interviews grouped by interviewer
    const queue = await env.DB.prepare(`
    SELECT i.*, 
           ie.first_name, ie.last_name, ie.major, ie.is_paused as student_paused,
           ir.name as company_name, ir.is_paused as company_paused
    FROM interviews i
    JOIN interviewees ie ON i.interviewee_id = ie.id
    JOIN interviewers ir ON i.interviewer_id = ir.id
    WHERE i.status IN ('ENQUEUED', 'CALLING', 'HAPPENING')
    ORDER BY ir.name, i.queue_position
  `).all();

    return json({ success: true, data: queue.results });
}
