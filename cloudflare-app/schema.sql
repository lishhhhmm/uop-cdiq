-- CDIQ Database Schema for Cloudflare D1

-- Operators: Users who can access the system (Secretaries, Gatekeepers, and Companies)
CREATE TABLE IF NOT EXISTS operators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('SECRETARY', 'GATEKEEPER', 'COMPANY')),
    interviewer_id INTEGER,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (interviewer_id) REFERENCES interviewers(id)
);


-- Interviewers: Companies/tables that conduct interviews
CREATE TABLE IF NOT EXISTS interviewers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    table_number TEXT,
    is_active INTEGER DEFAULT 1,
    is_paused INTEGER DEFAULT 0,
    image_url TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);


-- Interviewees: Students registered for interviews
CREATE TABLE IF NOT EXISTS interviewees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    major TEXT,
    graduation_year TEXT,
    is_paused INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);


-- Interviews: The queue and interview state
CREATE TABLE IF NOT EXISTS interviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    interviewee_id INTEGER NOT NULL,
    interviewer_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'ENQUEUED' CHECK (status IN ('ENQUEUED', 'CALLING', 'HAPPENING', 'COMPLETED', 'NO_SHOW')),
    queue_position INTEGER,
    enqueued_at TEXT DEFAULT (datetime('now')),
    called_at TEXT,
    started_at TEXT,
    completed_at TEXT,
    notes TEXT,
    FOREIGN KEY (interviewee_id) REFERENCES interviewees(id),
    FOREIGN KEY (interviewer_id) REFERENCES interviewers(id)
);

-- Sessions: Simple session management
CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    operator_id INTEGER NOT NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (operator_id) REFERENCES operators(id)
);

-- Indexes for common queries
CREATE INDEX IF NOT EXISTS idx_interviews_status ON interviews(status);
CREATE INDEX IF NOT EXISTS idx_interviews_interviewer ON interviews(interviewer_id);
CREATE INDEX IF NOT EXISTS idx_interviews_queue ON interviews(interviewer_id, status, queue_position);
CREATE INDEX IF NOT EXISTS idx_sessions_expires ON sessions(expires_at);
