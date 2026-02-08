var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });

// .wrangler/tmp/bundle-NXjgIc/checked-fetch.js
var urls = /* @__PURE__ */ new Set();
function checkURL(request, init) {
  const url = request instanceof URL ? request : new URL(
    (typeof request === "string" ? new Request(request, init) : request).url
  );
  if (url.port && url.port !== "443" && url.protocol === "https:") {
    if (!urls.has(url.toString())) {
      urls.add(url.toString());
      console.warn(
        `WARNING: known issue with \`fetch()\` requests to custom HTTPS ports in published Workers:
 - ${url.toString()} - the custom port will be ignored when the Worker is published using the \`wrangler deploy\` command.
`
      );
    }
  }
}
__name(checkURL, "checkURL");
globalThis.fetch = new Proxy(globalThis.fetch, {
  apply(target, thisArg, argArray) {
    const [request, init] = argArray;
    checkURL(request, init);
    return Reflect.apply(target, thisArg, argArray);
  }
});

// .wrangler/tmp/bundle-NXjgIc/strip-cf-connecting-ip-header.js
function stripCfConnectingIPHeader(input, init) {
  const request = new Request(input, init);
  request.headers.delete("CF-Connecting-IP");
  return request;
}
__name(stripCfConnectingIPHeader, "stripCfConnectingIPHeader");
globalThis.fetch = new Proxy(globalThis.fetch, {
  apply(target, thisArg, argArray) {
    return Reflect.apply(target, thisArg, [
      stripCfConnectingIPHeader.apply(null, argArray)
    ]);
  }
});

