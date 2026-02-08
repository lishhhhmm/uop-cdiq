// Main Cloudflare Worker entry point
import type { Env } from './types';
import {
    handleLogin,
    handleLogout,
    handleGetMe,
    handleRegisterStudent,
    handleEnqueueStudent,
    handleGetStudents,
    handleGetInterviewers,
    handleGetQueue,
    handleCallNext,
    handleStartInterview,
    handleCompleteInterview,
    handleNoShow,
    handleDashboard,
    handleCreateInterviewer,
    handleUpdateInterviewer,
    handleDeleteInterviewer,
    handleDeleteStudent,
    handleToggleStudentPause,
    handleToggleInterviewerPause,
    handleGatekeeperCallNext,
    handleGatekeeperAction,
    handleGatekeeperQueue
} from './api';

// Static HTML imports
import { getPublicDashboardHtml } from './pages/dashboard';
import { getLoginPageHtml } from './pages/login';
import { getSecretaryPageHtml } from './pages/secretary';
import { getCompanyPageHtml } from './pages/company';
import { getGatekeeperPageHtml } from './pages/gatekeeper';

export default {
    async fetch(request: Request, env: Env): Promise<Response> {
        const url = new URL(request.url);
        const path = url.pathname;
        const method = request.method;

        // CORS headers for API
        const corsHeaders = {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization'
        };

        // Handle preflight
        if (method === 'OPTIONS') {
            return new Response(null, { headers: corsHeaders });
        }

        try {
            // ============ API ROUTES ============
            if (path.startsWith('/api/')) {
                let response: Response;

                // Auth routes
                if (path === '/api/auth/login' && method === 'POST') {
                    response = await handleLogin(request, env);
                } else if (path === '/api/auth/logout' && method === 'POST') {
                    response = await handleLogout(request, env);
                } else if (path === '/api/auth/me' && method === 'GET') {
                    response = await handleGetMe(request, env);
                }
                // Secretary routes
                else if (path === '/api/students' && method === 'POST') {
                    response = await handleRegisterStudent(request, env);
                } else if (path === '/api/students' && method === 'GET') {
                    response = await handleGetStudents(request, env);
                } else if (path === '/api/queue' && method === 'POST') {
                    response = await handleEnqueueStudent(request, env);
                } else if (path === '/api/queue' && method === 'GET') {
                    response = await handleGetQueue(request, env);
                } else if (path === '/api/interviewers' && method === 'GET') {
                    response = await handleGetInterviewers(request, env);
                }
                // Company routes
                else if (path === '/api/call-next' && method === 'POST') {
                    response = await handleCallNext(request, env);
                } else if (path === '/api/start-interview' && method === 'POST') {
                    response = await handleStartInterview(request, env);
                } else if (path === '/api/complete-interview' && method === 'POST') {
                    response = await handleCompleteInterview(request, env);
                } else if (path === '/api/no-show' && method === 'POST') {
                    response = await handleNoShow(request, env);
                }
                // Public routes
                else if (path === '/api/dashboard' && method === 'GET') {
                    response = await handleDashboard(env);
                }
                // Interviewer CRUD routes (Secretary)
                else if (path === '/api/interviewers' && method === 'POST') {
                    response = await handleCreateInterviewer(request, env);
                } else if (path.startsWith('/api/interviewers') && method === 'PUT') {
                    response = await handleUpdateInterviewer(request, env);
                } else if (path.startsWith('/api/interviewers') && method === 'DELETE') {
                    response = await handleDeleteInterviewer(request, env);
                }
                // Student CRUD routes
                else if (path.startsWith('/api/students') && method === 'DELETE') {
                    response = await handleDeleteStudent(request, env);
                }
                // Pause/Unpause routes
                else if (path === '/api/students/toggle-pause' && method === 'POST') {
                    response = await handleToggleStudentPause(request, env);
                } else if (path === '/api/interviewers/toggle-pause' && method === 'POST') {
                    response = await handleToggleInterviewerPause(request, env);
                }
                // Gatekeeper routes
                else if (path === '/api/gatekeeper/call-next' && method === 'POST') {
                    response = await handleGatekeeperCallNext(request, env);
                } else if (path === '/api/gatekeeper/action' && method === 'POST') {
                    response = await handleGatekeeperAction(request, env);
                } else if (path === '/api/gatekeeper/queue' && method === 'GET') {
                    response = await handleGatekeeperQueue(request, env);
                }
                // 404
                else {
                    response = new Response(JSON.stringify({ success: false, error: 'Not found' }), {
                        status: 404,
                        headers: { 'Content-Type': 'application/json' }
                    });
                }

                // Add CORS headers to response
                const newHeaders = new Headers(response.headers);
                Object.entries(corsHeaders).forEach(([key, value]) => {
                    newHeaders.set(key, value);
                });

                return new Response(response.body, {
                    status: response.status,
                    headers: newHeaders
                });
            }

            // ============ PAGE ROUTES ============
            if (path === '/' || path === '/dashboard') {
                return new Response(getPublicDashboardHtml(), {
                    headers: { 'Content-Type': 'text/html' }
                });
            }

            if (path === '/login') {
                return new Response(getLoginPageHtml(), {
                    headers: { 'Content-Type': 'text/html' }
                });
            }

            if (path === '/secretary') {
                return new Response(getSecretaryPageHtml(), {
                    headers: { 'Content-Type': 'text/html' }
                });
            }

            if (path === '/company') {
                return new Response(getCompanyPageHtml(), {
                    headers: { 'Content-Type': 'text/html' }
                });
            }

            if (path === '/gatekeeper') {
                return new Response(getGatekeeperPageHtml(), {
                    headers: { 'Content-Type': 'text/html' }
                });
            }

            // 404 for unknown routes
            return new Response('Not Found', { status: 404 });

        } catch (error) {
            console.error('Worker error:', error);
            return new Response(JSON.stringify({
                success: false,
                error: 'Internal server error'
            }), {
                status: 500,
                headers: { 'Content-Type': 'application/json' }
            });
        }
    }
};
