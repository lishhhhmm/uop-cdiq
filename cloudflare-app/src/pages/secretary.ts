// Secretary Operator Page - Student registration and queue management
export function getSecretaryPageHtml(): string {
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
        <h2>📝 Register Student</h2>
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
        <h2>👥 Registered Students</h2>
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
            <p>\${s.major || 'No major'} • \${s.graduation_year || 'N/A'}</p>
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
          showToast('✅ Student registered!');
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
          showToast('✅ Added to queue (Position #' + data.data.queue_position + ')');
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
  </script>
</body>
</html>`;
}