// src/auth.ts
function generateSessionId() {
  const array = new Uint8Array(32);
  crypto.getRandomValues(array);
  return Array.from(array, (b) => b.toString(16).padStart(2, "0")).join("");
}
__name(generateSessionId, "generateSessionId");
async function hashPassword(password) {
  const encoder = new TextEncoder();
  const data = encoder.encode(password);
  const hashBuffer = await crypto.subtle.digest("SHA-256", data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
}
__name(hashPassword, "hashPassword");
async function verifyPassword(password, hash) {
  const passwordHash = await hashPassword(password);
  return passwordHash === hash;
}
__name(verifyPassword, "verifyPassword");
async function createSession(db, operatorId) {
  const sessionId = generateSessionId();
  const expiresAt = new Date(Date.now() + 24 * 60 * 60 * 1e3).toISOString();
  await db.prepare(
    "INSERT INTO sessions (id, operator_id, expires_at) VALUES (?, ?, ?)"
  ).bind(sessionId, operatorId, expiresAt).run();
  return sessionId;
}
__name(createSession, "createSession");
async function validateSession(db, sessionId) {
  if (!sessionId)
    return null;
  const now = (/* @__PURE__ */ new Date()).toISOString();
  const result = await db.prepare(`
    SELECT 
      s.id as session_id, s.operator_id, s.expires_at, s.created_at as session_created,
      o.id, o.username, o.password_hash, o.role, o.interviewer_id, o.created_at
    FROM sessions s
    JOIN operators o ON s.operator_id = o.id
    WHERE s.id = ? AND s.expires_at > ?
  `).bind(sessionId, now).first();
  if (!result)
    return null;
  return {
    session: {
      id: result.session_id,
      operator_id: result.operator_id,
      expires_at: result.expires_at,
      created_at: result.session_created
    },
    operator: {
      id: result.id,
      username: result.username,
      password_hash: result.password_hash,
      role: result.role,
      interviewer_id: result.interviewer_id,
      created_at: result.created_at
    }
  };
}
__name(validateSession, "validateSession");
async function deleteSession(db, sessionId) {
  await db.prepare("DELETE FROM sessions WHERE id = ?").bind(sessionId).run();
}
__name(deleteSession, "deleteSession");
function getSessionFromCookies(request) {
  const cookieHeader = request.headers.get("Cookie");
  if (!cookieHeader)
    return null;
  const cookies = cookieHeader.split(";").map((c) => c.trim());
  for (const cookie of cookies) {
    const [name, value] = cookie.split("=");
    if (name === "session") {
      return value;
    }
  }
  return null;
}
__name(getSessionFromCookies, "getSessionFromCookies");
function createSessionCookie(sessionId) {
  return `session=${sessionId}; Path=/; HttpOnly; SameSite=Strict; Max-Age=86400`;
}
__name(createSessionCookie, "createSessionCookie");
function createLogoutCookie() {
  return `session=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0`;
}
__name(createLogoutCookie, "createLogoutCookie");

// src/api.ts
function json(data, status = 200, headers = {}) {
  return new Response(JSON.stringify(data), {
    status,
    headers: {
      "Content-Type": "application/json",
      ...headers
    }
  });
}
__name(json, "json");
var DEV_MODE = true;
var DEV_OPERATOR = {
  id: 1,
  username: "dev",
  password_hash: "",
  role: "COMPANY",
  interviewer_id: 1,
  created_at: ""
};
async function requireAuth(request, env) {
  if (DEV_MODE) {
    return { operator: DEV_OPERATOR };
  }
  const sessionId = getSessionFromCookies(request);
  const auth = await validateSession(env.DB, sessionId);
  if (!auth) {
    return json({ success: false, error: "Unauthorized" }, 401);
  }
  return { operator: auth.operator };
}
__name(requireAuth, "requireAuth");
async function requireSecretary(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  if (DEV_MODE)
    return auth;
  if (auth.operator.role !== "SECRETARY") {
    return json({ success: false, error: "Forbidden: Secretary access required" }, 403);
  }
  return auth;
}
__name(requireSecretary, "requireSecretary");
async function requireGatekeeper(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  if (DEV_MODE)
    return auth;
  if (auth.operator.role !== "GATEKEEPER") {
    return json({ success: false, error: "Forbidden: Gatekeeper access required" }, 403);
  }
  return auth;
}
__name(requireGatekeeper, "requireGatekeeper");
async function requireSecretaryOrGatekeeper(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  if (DEV_MODE)
    return auth;
  if (auth.operator.role !== "SECRETARY" && auth.operator.role !== "GATEKEEPER") {
    return json({ success: false, error: "Forbidden: Secretary or Gatekeeper access required" }, 403);
  }
  return auth;
}
__name(requireSecretaryOrGatekeeper, "requireSecretaryOrGatekeeper");
async function handleLogin(request, env) {
  try {
    const body = await request.json();
    const { username, password } = body;
    if (!username || !password) {
      return json({ success: false, error: "Username and password required" }, 400);
    }
    const operator = await env.DB.prepare(
      "SELECT * FROM operators WHERE username = ?"
    ).bind(username).first();
    if (!operator) {
      return json({ success: false, error: "Invalid credentials" }, 401);
    }
    const valid = await verifyPassword(password, operator.password_hash);
    if (!valid) {
      return json({ success: false, error: "Invalid credentials" }, 401);
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
      "Set-Cookie": createSessionCookie(sessionId)
    });
  } catch (e) {
    const errorMessage = e instanceof Error ? e.message : "Unknown error";
    console.error("Login error:", errorMessage);
    return json({ success: false, error: `Login failed: ${errorMessage}` }, 500);
  }
}
__name(handleLogin, "handleLogin");
async function handleLogout(request, env) {
  const sessionId = getSessionFromCookies(request);
  if (sessionId) {
    await deleteSession(env.DB, sessionId);
  }
  return json({ success: true }, 200, {
    "Set-Cookie": createLogoutCookie()
  });
}
__name(handleLogout, "handleLogout");
async function handleGetMe(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  return json({
    success: true,
    data: {
      username: auth.operator.username,
      role: auth.operator.role,
      interviewer_id: auth.operator.interviewer_id
    }
  });
}
__name(handleGetMe, "handleGetMe");
async function handleRegisterStudent(request, env) {
  const auth = await requireSecretary(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const body = await request.json();
    const { first_name, last_name, email, phone, major, graduation_year } = body;
    if (!first_name || !last_name) {
      return json({ success: false, error: "First name and last name required" }, 400);
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
    return json({ success: false, error: "Failed to register student" }, 500);
  }
}
__name(handleRegisterStudent, "handleRegisterStudent");
async function handleEnqueueStudent(request, env) {
  const auth = await requireSecretary(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const body = await request.json();
    const { interviewee_id, interviewer_id } = body;
    if (!interviewee_id || !interviewer_id) {
      return json({ success: false, error: "Student and company IDs required" }, 400);
    }
    const lastPosition = await env.DB.prepare(`
      SELECT MAX(queue_position) as max_pos FROM interviews 
      WHERE interviewer_id = ? AND status IN ('ENQUEUED', 'CALLING')
    `).bind(interviewer_id).first();
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
    return json({ success: false, error: "Failed to enqueue student" }, 500);
  }
}
__name(handleEnqueueStudent, "handleEnqueueStudent");
async function handleGetStudents(request, env) {
  const auth = await requireSecretary(request, env);
  if (auth instanceof Response)
    return auth;
  const students = await env.DB.prepare(
    "SELECT * FROM interviewees ORDER BY created_at DESC"
  ).all();
  return json({ success: true, data: students.results });
}
__name(handleGetStudents, "handleGetStudents");
async function handleGetInterviewers(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  const interviewers = await env.DB.prepare(
    "SELECT * FROM interviewers WHERE is_active = 1 ORDER BY name"
  ).all();
  return json({ success: true, data: interviewers.results });
}
__name(handleGetInterviewers, "handleGetInterviewers");
async function handleGetQueue(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  const url = new URL(request.url);
  const interviewerId = url.searchParams.get("interviewer_id");
  let query = `
    SELECT i.*, 
           ie.first_name, ie.last_name, ie.major,
           ir.name as company_name
    FROM interviews i
    JOIN interviewees ie ON i.interviewee_id = ie.id
    JOIN interviewers ir ON i.interviewer_id = ir.id
    WHERE i.status IN ('ENQUEUED', 'CALLING', 'HAPPENING')
  `;
  const params = [];
  if (interviewerId) {
    query += " AND i.interviewer_id = ?";
    params.push(parseInt(interviewerId));
  }
  query += " ORDER BY i.interviewer_id, i.queue_position";
  const stmt = params.length > 0 ? env.DB.prepare(query).bind(...params) : env.DB.prepare(query);
  const queue = await stmt.all();
  return json({ success: true, data: queue.results });
}
__name(handleGetQueue, "handleGetQueue");
async function handleCallNext(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  if (auth.operator.role !== "COMPANY" || !auth.operator.interviewer_id) {
    return json({ success: false, error: "Company access required" }, 403);
  }
  const interviewerId = auth.operator.interviewer_id;
  const calling = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'CALLING'
  `).bind(interviewerId).first();
  if (calling) {
    return json({ success: false, error: "Already calling a student. Complete or mark as no-show first." }, 400);
  }
  const interviewing = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'HAPPENING'
  `).bind(interviewerId).first();
  if (interviewing) {
    return json({ success: false, error: "Interview in progress. Complete it first." }, 400);
  }
  const next = await env.DB.prepare(`
    SELECT i.id, ie.first_name, ie.last_name
    FROM interviews i
    JOIN interviewees ie ON i.interviewee_id = ie.id
    WHERE i.interviewer_id = ? AND i.status = 'ENQUEUED'
    ORDER BY i.queue_position
    LIMIT 1
  `).bind(interviewerId).first();
  if (!next) {
    return json({ success: false, error: "No students in queue" }, 404);
  }
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
__name(handleCallNext, "handleCallNext");
async function handleStartInterview(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  if (auth.operator.role !== "COMPANY" || !auth.operator.interviewer_id) {
    return json({ success: false, error: "Company access required" }, 403);
  }
  const interviewerId = auth.operator.interviewer_id;
  const calling = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'CALLING'
  `).bind(interviewerId).first();
  if (!calling) {
    return json({ success: false, error: "No student being called" }, 404);
  }
  await env.DB.prepare(`
    UPDATE interviews SET status = 'HAPPENING', started_at = datetime('now')
    WHERE id = ?
  `).bind(calling.id).run();
  return json({ success: true, data: { interview_id: calling.id } });
}
__name(handleStartInterview, "handleStartInterview");
async function handleCompleteInterview(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  if (auth.operator.role !== "COMPANY" || !auth.operator.interviewer_id) {
    return json({ success: false, error: "Company access required" }, 403);
  }
  const interviewerId = auth.operator.interviewer_id;
  const body = await request.json();
  const current = await env.DB.prepare(`
    SELECT id, status FROM interviews 
    WHERE interviewer_id = ? AND status IN ('CALLING', 'HAPPENING')
  `).bind(interviewerId).first();
  if (!current) {
    return json({ success: false, error: "No active interview" }, 404);
  }
  await env.DB.prepare(`
    UPDATE interviews 
    SET status = 'COMPLETED', completed_at = datetime('now'), notes = ?
    WHERE id = ?
  `).bind(body.notes || null, current.id).run();
  return json({ success: true, data: { interview_id: current.id } });
}
__name(handleCompleteInterview, "handleCompleteInterview");
async function handleNoShow(request, env) {
  const auth = await requireAuth(request, env);
  if (auth instanceof Response)
    return auth;
  if (auth.operator.role !== "COMPANY" || !auth.operator.interviewer_id) {
    return json({ success: false, error: "Company access required" }, 403);
  }
  const interviewerId = auth.operator.interviewer_id;
  const calling = await env.DB.prepare(`
    SELECT id FROM interviews 
    WHERE interviewer_id = ? AND status = 'CALLING'
  `).bind(interviewerId).first();
  if (!calling) {
    return json({ success: false, error: "No student being called" }, 404);
  }
  await env.DB.prepare(`
    UPDATE interviews SET status = 'NO_SHOW', completed_at = datetime('now')
    WHERE id = ?
  `).bind(calling.id).run();
  return json({ success: true, data: { interview_id: calling.id } });
}
__name(handleNoShow, "handleNoShow");
async function handleDashboard(env) {
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
  `).all();
  const interviewerStatuses = interviewers.results.map((ir) => ({
    id: ir.id,
    name: ir.name,
    table_number: ir.table_number,
    status: ir.is_paused ? "PAUSED" : ir.current_status === "CALLING" ? "CALLING" : ir.current_status === "HAPPENING" ? "INTERVIEWING" : "IDLE",
    current_student: ir.current_student,
    queue_length: ir.queue_length,
    is_paused: ir.is_paused
  }));
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
  `).all();
  const dashboardData = {
    interviewers: interviewerStatuses,
    recent_calls: recentCalls.results
  };
  return json({ success: true, data: dashboardData });
}
__name(handleDashboard, "handleDashboard");
async function handleCreateInterviewer(request, env) {
  const auth = await requireSecretary(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const body = await request.json();
    const { name, table_number } = body;
    if (!name) {
      return json({ success: false, error: "Company name required" }, 400);
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
    return json({ success: false, error: "Failed to create company" }, 500);
  }
}
__name(handleCreateInterviewer, "handleCreateInterviewer");
async function handleUpdateInterviewer(request, env) {
  const auth = await requireSecretaryOrGatekeeper(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const url = new URL(request.url);
    const id = url.searchParams.get("id");
    if (!id) {
      return json({ success: false, error: "Interviewer ID required" }, 400);
    }
    const body = await request.json();
    const { name, table_number, is_paused } = body;
    const updates = [];
    const values = [];
    if (name !== void 0) {
      updates.push("name = ?");
      values.push(name);
    }
    if (table_number !== void 0) {
      updates.push("table_number = ?");
      values.push(table_number);
    }
    if (is_paused !== void 0) {
      updates.push("is_paused = ?");
      values.push(is_paused ? 1 : 0);
    }
    if (updates.length === 0) {
      return json({ success: false, error: "No fields to update" }, 400);
    }
    values.push(parseInt(id));
    await env.DB.prepare(`
      UPDATE interviewers SET ${updates.join(", ")} WHERE id = ?
    `).bind(...values).run();
    return json({ success: true });
  } catch (e) {
    return json({ success: false, error: "Failed to update company" }, 500);
  }
}
__name(handleUpdateInterviewer, "handleUpdateInterviewer");
async function handleDeleteInterviewer(request, env) {
  const auth = await requireSecretary(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const url = new URL(request.url);
    const id = url.searchParams.get("id");
    if (!id) {
      return json({ success: false, error: "Interviewer ID required" }, 400);
    }
    await env.DB.prepare(`
      UPDATE interviewers SET is_active = 0 WHERE id = ?
    `).bind(parseInt(id)).run();
    return json({ success: true });
  } catch (e) {
    return json({ success: false, error: "Failed to delete company" }, 500);
  }
}
__name(handleDeleteInterviewer, "handleDeleteInterviewer");
async function handleDeleteStudent(request, env) {
  const auth = await requireSecretary(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const url = new URL(request.url);
    const id = url.searchParams.get("id");
    if (!id) {
      return json({ success: false, error: "Student ID required" }, 400);
    }
    await env.DB.prepare(`
      DELETE FROM interviews WHERE interviewee_id = ? AND status = 'ENQUEUED'
    `).bind(parseInt(id)).run();
    await env.DB.prepare(`
      DELETE FROM interviewees WHERE id = ?
    `).bind(parseInt(id)).run();
    return json({ success: true });
  } catch (e) {
    return json({ success: false, error: "Failed to delete student" }, 500);
  }
}
__name(handleDeleteStudent, "handleDeleteStudent");
async function handleToggleStudentPause(request, env) {
  const auth = await requireSecretaryOrGatekeeper(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const url = new URL(request.url);
    const id = url.searchParams.get("id");
    if (!id) {
      return json({ success: false, error: "Student ID required" }, 400);
    }
    await env.DB.prepare(`
      UPDATE interviewees SET is_paused = 1 - is_paused WHERE id = ?
    `).bind(parseInt(id)).run();
    return json({ success: true });
  } catch (e) {
    return json({ success: false, error: "Failed to toggle pause" }, 500);
  }
}
__name(handleToggleStudentPause, "handleToggleStudentPause");
async function handleToggleInterviewerPause(request, env) {
  const auth = await requireSecretaryOrGatekeeper(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const url = new URL(request.url);
    const id = url.searchParams.get("id");
    if (!id) {
      return json({ success: false, error: "Interviewer ID required" }, 400);
    }
    await env.DB.prepare(`
      UPDATE interviewers SET is_paused = 1 - is_paused WHERE id = ?
    `).bind(parseInt(id)).run();
    return json({ success: true });
  } catch (e) {
    return json({ success: false, error: "Failed to toggle pause" }, 500);
  }
}
__name(handleToggleInterviewerPause, "handleToggleInterviewerPause");
async function handleGatekeeperCallNext(request, env) {
  const auth = await requireGatekeeper(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const body = await request.json();
    const interviewerId = body.interviewer_id;
    if (!interviewerId) {
      return json({ success: false, error: "Interviewer ID required" }, 400);
    }
    const interviewer = await env.DB.prepare(`
      SELECT is_paused FROM interviewers WHERE id = ?
    `).bind(interviewerId).first();
    if (interviewer?.is_paused) {
      return json({ success: false, error: "Interviewer is paused" }, 400);
    }
    const calling = await env.DB.prepare(`
      SELECT id FROM interviews WHERE interviewer_id = ? AND status = 'CALLING'
    `).bind(interviewerId).first();
    if (calling) {
      return json({ success: false, error: "Already calling a student" }, 400);
    }
    const next = await env.DB.prepare(`
      SELECT i.id, ie.first_name, ie.last_name
      FROM interviews i
      JOIN interviewees ie ON i.interviewee_id = ie.id
      WHERE i.interviewer_id = ? AND i.status = 'ENQUEUED' AND ie.is_paused = 0
      ORDER BY i.queue_position
      LIMIT 1
    `).bind(interviewerId).first();
    if (!next) {
      return json({ success: false, error: "No available students in queue" }, 404);
    }
    await env.DB.prepare(`
      UPDATE interviews SET status = 'CALLING', called_at = datetime('now') WHERE id = ?
    `).bind(next.id).run();
    return json({
      success: true,
      data: { interview_id: next.id, student_name: `${next.first_name} ${next.last_name}` }
    });
  } catch (e) {
    return json({ success: false, error: "Failed to call next" }, 500);
  }
}
__name(handleGatekeeperCallNext, "handleGatekeeperCallNext");
async function handleGatekeeperAction(request, env) {
  const auth = await requireGatekeeper(request, env);
  if (auth instanceof Response)
    return auth;
  try {
    const body = await request.json();
    const { interview_id, action } = body;
    if (!interview_id || !action) {
      return json({ success: false, error: "Interview ID and action required" }, 400);
    }
    if (action === "start") {
      await env.DB.prepare(`
        UPDATE interviews SET status = 'HAPPENING', started_at = datetime('now') WHERE id = ?
      `).bind(interview_id).run();
    } else if (action === "complete") {
      await env.DB.prepare(`
        UPDATE interviews SET status = 'COMPLETED', completed_at = datetime('now') WHERE id = ?
      `).bind(interview_id).run();
    } else if (action === "no_show") {
      await env.DB.prepare(`
        UPDATE interviews SET status = 'NO_SHOW', completed_at = datetime('now') WHERE id = ?
      `).bind(interview_id).run();
    } else if (action === "dequeue") {
      await env.DB.prepare(`
        DELETE FROM interviews WHERE id = ?
      `).bind(interview_id).run();
    }
    return json({ success: true });
  } catch (e) {
    return json({ success: false, error: "Failed to perform action" }, 500);
  }
}
__name(handleGatekeeperAction, "handleGatekeeperAction");
async function handleGatekeeperQueue(request, env) {
  const auth = await requireGatekeeper(request, env);
  if (auth instanceof Response)
    return auth;
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
__name(handleGatekeeperQueue, "handleGatekeeperQueue");

// src/pages/dashboard.ts
function getPublicDashboardHtml() {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Fair - Live Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-primary: #0f0f23;
      --bg-secondary: #1a1a2e;
      --bg-card: #16213e;
      --accent-primary: #6366f1;
      --accent-secondary: #8b5cf6;
      --accent-success: #10b981;
      --accent-warning: #f59e0b;
      --accent-danger: #ef4444;
      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --border: rgba(255, 255, 255, 0.1);
      --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      background-image: 
        radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    header {
      text-align: center;
      margin-bottom: 3rem;
    }

    h1 {
      font-size: 3rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
    }

    .subtitle {
      color: var(--text-secondary);
      font-size: 1.1rem;
    }

    .live-indicator {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(16, 185, 129, 0.1);
      color: var(--accent-success);
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      font-size: 0.875rem;
      font-weight: 500;
      margin-top: 1rem;
    }

    .live-dot {
      width: 8px;
      height: 8px;
      background: var(--accent-success);
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
    }

    .company-card {
      background: var(--bg-card);
      border-radius: 1rem;
      padding: 1.5rem;
      border: 1px solid var(--border);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .company-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--status-color, var(--text-secondary));
    }

    .company-card.idle { --status-color: var(--text-secondary); }
    .company-card.calling { 
      --status-color: var(--accent-warning);
      animation: glow 2s ease-in-out infinite;
    }
    .company-card.interviewing { --status-color: var(--accent-success); }

    @keyframes glow {
      0%, 100% { box-shadow: 0 0 20px rgba(245, 158, 11, 0.3); }
      50% { box-shadow: 0 0 40px rgba(245, 158, 11, 0.5); }
    }

    .company-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .company-name {
      font-size: 1.25rem;
      font-weight: 600;
    }

    .table-number {
      background: rgba(255, 255, 255, 0.1);
      padding: 0.25rem 0.75rem;
      border-radius: 0.5rem;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .status-idle {
      background: rgba(148, 163, 184, 0.1);
      color: var(--text-secondary);
    }

    .status-calling {
      background: rgba(245, 158, 11, 0.15);
      color: var(--accent-warning);
    }

    .status-interviewing {
      background: rgba(16, 185, 129, 0.15);
      color: var(--accent-success);
    }

    .student-name {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 1rem 0;
      color: var(--text-primary);
    }

    .queue-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--text-secondary);
      font-size: 0.875rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border);
    }

    .queue-count {
      font-weight: 600;
      color: var(--accent-primary);
    }

    .recent-calls {
      margin-top: 3rem;
      background: var(--bg-secondary);
      border-radius: 1rem;
      padding: 1.5rem;
      border: 1px solid var(--border);
    }

    .recent-calls h2 {
      font-size: 1.25rem;
      margin-bottom: 1rem;
      color: var(--text-secondary);
    }

    .call-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .call-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem;
      background: var(--bg-card);
      border-radius: 0.5rem;
    }

    .call-info {
      display: flex;
      gap: 1rem;
    }

    .call-company {
      color: var(--accent-primary);
      font-weight: 500;
    }

    .call-student {
      color: var(--text-primary);
    }

    .call-time {
      color: var(--text-secondary);
      font-size: 0.875rem;
    }

    .login-link {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: var(--bg-card);
      color: var(--text-secondary);
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-size: 0.875rem;
      border: 1px solid var(--border);
      transition: all 0.2s;
    }

    .login-link:hover {
      background: var(--accent-primary);
      color: white;
      border-color: var(--accent-primary);
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--text-secondary);
    }

    .empty-state svg {
      width: 64px;
      height: 64px;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Career Fair</h1>
      <p class="subtitle">Live Interview Queue Dashboard</p>
      <div class="live-indicator">
        <span class="live-dot"></span>
        <span>Live \u2022 Auto-refreshing every 3s</span>
      </div>
    </header>

    <div id="dashboard" class="grid">
      <div class="empty-state">
        <p>Loading...</p>
      </div>
    </div>

    <div id="recent-calls" class="recent-calls" style="display: none;">
      <h2>\u{1F4E2} Recent Calls</h2>
      <div class="call-list" id="call-list"></div>
    </div>
  </div>

  <a href="/login" class="login-link">Operator Login \u2192</a>

  <script>
    async function fetchDashboard() {
      try {
        const res = await fetch('/api/dashboard');
        const data = await res.json();
        
        if (data.success) {
          renderDashboard(data.data);
        }
      } catch (e) {
        console.error('Failed to fetch dashboard:', e);
      }
    }

    function renderDashboard(data) {
      const dashboard = document.getElementById('dashboard');
      const recentCallsSection = document.getElementById('recent-calls');
      const callList = document.getElementById('call-list');

      if (!data.interviewers || data.interviewers.length === 0) {
        dashboard.innerHTML = '<div class="empty-state"><p>No companies registered yet</p></div>';
        return;
      }

      dashboard.innerHTML = data.interviewers.map(company => {
        const statusClass = company.status.toLowerCase();
        const statusLabel = company.status === 'IDLE' ? 'Available' : 
                           company.status === 'CALLING' ? 'Now Calling' : 'Interviewing';
        
        return \`
          <div class="company-card \${statusClass}">
            <div class="company-header">
              <span class="company-name">\${company.name}</span>
              \${company.table_number ? \`<span class="table-number">Table \${company.table_number}</span>\` : ''}
            </div>
            <span class="status-badge status-\${statusClass}">\${statusLabel}</span>
            \${company.current_student ? \`<div class="student-name">\${company.current_student}</div>\` : ''}
            <div class="queue-info">
              <span class="queue-count">\${company.queue_length}</span>
              <span>student\${company.queue_length !== 1 ? 's' : ''} in queue</span>
            </div>
          </div>
        \`;
      }).join('');

      // Recent calls
      if (data.recent_calls && data.recent_calls.length > 0) {
        recentCallsSection.style.display = 'block';
        callList.innerHTML = data.recent_calls.map(call => \`
          <div class="call-item">
            <div class="call-info">
              <span class="call-company">\${call.company}</span>
              <span class="call-student">\${call.student}</span>
            </div>
            <span class="call-time">\${new Date(call.called_at).toLocaleTimeString()}</span>
          </div>
        \`).join('');
      } else {
        recentCallsSection.style.display = 'none';
      }
    }

    // Initial fetch
    fetchDashboard();

    // Auto-refresh every 3 seconds
    setInterval(fetchDashboard, 3000);
  <\/script>
</body>
</html>`;
}
__name(getPublicDashboardHtml, "getPublicDashboardHtml");

