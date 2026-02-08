// Public Dashboard Page - Real-time view of company statuses
export function getPublicDashboardHtml(): string {
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
        <span>Live • Auto-refreshing every 3s</span>
      </div>
    </header>

    <div id="dashboard" class="grid">
      <div class="empty-state">
        <p>Loading...</p>
      </div>
    </div>

    <div id="recent-calls" class="recent-calls" style="display: none;">
      <h2>📢 Recent Calls</h2>
      <div class="call-list" id="call-list"></div>
    </div>
  </div>

  <a href="/login" class="login-link">Operator Login →</a>

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
  </script>
</body>
</html>`;
}
