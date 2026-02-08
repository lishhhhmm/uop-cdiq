// Type definitions for the CDIQ Cloudflare Worker

export interface Env {
    DB: D1Database;
    SESSION_SECRET: string;
}

// Database row types
export interface Operator {
    id: number;
    username: string;
    password_hash: string;
    role: 'SECRETARY' | 'GATEKEEPER' | 'COMPANY';
    interviewer_id: number | null;
    created_at: string;
}

export interface Interviewer {
    id: number;
    name: string;
    table_number: string | null;
    is_active: number;
    is_paused: number;
    image_url: string | null;
    created_at: string;
}

export interface Interviewee {
    id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    major: string | null;
    graduation_year: string | null;
    is_paused: number;
    created_at: string;
}

export type InterviewStatus = 'ENQUEUED' | 'CALLING' | 'HAPPENING' | 'COMPLETED' | 'NO_SHOW';

export interface Interview {
    id: number;
    interviewee_id: number;
    interviewer_id: number;
    status: InterviewStatus;
    queue_position: number | null;
    enqueued_at: string;
    called_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    notes: string | null;
}

export interface Session {
    id: string;
    operator_id: number;
    expires_at: string;
    created_at: string;
}

// API Request/Response types
export interface LoginRequest {
    username: string;
    password: string;
}

export interface RegisterStudentRequest {
    first_name: string;
    last_name: string;
    email?: string;
    phone?: string;
    major?: string;
    graduation_year?: string;
}

export interface EnqueueRequest {
    interviewee_id: number;
    interviewer_id: number;
}

export interface ApiResponse<T = unknown> {
    success: boolean;
    data?: T;
    error?: string;
}

// Extended types for API responses
export interface InterviewerStatus {
    id: number;
    name: string;
    table_number: string | null;
    status: 'IDLE' | 'CALLING' | 'INTERVIEWING' | 'PAUSED';
    current_student: string | null;
    queue_length: number;
    is_paused: number;
}

// New request types for Phase 1.1
export interface CreateInterviewerRequest {
    name: string;
    table_number?: string;
}

export interface UpdateInterviewerRequest {
    name?: string;
    table_number?: string;
    is_paused?: boolean;
}

export interface DashboardData {
    interviewers: InterviewerStatus[];
    recent_calls: {
        company: string;
        student: string;
        called_at: string;
    }[];
}

export interface AuthenticatedRequest {
    operator: Operator;
    session: Session;
}