// src/pages/login.ts
function getLoginPageHtml() {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Career Fair</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-primary: #0f0f23;
      --bg-secondary: #1a1a2e;
      --bg-card: #16213e;
      --accent-primary: #6366f1;
      --accent-secondary: #8b5cf6;
      --accent-success: #10b981;
      --accent-danger: #ef4444;
      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --border: rgba(255, 255, 255, 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: 
        radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      padding: 2rem;
    }

    .login-card {
      background: var(--bg-card);
      border-radius: 1.5rem;
      padding: 2.5rem;
      border: 1px solid var(--border);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .logo {
      text-align: center;
      margin-bottom: 2rem;
    }

    .logo h1 {
      font-size: 2rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .logo p {
      color: var(--text-secondary);
      margin-top: 0.5rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-secondary);
    }

    input {
      width: 100%;
      padding: 0.875rem 1rem;
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: 0.75rem;
      color: var(--text-primary);
      font-size: 1rem;
      font-family: inherit;
      transition: all 0.2s;
    }

    input:focus {
      outline: none;
      border-color: var(--accent-primary);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    input::placeholder {
      color: var(--text-secondary);
      opacity: 0.5;
    }

    .btn {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      border: none;
      border-radius: 0.75rem;
      color: white;
      font-size: 1rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .error-message {
      background: rgba(239, 68, 68, 0.1);
      color: var(--accent-danger);
      padding: 0.875rem 1rem;
      border-radius: 0.75rem;
      margin-bottom: 1.5rem;
      font-size: 0.875rem;
      display: none;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 1.5rem;
      color: var(--text-secondary);
      text-decoration: none;
      font-size: 0.875rem;
      transition: color 0.2s;
    }

    .back-link:hover {
      color: var(--accent-primary);
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="logo">
        <h1>Career Fair</h1>
        <p>Operator Login</p>
      </div>

      <div id="error" class="error-message"></div>

      <form id="login-form">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn" id="submit-btn">Sign In</button>
      </form>

      <a href="/" class="back-link">\u2190 Back to Dashboard</a>
    </div>
  </div>

  <script>
    const form = document.getElementById('login-form');
    const errorEl = document.getElementById('error');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Signing in...';
      errorEl.style.display = 'none';

      try {
        const res = await fetch('/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password })
        });

        const data = await res.json();

        if (data.success) {
          // Redirect based on role
          if (data.data.role === 'SECRETARY') {
            window.location.href = '/secretary';
          } else if (data.data.role === 'COMPANY') {
            window.location.href = '/company';
          }
        } else {
          errorEl.textContent = data.error || 'Login failed';
          errorEl.style.display = 'block';
        }
      } catch (e) {
        errorEl.textContent = 'Connection error. Please try again.';
        errorEl.style.display = 'block';
      }

      submitBtn.disabled = false;
      submitBtn.textContent = 'Sign In';
    });
  <\/script>
</body>
</html>`;
}
__name(getLoginPageHtml, "getLoginPageHtml");

// src/pages/secretary.ts
function getSecretaryPageHtml() {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secretary Panel - Career Fair</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-primary: #0f0f23;
      --bg-secondary: #1a1a2e;
      --bg-card: #16213e;
      --accent-primary: #6366f1;
      --accent-secondary: #8b5cf6;
      --accent-success: #10b981;
      --accent-warning: #f59e0b;
      --accent-danger: #ef4444;
      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --border: rgba(255, 255, 255, 0.1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      background-image: 
        radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
    }

    .header {
      background: var(--bg-secondary);
      border-bottom: 1px solid var(--border);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header h1 {
      font-size: 1.5rem;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .header-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .user-info {
      color: var(--text-secondary);
      font-size: 0.875rem;
    }

    .btn {
      padding: 0.625rem 1.25rem;
      border: none;
      border-radius: 0.5rem;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      font-family: inherit;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      color: white;
    }

    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3); }

    .btn-secondary {
      background: var(--bg-card);
      color: var(--text-secondary);
      border: 1px solid var(--border);
    }

    .btn-secondary:hover { background: var(--bg-secondary); color: var(--text-primary); }

    .btn-success {
      background: var(--accent-success);
      color: white;
    }

    .btn-success:hover { background: #0d9668; }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
    }

    @media (max-width: 1024px) {
      .container { grid-template-columns: 1fr; }
    }

    .panel {
      background: var(--bg-card);
      border-radius: 1rem;
      border: 1px solid var(--border);
      overflow: hidden;
    }

    .panel-header {
      background: var(--bg-secondary);
      padding: 1rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .panel-header h2 {
      font-size: 1.125rem;
      font-weight: 600;
    }

    .panel-body {
      padding: 1.5rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    label {
      display: block;
      margin-bottom: 0.375rem;
      font-size: 0.8125rem;
      font-weight: 500;
      color: var(--text-secondary);
    }

    input, select {
      width: 100%;
      padding: 0.75rem;
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: 0.5rem;
      color: var(--text-primary);
      font-size: 0.9375rem;
      font-family: inherit;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--accent-primary);
    }

    .student-list {
      max-height: 400px;
      overflow-y: auto;
    }

    .student-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.875rem 1rem;
      background: var(--bg-secondary);
      border-radius: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .student-info h4 {
      font-size: 0.9375rem;
      font-weight: 500;
    }

    .student-info p {
      font-size: 0.8125rem;
      color: var(--text-secondary);
    }

    .queue-section {
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border);
    }

    .enqueue-form {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .enqueue-form select {
      flex: 1;
      min-width: 150px;
    }

    .success-toast {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: var(--accent-success);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0.75rem;
      font-weight: 500;
      box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
      display: none;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from { transform: translateY(100%); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .queue-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 1rem;
      background: var(--bg-secondary);
      border-radius: 0.5rem;
      margin-bottom: 0.5rem;
      border-left: 3px solid var(--accent-primary);
    }

    .queue-item .position {
      font-weight: 700;
      color: var(--accent-primary);
      margin-right: 1rem;
    }

    .queue-item .company {
      color: var(--text-secondary);
      font-size: 0.8125rem;
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>Secretary Panel</h1>
    <div class="header-actions">
      <span class="user-info" id="user-info"></span>
      <a href="/" class="btn btn-secondary">View Dashboard</a>
      <button onclick="logout()" class="btn btn-secondary">Logout</button>
    </div>
  </header>

  <div class="container">
    <!-- Register Student -->
    <div class="panel">
      <div class="panel-header">
        <h2>\u{1F4DD} Register Student</h2>
      </div>
      <div class="panel-body">
        <form id="register-form">
          <div class="form-row">
            <div class="form-group">
              <label>First Name *</label>
              <input type="text" id="first_name" required placeholder="John">
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input type="text" id="last_name" required placeholder="Doe">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email</label>
              <input type="email" id="email" placeholder="john@university.edu">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" id="phone" placeholder="555-123-4567">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Major</label>
              <input type="text" id="major" placeholder="Computer Science">
            </div>
            <div class="form-group">
              <label>Graduation Year</label>
              <select id="graduation_year">
                <option value="">Select Year</option>
                <option value="2024">2024</option>
                <option value="2025">2025</option>
                <option value="2026">2026</option>
                <option value="2027">2027</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="width: 100%">Register Student</button>
        </form>

        <div class="queue-section">
          <h3 style="margin-bottom: 1rem; font-size: 1rem;">Quick Queue</h3>
          <form id="enqueue-form" class="enqueue-form">
            <select id="enqueue-student" required>
              <option value="">Select Student</option>
            </select>
            <select id="enqueue-company" required>
              <option value="">Select Company</option>
            </select>
            <button type="submit" class="btn btn-success">Add to Queue</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Students & Queue -->
    <div class="panel">
      <div class="panel-header">
        <h2>\u{1F465} Registered Students</h2>
        <button onclick="loadStudents()" class="btn btn-secondary">Refresh</button>
      </div>
      <div class="panel-body">
        <div class="student-list" id="student-list">
          <p style="color: var(--text-secondary)">Loading...</p>
        </div>
      </div>
    </div>
  </div>

  <div id="toast" class="success-toast"></div>

  <script>
    let students = [];
    let companies = [];

    async function checkAuth() {
      // DEV MODE: Skip auth for testing
      document.getElementById('user-info').textContent = 'DEV MODE (no auth)';
      return;
      /* Original auth check:
      try {
        const res = await fetch('/api/auth/me');
        const data = await res.json();
        if (!data.success || data.data.role !== 'SECRETARY') {
          window.location.href = '/login';
          return;
        }
        document.getElementById('user-info').textContent = 'Logged in as: ' + data.data.username;
      } catch (e) {
        window.location.href = '/login';
      }
      */
    }

    async function loadStudents() {
      try {
        const res = await fetch('/api/students');
        const data = await res.json();
        if (data.success) {
          students = data.data;
          renderStudents();
          updateStudentDropdown();
        }
      } catch (e) {
        console.error('Failed to load students:', e);
      }
    }

    async function loadCompanies() {
      try {
        const res = await fetch('/api/interviewers');
        const data = await res.json();
        if (data.success) {
          companies = data.data;
          updateCompanyDropdown();
        }
      } catch (e) {
        console.error('Failed to load companies:', e);
      }
    }

    function renderStudents() {
      const list = document.getElementById('student-list');
      if (students.length === 0) {
        list.innerHTML = '<p style="color: var(--text-secondary)">No students registered yet</p>';
        return;
      }
      list.innerHTML = students.map(s => \`
        <div class="student-item">
          <div class="student-info">
            <h4>\${s.first_name} \${s.last_name}</h4>
            <p>\${s.major || 'No major'} \u2022 \${s.graduation_year || 'N/A'}</p>
          </div>
          <span style="color: var(--text-secondary); font-size: 0.75rem">#\${s.id}</span>
        </div>
      \`).join('');
    }

    function updateStudentDropdown() {
      const select = document.getElementById('enqueue-student');
      select.innerHTML = '<option value="">Select Student</option>' + 
        students.map(s => \`<option value="\${s.id}">\${s.first_name} \${s.last_name}</option>\`).join('');
    }

    function updateCompanyDropdown() {
      const select = document.getElementById('enqueue-company');
      select.innerHTML = '<option value="">Select Company</option>' + 
        companies.map(c => \`<option value="\${c.id}">\${c.name}</option>\`).join('');
    }

    function showToast(msg) {
      const toast = document.getElementById('toast');
      toast.textContent = msg;
      toast.style.display = 'block';
      setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    document.getElementById('register-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = {
        first_name: document.getElementById('first_name').value,
        last_name: document.getElementById('last_name').value,
        email: document.getElementById('email').value || undefined,
        phone: document.getElementById('phone').value || undefined,
        major: document.getElementById('major').value || undefined,
        graduation_year: document.getElementById('graduation_year').value || undefined
      };

      try {
        const res = await fetch('/api/students', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        });
        const data = await res.json();
        if (data.success) {
          showToast('\u2705 Student registered!');
          e.target.reset();
          loadStudents();
        } else {
          alert(data.error || 'Failed to register');
        }
      } catch (e) {
        alert('Connection error');
      }
    });

    document.getElementById('enqueue-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const interviewee_id = parseInt(document.getElementById('enqueue-student').value);
      const interviewer_id = parseInt(document.getElementById('enqueue-company').value);

      try {
        const res = await fetch('/api/queue', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ interviewee_id, interviewer_id })
        });
        const data = await res.json();
        if (data.success) {
          showToast('\u2705 Added to queue (Position #' + data.data.queue_position + ')');
          e.target.reset();
        } else {
          alert(data.error || 'Failed to enqueue');
        }
      } catch (e) {
        alert('Connection error');
      }
    });

    async function logout() {
      await fetch('/api/auth/logout', { method: 'POST' });
      window.location.href = '/login';
    }

    // Initialize
    checkAuth();
    loadStudents();
    loadCompanies();
  <\/script>
</body>
</html>`;
}
__name(getSecretaryPageHtml, "getSecretaryPageHtml");

// src/pages/company.ts
function getCompanyPageHtml() {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Panel - Career Fair</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-primary: #0f0f23;
      --bg-secondary: #1a1a2e;
      --bg-card: #16213e;
      --accent-primary: #6366f1;
      --accent-secondary: #8b5cf6;
      --accent-success: #10b981;
      --accent-warning: #f59e0b;
      --accent-danger: #ef4444;
      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --border: rgba(255, 255, 255, 0.1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      background-image: 
        radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
    }

    .header {
      background: var(--bg-secondary);
      border-bottom: 1px solid var(--border);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header h1 {
      font-size: 1.5rem;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .header-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .user-info {
      color: var(--text-secondary);
      font-size: 0.875rem;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 0.75rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-family: inherit;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      color: white;
    }

    .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4); }

    .btn-secondary {
      background: var(--bg-card);
      color: var(--text-secondary);
      border: 1px solid var(--border);
      padding: 0.625rem 1.25rem;
      font-size: 0.875rem;
    }

    .btn-secondary:hover { background: var(--bg-secondary); color: var(--text-primary); }

    .btn-success {
      background: linear-gradient(135deg, var(--accent-success), #059669);
      color: white;
    }

    .btn-success:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4); }

    .btn-warning {
      background: linear-gradient(135deg, var(--accent-warning), #d97706);
      color: white;
    }

    .btn-warning:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4); }

    .btn-danger {
      background: var(--accent-danger);
      color: white;
    }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 3rem 2rem;
    }

    .status-card {
      background: var(--bg-card);
      border-radius: 1.5rem;
      border: 1px solid var(--border);
      padding: 3rem;
      text-align: center;
      margin-bottom: 2rem;
    }

    .status-badge {
      display: inline-block;
      padding: 0.5rem 1.25rem;
      border-radius: 2rem;
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: 1.5rem;
    }

    .status-idle { background: rgba(148, 163, 184, 0.15); color: var(--text-secondary); }
    .status-calling { background: rgba(245, 158, 11, 0.15); color: var(--accent-warning); }
    .status-interviewing { background: rgba(16, 185, 129, 0.15); color: var(--accent-success); }

    .current-student {
      font-size: 3rem;
      font-weight: 700;
      margin: 1rem 0;
      background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .action-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 2rem;
    }

    .action-buttons .btn {
      min-width: 150px;
      padding: 1rem 2rem;
      font-size: 1.125rem;
    }

    .queue-panel {
      background: var(--bg-card);
      border-radius: 1rem;
      border: 1px solid var(--border);
      overflow: hidden;
    }

    .queue-header {
      background: var(--bg-secondary);
      padding: 1rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .queue-header h2 {
      font-size: 1.125rem;
      font-weight: 600;
    }

    .queue-count {
      background: var(--accent-primary);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.875rem;
      font-weight: 600;
    }

    .queue-list {
      padding: 1rem;
    }

    .queue-item {
      display: flex;
      align-items: center;
      padding: 1rem;
      background: var(--bg-secondary);
      border-radius: 0.75rem;
      margin-bottom: 0.5rem;
    }

    .queue-position {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      margin-right: 1rem;
    }

    .queue-student-info h4 {
      font-weight: 500;
    }

    .queue-student-info p {
      font-size: 0.8125rem;
      color: var(--text-secondary);
    }

    .empty-queue {
      text-align: center;
      padding: 2rem;
      color: var(--text-secondary);
    }

    .calling-animation {
      animation: pulseGlow 1.5s ease-in-out infinite;
    }

    @keyframes pulseGlow {
      0%, 100% { box-shadow: 0 0 30px rgba(245, 158, 11, 0.3); }
      50% { box-shadow: 0 0 60px rgba(245, 158, 11, 0.5); }
    }

    .interviewing-glow {
      box-shadow: 0 0 40px rgba(16, 185, 129, 0.3);
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>Company Panel</h1>
    <div class="header-actions">
      <span class="user-info" id="user-info"></span>
      <a href="/" class="btn btn-secondary">View Dashboard</a>
      <button onclick="logout()" class="btn btn-secondary">Logout</button>
    </div>
  </header>

  <div class="container">
    <div class="status-card" id="status-card">
      <span class="status-badge status-idle" id="status-badge">Ready</span>
      <div class="current-student" id="current-student">No Active Interview</div>
      <p style="color: var(--text-secondary)" id="status-message">Call the next student in your queue</p>
      <div class="action-buttons" id="action-buttons">
        <button class="btn btn-primary" id="btn-call-next" onclick="callNext()">
          \u{1F4E2} Call Next
        </button>
      </div>
    </div>

    <div class="queue-panel">
      <div class="queue-header">
        <h2>\u{1F4CB} Your Queue</h2>
        <span class="queue-count" id="queue-count">0</span>
      </div>
      <div class="queue-list" id="queue-list">
        <div class="empty-queue">Loading...</div>
      </div>
    </div>
  </div>

  <script>
    let currentStatus = 'IDLE';
    let currentInterview = null;
    let interviewerId = null;

    async function checkAuth() {
      // DEV MODE: Skip auth, use company ID 1 for testing
      document.getElementById('user-info').textContent = 'DEV MODE (TechCorp)';
      interviewerId = 1; // Default to first company
      loadQueue();
      return;
      /* Original auth check:
      try {
        const res = await fetch('/api/auth/me');
        const data = await res.json();
        if (!data.success || data.data.role !== 'COMPANY') {
          window.location.href = '/login';
          return;
        }
        document.getElementById('user-info').textContent = 'Logged in as: ' + data.data.username;
        interviewerId = data.data.interviewer_id;
        loadQueue();
      } catch (e) {
        window.location.href = '/login';
      }
      */
    }

    async function loadQueue() {
      try {
        const res = await fetch('/api/queue?interviewer_id=' + interviewerId);
        const data = await res.json();
        if (data.success) {
          renderQueue(data.data);
          detectCurrentStatus(data.data);
        }
      } catch (e) {
        console.error('Failed to load queue:', e);
      }
    }

    function detectCurrentStatus(queue) {
      const calling = queue.find(i => i.status === 'CALLING');
      const happening = queue.find(i => i.status === 'HAPPENING');

      if (calling) {
        setStatus('CALLING', calling.first_name + ' ' + calling.last_name, calling);
      } else if (happening) {
        setStatus('HAPPENING', happening.first_name + ' ' + happening.last_name, happening);
      } else {
        setStatus('IDLE', null, null);
      }
    }

    function setStatus(status, studentName, interview) {
      currentStatus = status;
      currentInterview = interview;

      const card = document.getElementById('status-card');
      const badge = document.getElementById('status-badge');
      const studentEl = document.getElementById('current-student');
      const messageEl = document.getElementById('status-message');
      const actions = document.getElementById('action-buttons');

      card.className = 'status-card';

      if (status === 'IDLE') {
        badge.className = 'status-badge status-idle';
        badge.textContent = 'Ready';
        studentEl.textContent = 'No Active Interview';
        messageEl.textContent = 'Call the next student in your queue';
        actions.innerHTML = '<button class="btn btn-primary" onclick="callNext()">\u{1F4E2} Call Next</button>';
      } else if (status === 'CALLING') {
        card.classList.add('calling-animation');
        badge.className = 'status-badge status-calling';
        badge.textContent = 'Now Calling';
        studentEl.textContent = studentName;
        messageEl.textContent = 'Please wait for the student to arrive';
        actions.innerHTML = \`
          <button class="btn btn-success" onclick="startInterview()">\u2713 Student Arrived</button>
          <button class="btn btn-danger" onclick="markNoShow()">\u2717 No Show</button>
        \`;
      } else if (status === 'HAPPENING') {
        card.classList.add('interviewing-glow');
        badge.className = 'status-badge status-interviewing';
        badge.textContent = 'Interviewing';
        studentEl.textContent = studentName;
        messageEl.textContent = 'Interview in progress';
        actions.innerHTML = '<button class="btn btn-success" onclick="completeInterview()">\u2713 Complete Interview</button>';
      }
    }

    function renderQueue(queue) {
      const list = document.getElementById('queue-list');
      const countEl = document.getElementById('queue-count');
      
      const enqueued = queue.filter(i => i.status === 'ENQUEUED');
      countEl.textContent = enqueued.length;

      if (enqueued.length === 0) {
        list.innerHTML = '<div class="empty-queue">No students in queue</div>';
        return;
      }

      list.innerHTML = enqueued.map((item, idx) => \`
        <div class="queue-item">
          <div class="queue-position">\${idx + 1}</div>
          <div class="queue-student-info">
            <h4>\${item.first_name} \${item.last_name}</h4>
            <p>\${item.major || 'No major specified'}</p>
          </div>
        </div>
      \`).join('');
    }

    async function callNext() {
      try {
        const res = await fetch('/api/call-next', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
          loadQueue();
        } else {
          alert(data.error || 'Failed to call next');
        }
      } catch (e) {
        alert('Connection error');
      }
    }

    async function startInterview() {
      try {
        const res = await fetch('/api/start-interview', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
          loadQueue();
        } else {
          alert(data.error || 'Failed to start interview');
        }
      } catch (e) {
        alert('Connection error');
      }
    }

    async function completeInterview() {
      try {
        const res = await fetch('/api/complete-interview', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({})
        });
        const data = await res.json();
        if (data.success) {
          loadQueue();
        } else {
          alert(data.error || 'Failed to complete interview');
        }
      } catch (e) {
        alert('Connection error');
      }
    }

    async function markNoShow() {
      if (!confirm('Mark this student as no-show?')) return;
      
      try {
        const res = await fetch('/api/no-show', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
          loadQueue();
        } else {
          alert(data.error || 'Failed to mark no-show');
        }
      } catch (e) {
        alert('Connection error');
      }
    }

    async function logout() {
      await fetch('/api/auth/logout', { method: 'POST' });
      window.location.href = '/login';
    }

    // Initialize
    checkAuth();
    
    // Auto-refresh queue every 5 seconds
    setInterval(loadQueue, 5000);
  <\/script>
</body>
</html>`;
}
__name(getCompanyPageHtml, "getCompanyPageHtml");

// src/pages/gatekeeper.ts
function getGatekeeperPageHtml() {
  return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gatekeeper Panel - CDIQ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #f0f0f0;
        }

        .header {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(233, 69, 96, 0.3);
        }

        .main {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .company-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        .company-card.paused {
            opacity: 0.6;
            border-color: rgba(255, 193, 7, 0.5);
        }

        .company-card.calling {
            border-color: #e94560;
            box-shadow: 0 0 20px rgba(233, 69, 96, 0.3);
            animation: pulse 2s infinite;
        }

        .company-card.interviewing {
            border-color: #4CAF50;
            box-shadow: 0 0 15px rgba(76, 175, 80, 0.3);
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(233, 69, 96, 0.3); }
            50% { box-shadow: 0 0 30px rgba(233, 69, 96, 0.5); }
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .company-name {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .company-table {
            font-size: 0.85rem;
            color: #888;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-idle { background: rgba(100, 100, 100, 0.5); }
        .status-calling { background: rgba(233, 69, 96, 0.5); color: #fff; }
        .status-interviewing { background: rgba(76, 175, 80, 0.5); color: #fff; }
        .status-paused { background: rgba(255, 193, 7, 0.5); color: #000; }

        .current-student {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
        }

        .current-student .name {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .current-student .status {
            font-size: 0.85rem;
            color: #888;
            margin-top: 0.25rem;
        }

        .queue-list {
            margin: 1rem 0;
            max-height: 150px;
            overflow-y: auto;
        }

        .queue-item {
            padding: 0.5rem;
            background: rgba(255,255,255,0.03);
            border-radius: 4px;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .queue-item.paused {
            opacity: 0.5;
            text-decoration: line-through;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.85rem;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-call { background: linear-gradient(135deg, #e94560, #ff6b6b); color: white; }
        .btn-start { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; }
        .btn-complete { background: linear-gradient(135deg, #2196F3, #1976D2); color: white; }
        .btn-noshow { background: linear-gradient(135deg, #ff9800, #f57c00); color: white; }
        .btn-pause { background: rgba(255, 193, 7, 0.3); color: #ffc107; border: 1px solid #ffc107; }

        .btn:hover:not(:disabled) { transform: translateY(-2px); }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #888;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>\u{1F6AA} Gatekeeper Panel</h1>
        <div class="user-info">
            <span id="userDisplay">Loading...</span>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </header>

    <main class="main">
        <div id="companiesContainer" class="companies-grid">
            <div class="loading">Loading companies...</div>
        </div>
    </main>

    <script>
        let companiesData = [];
        let queueData = [];

        async function checkAuth() {
            // DEV MODE: Skip auth for testing
            document.getElementById('userDisplay').textContent = 'DEV MODE (no auth)';
            return;
            /* Original auth check:
            try {
                const res = await fetch('/api/auth/me');
                const data = await res.json();
                if (!data.success || data.data.role !== 'GATEKEEPER') {
                    window.location.href = '/login';
                    return;
                }
                document.getElementById('userDisplay').textContent = 'Gatekeeper: ' + data.data.username;
            } catch {
                window.location.href = '/login';
            }
            */
        }

        async function fetchData() {
            try {
                const [dashRes, queueRes] = await Promise.all([
                    fetch('/api/dashboard'),
                    fetch('/api/gatekeeper/queue')
                ]);
                
                const dashData = await dashRes.json();
                const qData = await queueRes.json();
                
                if (dashData.success) companiesData = dashData.data.interviewers;
                if (qData.success) queueData = qData.data;
                
                renderCompanies();
            } catch (e) {
                console.error('Failed to fetch data:', e);
            }
        }

        function getQueueForCompany(companyId) {
            return queueData.filter(q => q.interviewer_id === companyId);
        }

        function getCurrentInterview(companyId) {
            return queueData.find(q => 
                q.interviewer_id === companyId && 
                (q.status === 'CALLING' || q.status === 'HAPPENING')
            );
        }

        function renderCompanies() {
            const container = document.getElementById('companiesContainer');
            
            if (!companiesData.length) {
                container.innerHTML = '<div class="loading">No companies found</div>';
                return;
            }

            container.innerHTML = companiesData.map(company => {
                const queue = getQueueForCompany(company.id).filter(q => q.status === 'ENQUEUED');
                const current = getCurrentInterview(company.id);
                const statusClass = company.is_paused ? 'paused' : 
                    company.status === 'CALLING' ? 'calling' : 
                    company.status === 'INTERVIEWING' ? 'interviewing' : '';

                return \`
                    <div class="company-card \${statusClass}">
                        <div class="company-header">
                            <div>
                                <div class="company-name">\${company.name}</div>
                                <div class="company-table">Table: \${company.table_number || 'N/A'}</div>
                            </div>
                            <span class="status-badge status-\${company.status.toLowerCase()}">\${company.status}</span>
                        </div>

                        \${current ? \`
                            <div class="current-student">
                                <div class="name">\${current.first_name} \${current.last_name}</div>
                                <div class="status">\${current.status} \${current.major ? '\u2022 ' + current.major : ''}</div>
                            </div>
                        \` : \`
                            <div class="current-student">
                                <div class="name" style="color: #888">No active interview</div>
                            </div>
                        \`}

                        <div class="queue-list">
                            \${queue.length ? queue.map((q, i) => \`
                                <div class="queue-item \${q.student_paused ? 'paused' : ''}">
                                    <span>\${i + 1}. \${q.first_name} \${q.last_name}</span>
                                    <span style="color: #888">\${q.major || ''}</span>
                                </div>
                            \`).join('') : '<div style="color: #666; text-align: center; padding: 0.5rem">Queue empty</div>'}
                        </div>

                        <div class="actions">
                            <button class="btn btn-call" 
                                onclick="callNext(\${company.id})" 
                                \${company.is_paused || current || !queue.length ? 'disabled' : ''}>
                                \u{1F4E2} Call Next
                            </button>
                            <button class="btn btn-start" 
                                onclick="startInterview(\${current?.id})"
                                \${!current || current.status !== 'CALLING' ? 'disabled' : ''}>
                                \u2713 Arrived
                            </button>
                            <button class="btn btn-complete" 
                                onclick="completeInterview(\${current?.id})"
                                \${!current || current.status !== 'HAPPENING' ? 'disabled' : ''}>
                                \u2713 Complete
                            </button>
                            <button class="btn btn-noshow" 
                                onclick="noShow(\${current?.id})"
                                \${!current ? 'disabled' : ''}>
                                \u2717 No Show
                            </button>
                            <button class="btn btn-pause" 
                                onclick="togglePause(\${company.id})"
                                style="grid-column: span 2">
                                \${company.is_paused ? '\u25B6 Unpause' : '\u23F8 Pause'} Company
                            </button>
                        </div>
                    </div>
                \`;
            }).join('');
        }

        async function callNext(companyId) {
            try {
                const res = await fetch('/api/gatekeeper/call-next', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ interviewer_id: companyId })
                });
                const data = await res.json();
                if (!data.success) alert(data.error);
                fetchData();
            } catch (e) { alert('Failed: ' + e.message); }
        }

        async function startInterview(interviewId) {
            if (!interviewId) return;
            try {
                const res = await fetch('/api/gatekeeper/action', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ interview_id: interviewId, action: 'start' })
                });
                fetchData();
            } catch (e) { alert('Failed: ' + e.message); }
        }

        async function completeInterview(interviewId) {
            if (!interviewId) return;
            try {
                const res = await fetch('/api/gatekeeper/action', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ interview_id: interviewId, action: 'complete' })
                });
                fetchData();
            } catch (e) { alert('Failed: ' + e.message); }
        }

        async function noShow(interviewId) {
            if (!interviewId) return;
            if (!confirm('Mark this student as no-show?')) return;
            try {
                const res = await fetch('/api/gatekeeper/action', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ interview_id: interviewId, action: 'no_show' })
                });
                fetchData();
            } catch (e) { alert('Failed: ' + e.message); }
        }

        async function togglePause(companyId) {
            try {
                const res = await fetch('/api/interviewers/toggle-pause?id=' + companyId, {
                    method: 'POST'
                });
                fetchData();
            } catch (e) { alert('Failed: ' + e.message); }
        }

        async function logout() {
            await fetch('/api/auth/logout', { method: 'POST' });
            window.location.href = '/login';
        }

        // Initialize
        checkAuth();
        fetchData();
        setInterval(fetchData, 2000);
    <\/script>
</body>
</html>`;
}
__name(getGatekeeperPageHtml, "getGatekeeperPageHtml");

// src/index.ts
var src_default = {
  async fetch(request, env) {
    const url = new URL(request.url);
    const path = url.pathname;
    const method = request.method;
    const corsHeaders = {
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
      "Access-Control-Allow-Headers": "Content-Type, Authorization"
    };
    if (method === "OPTIONS") {
      return new Response(null, { headers: corsHeaders });
    }
    try {
      if (path.startsWith("/api/")) {
        let response;
        if (path === "/api/auth/login" && method === "POST") {
          response = await handleLogin(request, env);
        } else if (path === "/api/auth/logout" && method === "POST") {
          response = await handleLogout(request, env);
        } else if (path === "/api/auth/me" && method === "GET") {
          response = await handleGetMe(request, env);
        } else if (path === "/api/students" && method === "POST") {
          response = await handleRegisterStudent(request, env);
        } else if (path === "/api/students" && method === "GET") {
          response = await handleGetStudents(request, env);
        } else if (path === "/api/queue" && method === "POST") {
          response = await handleEnqueueStudent(request, env);
        } else if (path === "/api/queue" && method === "GET") {
          response = await handleGetQueue(request, env);
        } else if (path === "/api/interviewers" && method === "GET") {
          response = await handleGetInterviewers(request, env);
        } else if (path === "/api/call-next" && method === "POST") {
          response = await handleCallNext(request, env);
        } else if (path === "/api/start-interview" && method === "POST") {
          response = await handleStartInterview(request, env);
        } else if (path === "/api/complete-interview" && method === "POST") {
          response = await handleCompleteInterview(request, env);
        } else if (path === "/api/no-show" && method === "POST") {
          response = await handleNoShow(request, env);
        } else if (path === "/api/dashboard" && method === "GET") {
          response = await handleDashboard(env);
        } else if (path === "/api/interviewers" && method === "POST") {
          response = await handleCreateInterviewer(request, env);
        } else if (path.startsWith("/api/interviewers") && method === "PUT") {
          response = await handleUpdateInterviewer(request, env);
        } else if (path.startsWith("/api/interviewers") && method === "DELETE") {
          response = await handleDeleteInterviewer(request, env);
        } else if (path.startsWith("/api/students") && method === "DELETE") {
          response = await handleDeleteStudent(request, env);
        } else if (path === "/api/students/toggle-pause" && method === "POST") {
          response = await handleToggleStudentPause(request, env);
        } else if (path === "/api/interviewers/toggle-pause" && method === "POST") {
          response = await handleToggleInterviewerPause(request, env);
        } else if (path === "/api/gatekeeper/call-next" && method === "POST") {
          response = await handleGatekeeperCallNext(request, env);
        } else if (path === "/api/gatekeeper/action" && method === "POST") {
          response = await handleGatekeeperAction(request, env);
        } else if (path === "/api/gatekeeper/queue" && method === "GET") {
          response = await handleGatekeeperQueue(request, env);
        } else {
          response = new Response(JSON.stringify({ success: false, error: "Not found" }), {
            status: 404,
            headers: { "Content-Type": "application/json" }
          });
        }
        const newHeaders = new Headers(response.headers);
        Object.entries(corsHeaders).forEach(([key, value]) => {
          newHeaders.set(key, value);
        });
        return new Response(response.body, {
          status: response.status,
          headers: newHeaders
        });
      }
      if (path === "/" || path === "/dashboard") {
        return new Response(getPublicDashboardHtml(), {
          headers: { "Content-Type": "text/html" }
        });
      }
      if (path === "/login") {
        return new Response(getLoginPageHtml(), {
          headers: { "Content-Type": "text/html" }
        });
      }
      if (path === "/secretary") {
        return new Response(getSecretaryPageHtml(), {
          headers: { "Content-Type": "text/html" }
        });
      }
      if (path === "/company") {
        return new Response(getCompanyPageHtml(), {
          headers: { "Content-Type": "text/html" }
        });
      }
      if (path === "/gatekeeper") {
        return new Response(getGatekeeperPageHtml(), {
          headers: { "Content-Type": "text/html" }
        });
      }
      return new Response("Not Found", { status: 404 });
    } catch (error) {
      console.error("Worker error:", error);
      return new Response(JSON.stringify({
        success: false,
        error: "Internal server error"
      }), {
        status: 500,
        headers: { "Content-Type": "application/json" }
      });
    }
  }
};

// node_modules/wrangler/templates/middleware/middleware-ensure-req-body-drained.ts
var drainBody = /* @__PURE__ */ __name(async (request, env, _ctx, middlewareCtx) => {
  try {
    return await middlewareCtx.next(request, env);
  } finally {
    try {
      if (request.body !== null && !request.bodyUsed) {
        const reader = request.body.getReader();
        while (!(await reader.read()).done) {
        }
      }
    } catch (e) {
      console.error("Failed to drain the unused request body.", e);
    }
  }
}, "drainBody");
var middleware_ensure_req_body_drained_default = drainBody;

// node_modules/wrangler/templates/middleware/middleware-miniflare3-json-error.ts
function reduceError(e) {
  return {
    name: e?.name,
    message: e?.message ?? String(e),
    stack: e?.stack,
    cause: e?.cause === void 0 ? void 0 : reduceError(e.cause)
  };
}
__name(reduceError, "reduceError");
var jsonError = /* @__PURE__ */ __name(async (request, env, _ctx, middlewareCtx) => {
  try {
    return await middlewareCtx.next(request, env);
  } catch (e) {
    const error = reduceError(e);
    return Response.json(error, {
      status: 500,
      headers: { "MF-Experimental-Error-Stack": "true" }
    });
  }
}, "jsonError");
var middleware_miniflare3_json_error_default = jsonError;

// .wrangler/tmp/bundle-NXjgIc/middleware-insertion-facade.js
var __INTERNAL_WRANGLER_MIDDLEWARE__ = [
  middleware_ensure_req_body_drained_default,
  middleware_miniflare3_json_error_default
];
var middleware_insertion_facade_default = src_default;

// node_modules/wrangler/templates/middleware/common.ts
var __facade_middleware__ = [];
function __facade_register__(...args) {
  __facade_middleware__.push(...args.flat());
}
__name(__facade_register__, "__facade_register__");
function __facade_invokeChain__(request, env, ctx, dispatch, middlewareChain) {
  const [head, ...tail] = middlewareChain;
  const middlewareCtx = {
    dispatch,
    next(newRequest, newEnv) {
      return __facade_invokeChain__(newRequest, newEnv, ctx, dispatch, tail);
    }
  };
  return head(request, env, ctx, middlewareCtx);
}
__name(__facade_invokeChain__, "__facade_invokeChain__");
function __facade_invoke__(request, env, ctx, dispatch, finalMiddleware) {
  return __facade_invokeChain__(request, env, ctx, dispatch, [
    ...__facade_middleware__,
    finalMiddleware
  ]);
}
__name(__facade_invoke__, "__facade_invoke__");

// .wrangler/tmp/bundle-NXjgIc/middleware-loader.entry.ts
var __Facade_ScheduledController__ = class {
  constructor(scheduledTime, cron, noRetry) {
    this.scheduledTime = scheduledTime;
    this.cron = cron;
    this.#noRetry = noRetry;
  }
  #noRetry;
  noRetry() {
    if (!(this instanceof __Facade_ScheduledController__)) {
      throw new TypeError("Illegal invocation");
    }
    this.#noRetry();
  }
};
__name(__Facade_ScheduledController__, "__Facade_ScheduledController__");
function wrapExportedHandler(worker) {
  if (__INTERNAL_WRANGLER_MIDDLEWARE__ === void 0 || __INTERNAL_WRANGLER_MIDDLEWARE__.length === 0) {
    return worker;
  }
  for (const middleware of __INTERNAL_WRANGLER_MIDDLEWARE__) {
    __facade_register__(middleware);
  }
  const fetchDispatcher = /* @__PURE__ */ __name(function(request, env, ctx) {
    if (worker.fetch === void 0) {
      throw new Error("Handler does not export a fetch() function.");
    }
    return worker.fetch(request, env, ctx);
  }, "fetchDispatcher");
  return {
    ...worker,
    fetch(request, env, ctx) {
      const dispatcher = /* @__PURE__ */ __name(function(type, init) {
        if (type === "scheduled" && worker.scheduled !== void 0) {
          const controller = new __Facade_ScheduledController__(
            Date.now(),
            init.cron ?? "",
            () => {
            }
          );
          return worker.scheduled(controller, env, ctx);
        }
      }, "dispatcher");
      return __facade_invoke__(request, env, ctx, dispatcher, fetchDispatcher);
    }
  };
}
__name(wrapExportedHandler, "wrapExportedHandler");
function wrapWorkerEntrypoint(klass) {
  if (__INTERNAL_WRANGLER_MIDDLEWARE__ === void 0 || __INTERNAL_WRANGLER_MIDDLEWARE__.length === 0) {
    return klass;
  }
  for (const middleware of __INTERNAL_WRANGLER_MIDDLEWARE__) {
    __facade_register__(middleware);
  }
  return class extends klass {
    #fetchDispatcher = (request, env, ctx) => {
      this.env = env;
      this.ctx = ctx;
      if (super.fetch === void 0) {
        throw new Error("Entrypoint class does not define a fetch() function.");
      }
      return super.fetch(request);
    };
    #dispatcher = (type, init) => {
      if (type === "scheduled" && super.scheduled !== void 0) {
        const controller = new __Facade_ScheduledController__(
          Date.now(),
          init.cron ?? "",
          () => {
          }
        );
        return super.scheduled(controller);
      }
    };
    fetch(request) {
      return __facade_invoke__(
        request,
        this.env,
        this.ctx,
        this.#dispatcher,
        this.#fetchDispatcher
      );
    }
  };
}
__name(wrapWorkerEntrypoint, "wrapWorkerEntrypoint");
var WRAPPED_ENTRY;
if (typeof middleware_insertion_facade_default === "object") {
  WRAPPED_ENTRY = wrapExportedHandler(middleware_insertion_facade_default);
} else if (typeof middleware_insertion_facade_default === "function") {
  WRAPPED_ENTRY = wrapWorkerEntrypoint(middleware_insertion_facade_default);
}
var middleware_loader_entry_default = WRAPPED_ENTRY;
export {
  __INTERNAL_WRANGLER_MIDDLEWARE__,
  middleware_loader_entry_default as default
};
//# sourceMappingURL=index.js.map
