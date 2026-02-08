// Authentication utilities for session management
import type { Env, Operator, Session } from './types';

// Generate a random session ID
export function generateSessionId(): string {
    const array = new Uint8Array(32);
    crypto.getRandomValues(array);
    return Array.from(array, b => b.toString(16).padStart(2, '0')).join('');
}

// Hash password using SHA-256
export async function hashPassword(password: string): Promise<string> {
    const encoder = new TextEncoder();
    const data = encoder.encode(password);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

// Verify password against stored hash
export async function verifyPassword(password: string, hash: string): Promise<boolean> {
    const passwordHash = await hashPassword(password);
    return passwordHash === hash;
}

// Create a new session
export async function createSession(db: D1Database, operatorId: number): Promise<string> {
    const sessionId = generateSessionId();
    const expiresAt = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(); // 24 hours

    await db.prepare(
        'INSERT INTO sessions (id, operator_id, expires_at) VALUES (?, ?, ?)'
    ).bind(sessionId, operatorId, expiresAt).run();

    return sessionId;
}

// Validate session and return operator
export async function validateSession(
    db: D1Database,
    sessionId: string | null
): Promise<{ operator: Operator; session: Session } | null> {
    if (!sessionId) return null;

    const now = new Date().toISOString();

    const result = await db.prepare(`
    SELECT 
      s.id as session_id, s.operator_id, s.expires_at, s.created_at as session_created,
      o.id, o.username, o.password_hash, o.role, o.interviewer_id, o.created_at
    FROM sessions s
    JOIN operators o ON s.operator_id = o.id
    WHERE s.id = ? AND s.expires_at > ?
  `).bind(sessionId, now).first<{
        session_id: string;
        operator_id: number;
        expires_at: string;
        session_created: string;
        id: number;
        username: string;
        password_hash: string;
        role: 'SECRETARY' | 'COMPANY';
        interviewer_id: number | null;
        created_at: string;
    }>();

    if (!result) return null;

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

// Delete session (logout)
export async function deleteSession(db: D1Database, sessionId: string): Promise<void> {
    await db.prepare('DELETE FROM sessions WHERE id = ?').bind(sessionId).run();
}

// Clean up expired sessions
export async function cleanupExpiredSessions(db: D1Database): Promise<void> {
    const now = new Date().toISOString();
    await db.prepare('DELETE FROM sessions WHERE expires_at < ?').bind(now).run();
}

// Get session ID from cookies
export function getSessionFromCookies(request: Request): string | null {
    const cookieHeader = request.headers.get('Cookie');
    if (!cookieHeader) return null;

    const cookies = cookieHeader.split(';').map(c => c.trim());
    for (const cookie of cookies) {
        const [name, value] = cookie.split('=');
        if (name === 'session') {
            return value;
        }
    }
    return null;
}

// Create Set-Cookie header for session
export function createSessionCookie(sessionId: string): string {
    return `session=${sessionId}; Path=/; HttpOnly; SameSite=Strict; Max-Age=86400`;
}

// Create expired cookie for logout
export function createLogoutCookie(): string {
    return `session=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0`;
}
