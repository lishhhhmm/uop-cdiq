// Gatekeeper panel page - manages all companies' interview flows
export function getGatekeeperPageHtml(): string {
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
        <h1>🚪 Gatekeeper Panel</h1>
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
                                <div class="status">\${current.status} \${current.major ? '• ' + current.major : ''}</div>
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
                                📢 Call Next
                            </button>
                            <button class="btn btn-start" 
                                onclick="startInterview(\${current?.id})"
                                \${!current || current.status !== 'CALLING' ? 'disabled' : ''}>
                                ✓ Arrived
                            </button>
                            <button class="btn btn-complete" 
                                onclick="completeInterview(\${current?.id})"
                                \${!current || current.status !== 'HAPPENING' ? 'disabled' : ''}>
                                ✓ Complete
                            </button>
                            <button class="btn btn-noshow" 
                                onclick="noShow(\${current?.id})"
                                \${!current ? 'disabled' : ''}>
                                ✗ No Show
                            </button>
                            <button class="btn btn-pause" 
                                onclick="togglePause(\${company.id})"
                                style="grid-column: span 2">
                                \${company.is_paused ? '▶ Unpause' : '⏸ Pause'} Company
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
    </script>
</body>
</html>`;
}
