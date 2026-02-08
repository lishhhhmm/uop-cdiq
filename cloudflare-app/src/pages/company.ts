// Company Operator Page - Call students and manage interviews
export function getCompanyPageHtml(): string {
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
          📢 Call Next
        </button>
      </div>
    </div>

    <div class="queue-panel">
      <div class="queue-header">
        <h2>📋 Your Queue</h2>
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
        actions.innerHTML = '<button class="btn btn-primary" onclick="callNext()">📢 Call Next</button>';
      } else if (status === 'CALLING') {
        card.classList.add('calling-animation');
        badge.className = 'status-badge status-calling';
        badge.textContent = 'Now Calling';
        studentEl.textContent = studentName;
        messageEl.textContent = 'Please wait for the student to arrive';
        actions.innerHTML = \`
          <button class="btn btn-success" onclick="startInterview()">✓ Student Arrived</button>
          <button class="btn btn-danger" onclick="markNoShow()">✗ No Show</button>
        \`;
      } else if (status === 'HAPPENING') {
        card.classList.add('interviewing-glow');
        badge.className = 'status-badge status-interviewing';
        badge.textContent = 'Interviewing';
        studentEl.textContent = studentName;
        messageEl.textContent = 'Interview in progress';
        actions.innerHTML = '<button class="btn btn-success" onclick="completeInterview()">✓ Complete Interview</button>';
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
  </script>
</body>
</html>`;
}
