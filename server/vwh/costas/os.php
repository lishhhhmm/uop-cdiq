<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Superadmin');

$a->custom_nav = [
    'Home' => '/',
    'Secretary' => '/costas/secretary.php',
    'Gatekeeper' => '/costas/gatekeeper.php',
    'Logout' => 'javascript:void(0)'
];

// ── SESSION ─────────────────────────────────────────────────────────────
$is_authed = $_SESSION['superadmin_auth'] ?? false;

// Stricter requirement: If we are viewing the page (GET), 
// we must have just logged in via the API (which sets this flag).
// This forces a login screen on every F5 / page reload.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $is_authed) {
    if (empty($_SESSION['superadmin_just_logged_in'])) {
        $is_authed = false;
        unset($_SESSION['superadmin_auth']);
    } else {
        // One-time flag: consume it for this page load
        unset($_SESSION['superadmin_just_logged_in']);
    }
}

$a->body_main = function () use ($is_authed) { ?>

    <!-- ── LOGIN SCREEN ──────────────────────────────────────────────────── -->
    <div id="login-screen" class="superadmin-login-screen" style="display: <?= $is_authed ? 'none' : 'flex' ?>;">
        <div class="superadmin-login-card">
            <h2>🔒 Superadmin Login</h2>
            <form id="login-form" onsubmit="doLogin(event)">
                <input type="password" id="login-password" placeholder="Enter password..." required>
                <button type="submit" class="btn-primary">Login</button>
                <p id="login-error"></p>
            </form>
        </div>
    </div>

    <!-- ── ADMIN DASHBOARD ───────────────────────────────────────────────── -->
    <div id="admin-dashboard" style="display: <?= $is_authed ? 'block' : 'none' ?>;">

        <!-- Tabs -->
        <div class="superadmin-tabs">
            <button class="tab-btn active" onclick="switchTab('companies', this)">🏢 Companies</button>
            <button class="tab-btn" onclick="switchTab('candidates', this)">👤 Candidates</button>
            <button class="tab-btn" onclick="switchTab('operators', this)">👥 Operators</button>
            <button class="tab-btn style-test-btn" onclick="switchTab('testdata', this)">🧪 Test Data</button>
            <button onclick="showChangePassword()" class="btn-secondary-sm">🔑 Password</button>
            <button onclick="doLogout()" class="btn-danger-sm">Logout</button>
        </div>

        <!-- ── Companies Tab ─────────────────────────────────────────────── -->
        <div id="tab-companies">
            <div class="superadmin-controls">
                <input type="text" id="filter-companies" placeholder="Search companies..."
                    oninput="filterTable('companies')" class="search-input">
                <div class="control-actions">
                    <button onclick="showAddCompany()" class="btn-success-sm">+ Add Company</button>
                    <button onclick="confirmDeleteAll('companies')" class="btn-danger-sm">🗑 Delete All</button>
                </div>
            </div>

            <div class="superadmin-table-container">
                <table id="table-companies" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                            <th style="padding: 0.75rem; text-align: left;">ID</th>
                            <th style="padding: 0.75rem; text-align: left;">Logo</th>
                            <th style="padding: 0.75rem; text-align: left;">Name</th>
                            <th style="padding: 0.75rem; text-align: left;">Table</th>
                            <th style="padding: 0.75rem; text-align: center;">Active</th>
                            <th style="padding: 0.75rem; text-align: center;">Queue</th>
                            <th style="padding: 0.75rem; text-align: center;">Done</th>
                            <th style="padding: 0.75rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-companies">
                        <tr>
                            <td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p id="companies-count"
                style="text-align: right; font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;"></p>
        </div>

        <!-- ── Candidates Tab ────────────────────────────────────────────── -->
        <div id="tab-candidates" style="display: none;">
            <div class="superadmin-controls">
                <input type="text" id="filter-candidates" placeholder="Search candidates..."
                    oninput="filterTable('candidates')" class="search-input">
                <div class="control-actions">
                    <button onclick="showAddCandidate()" class="btn-success-sm">+ Add Candidate</button>
                    <button onclick="confirmDeleteAll('candidates')" class="btn-danger-sm">🗑 Delete All</button>
                </div>
            </div>

            <div class="superadmin-table-container">
                <table id="table-candidates" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                            <th style="padding: 0.75rem; text-align: left;">ID</th>
                            <th style="padding: 0.75rem; text-align: left;">Email</th>
                            <th style="padding: 0.75rem; text-align: left;">Name</th>
                            <th style="padding: 0.75rem; text-align: left;">Department</th>
                            <th style="padding: 0.75rem; text-align: center;">Active</th>
                            <th style="padding: 0.75rem; text-align: center;">Queue</th>
                            <th style="padding: 0.75rem; text-align: center;">Done</th>
                            <th style="padding: 0.75rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-candidates">
                        <tr>
                            <td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p id="candidates-count"
                style="text-align: right; font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;"></p>
        </div>

        <!-- ── Operators Tab ─────────────────────────────────────────────── -->
        <div id="tab-operators" style="display: none;">
            <div
                style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid var(--border);">
                <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">
                    Manage access credentials for Secretary and Gatekeeper roles.
                </p>
            </div>

            <div class="superadmin-table-container">
                <table id="table-operators" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                            <th style="padding: 0.75rem; text-align: left;">ID</th>
                            <th style="padding: 0.75rem; text-align: left;">Role</th>
                            <th style="padding: 0.75rem; text-align: left;">Reminder / Label</th>
                            <th style="padding: 0.75rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-operators">
                        <tr>
                            <td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Test Data Tab ─────────────────────────────────────────────── -->
        <div id="tab-testdata" style="display: none;">
            <div
                style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); max-width: 600px; margin: 0 auto;">
                <h3 style="margin-top: 0; color: var(--brand-orange);">Generate Sandbox Data</h3>
                <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.95rem;">
                    Quickly populate the database with realistic mock data to test queue functionality, load times, and
                    visual layouts.
                    <br><strong style="color: var(--accent-danger);">Warning: Do not use on a live production
                        instance!</strong>
                </p>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <button class="test-action-btn" onclick="generateTestData('generate_test_companies')">
                        <span>🏢 Generate 20 Random Companies</span>
                        <span class="test-action-sub">Mock Names & Tables</span>
                    </button>

                    <button class="test-action-btn" onclick="generateTestData('generate_test_candidates')">
                        <span>👤 Generate 50 Random Candidates</span>
                        <span class="test-action-sub">Full Profiles & Mock Emails</span>
                    </button>

                    <button class="test-action-btn" onclick="generateTestData('generate_test_queues')">
                        <span>🎲 Randomly Assign Candidates to Queues</span>
                        <span class="test-action-sub">1-4 Queues / Candidate</span>
                    </button>
                </div>
            </div>
        </div>

    </div><!-- /admin-dashboard -->

    <!-- ── DIALOGS ────────────────────────────────────────────────────────── -->

    <!-- Add/Edit Company Dialog -->
    <dialog id="dialog-company" onclick="if(event.target===this)this.close();"
        style="min-width: min(500px, 90vw); border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary);">
        <button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
        <h3 id="dialog-company-title" style="margin-top: 0;">Add Company</h3>
        <form id="form-company" onsubmit="submitCompany(event)" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" id="company-id" value="">
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span>Company Name *</span>
                <input type="text" id="company-name" required
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary);">
            </label>
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span>Table Number</span>
                <input type="text" id="company-table"
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary);">
            </label>
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span>Logo Image</span>
                <input type="file" id="company-image" accept="image/*">
            </label>
            <button type="submit"
                style="padding: 0.75rem; border-radius: var(--radius-md); background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%); color: #fff; font-weight: 600; border: none; cursor: pointer;">Save</button>
        </form>
    </dialog>

    <!-- Add Candidate Dialog -->
    <dialog id="dialog-candidate" onclick="if(event.target===this)this.close();"
        style="min-width: min(400px, 90vw); border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary);">
        <button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
        <h3 style="margin-top: 0;">Add Candidate</h3>
        <form id="form-candidate" onsubmit="submitCandidate(event)"
            style="display: flex; flex-direction: column; gap: 1rem;">
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span>Email Address *</span>
                <input type="email" id="candidate-email" required
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary);">
            </label>
            <button type="submit"
                style="padding: 0.75rem; border-radius: var(--radius-md); background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%); color: #fff; font-weight: 600; border: none; cursor: pointer;">Add
                Candidate</button>
        </form>
    </dialog>

    <!-- Job Positions Dialog -->
    <dialog id="dialog-jobs" onclick="if(event.target===this)this.close();"
        style="min-width: min(600px, 95vw); max-height: 80vh; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary); overflow-y: auto;">
        <button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
        <h3 id="dialog-jobs-title" style="margin-top: 0;">📋 Job Positions</h3>

        <div id="jobs-list" style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem;"></div>

        <div
            style="background: var(--bg-secondary); border-radius: var(--radius-md); padding: 1rem; border: 1px solid var(--border);">
            <h4 style="margin: 0 0 0.75rem 0; color: var(--text-secondary);">Add New Position</h4>
            <form id="form-add-job" onsubmit="submitJob(event)"
                style="display: flex; flex-direction: column; gap: 0.75rem;">
                <input type="hidden" id="job-interviewer-id" value="">
                <input type="text" id="job-title" placeholder="Position title..." required
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary);">
                <textarea id="job-description" placeholder="Position description..." rows="3" required
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary); resize: vertical;"></textarea>
                <button type="submit"
                    style="padding: 0.5rem 1rem; border-radius: var(--radius-md); background: var(--color-status--available, #22c55e); color: #fff; border: none; font-weight: 600; cursor: pointer;">
                    + Add Position
                </button>
            </form>
        </div>
    </dialog>

    <!-- Change Password Dialog -->
    <dialog id="dialog-password" onclick="if(event.target===this)this.close();"
        style="min-width: min(400px, 90vw); border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary);">
        <button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
        <h3 style="margin-top: 0;">🔑 Change Password</h3>
        <form id="form-password" onsubmit="submitPassword(event)" style="display: flex; flex-direction: column; gap: 1rem;">
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span>Current Password</span>
                <input type="password" id="pw-current" required
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary);">
            </label>
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span>New Password (min 6 chars)</span>
                <input type="password" id="pw-new" required minlength="6"
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary);">
            </label>
            <button type="submit"
                style="padding: 0.75rem; border-radius: var(--radius-md); background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%); color: #fff; font-weight: 600; border: none; cursor: pointer; box-shadow: var(--shadow-sm);">Change
                Password</button>
        </form>
    </dialog>

    <!-- Change Operator Password Dialog -->
    <dialog id="dialog-op-password" onclick="if(event.target===this)this.close();"
        style="min-width: min(400px, 90vw); border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary);">
        <button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
        <h3 id="op-password-title" style="margin-top: 0;">👥 Change Operator Password</h3>
        <form id="form-op-password" onsubmit="submitOpPassword(event)"
            style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" id="op-pw-id" value="">
            <label style="display: flex; flex-direction: column; gap: 0.25rem;">
                <span>New Password (min 6 chars)</span>
                <input type="password" id="op-pw-new" required minlength="6"
                    style="padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary);">
            </label>
            <button type="submit"
                style="padding: 0.75rem; border-radius: var(--radius-md); background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%); color: #fff; font-weight: 600; border: none; cursor: pointer; box-shadow: var(--shadow-sm);">Update
                Password</button>
        </form>
    </dialog>

    <!-- Confirm Dialog -->
    <dialog id="dialog-confirm" onclick="if(event.target===this)this.close();"
        style="min-width: min(400px, 90vw); border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg-primary); color: var(--text-primary);">
        <div style="display: flex; flex-direction: column; gap: 1rem; text-align: center;">
            <p id="confirm-message" style="font-weight: 600; margin: 0;"></p>
            <p id="confirm-warning" style="color: var(--accent-danger, #ef4444); margin: 0; font-size: 0.9rem;"></p>
            <div style="display: flex; gap: 0.5rem;">
                <button id="confirm-yes"
                    style="flex: 1; padding: 0.75rem; border-radius: var(--radius-md); background: var(--accent-danger, #ef4444); color: #fff; font-weight: 600; border: none; cursor: pointer;">
                    Yes, Delete
                </button>
                <button onclick="document.getElementById('dialog-confirm').close();"
                    style="flex: 1; padding: 0.75rem; border-radius: var(--radius-md); background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border); cursor: pointer;">
                    Cancel
                </button>
            </div>
        </div>
    </dialog>

    <!-- Toast Notification -->
    <div id="toast"
        style="display: none; position: fixed; bottom: 2rem; right: 2rem; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); color: #fff; font-weight: 600; z-index: 9999; transition: opacity 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
    </div>

<?php };

$a->assemble(); ?>

<style>
    .superadmin-tabs .style-test-btn {
        background: rgba(245, 158, 11, 0.1);
        color: var(--brand-orange);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .superadmin-tabs .style-test-btn.active {
        background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
        color: #fff;
        border-color: transparent;
    }

    .test-action-btn {
        padding: 1rem;
        border-radius: var(--radius-md);
        background: #f8fafc !important;
        border: 1px solid var(--border) !important;
        color: #1e293b !important;
        cursor: pointer;
        text-align: left;
        font-size: 1rem;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
        box-shadow: none !important;
    }

    .test-action-btn>span:first-child {
        color: #1e293b !important;
    }

    .test-action-btn:hover {
        background: #e2e8f0 !important;
        border-color: #cbd5e1 !important;
    }

    .test-action-btn:active {
        background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%) !important;
        border-color: transparent !important;
    }

    .test-action-btn:active>span:first-child,
    .test-action-btn:active .test-action-sub {
        color: #ffffff !important;
    }

    .test-action-sub {
        font-size: 0.85rem;
        color: #64748b !important;
        font-weight: 400;
    }

    table tbody tr:hover {
        background: var(--bg-secondary);
    }

    table tbody tr {
        border-bottom: 1px solid var(--border);
        transition: background 0.15s;
    }

    table th {
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .btn-action {
        padding: 0.35rem 0.6rem;
        border-radius: var(--radius-sm, 4px);
        border: 1px solid var(--border);
        background: var(--bg-secondary);
        color: var(--text-primary);
        cursor: pointer;
        font-size: 0.8rem;
        transition: background 0.15s;
    }

    .btn-action:hover {
        background: var(--bg-primary);
    }

    .btn-danger {
        background: var(--accent-danger, #ef4444);
        color: #fff;
        border-color: transparent;
    }

    .btn-danger:hover {
        opacity: 0.85;
        background: var(--accent-danger, #ef4444);
    }

    .badge-active {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-yes {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
    }

    .badge-no {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    dialog {
        padding: 1.5rem;
    }

    dialog::backdrop {
        background: rgba(0, 0, 0, 0.6);
    }

    .job-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 0.75rem 1rem;
    }

    .job-card .job-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.25rem;
    }

    .job-card .job-title {
        font-weight: 600;
    }

    .job-card .job-tag {
        font-size: 0.75rem;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: rgba(99, 102, 241, 0.15);
        color: #818cf8;
    }

    .job-card .job-desc {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin: 0.25rem 0 0.5rem;
        white-space: pre-wrap;
    }
</style>

<script>
    const API = '/costas/os_api.php';
    let companiesData = [];
    let candidatesData = [];

    // ── Utility ──────────────────────────────────────────────────────────────
    function toast(msg, isError = false) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.style.background = isError ? 'var(--accent-danger, #ef4444)' : 'var(--color-status--available, #22c55e)';
        el.style.display = 'block';
        el.style.opacity = '1';
        setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.style.display = 'none', 300); }, 3000);
    }

    async function apiCall(data, isForm = false) {
        const opts = { method: 'POST' };
        if (isForm) {
            opts.body = data;
        } else {
            const fd = new FormData();
            for (const [k, v] of Object.entries(data)) fd.append(k, v);
            opts.body = fd;
        }
        const res = await fetch(API, opts);
        return res.json();
    }

    // ── Auth ─────────────────────────────────────────────────────────────────
    async function doLogin(e) {
        e.preventDefault();
        const pw = document.getElementById('login-password').value;
        const res = await apiCall({ superadmin_login: '1', password: pw });
        if (res.ok) {
            location.reload();
        } else {
            const err = document.getElementById('login-error');
            err.textContent = res.error;
            err.style.display = 'block';
        }
    }

    async function doLogout() {
        await apiCall({ superadmin_logout: '1' });
        location.reload();
    }

    // ── Tabs ─────────────────────────────────────────────────────────────────
    function switchTab(tab, btn) {
        document.getElementById('tab-companies').style.display = tab === 'companies' ? 'block' : 'none';
        document.getElementById('tab-candidates').style.display = tab === 'candidates' ? 'block' : 'none';
        document.getElementById('tab-operators').style.display = tab === 'operators' ? 'block' : 'none';
        document.getElementById('tab-testdata').style.display = tab === 'testdata' ? 'block' : 'none';

        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        if (tab === 'companies') loadCompanies();
        else if (tab === 'candidates') loadCandidates();
        else if (tab === 'operators') loadOperators();
    }

    // ── Companies ────────────────────────────────────────────────────────────
    async function loadCompanies() {
        const res = await apiCall({ action: 'list_companies' });
        if (!res.ok) { toast(res.error, true); return; }
        companiesData = res.companies;
        renderCompanies(companiesData);
    }

    function renderCompanies(list) {
        const tbody = document.getElementById('tbody-companies');
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No companies found.</td></tr>';
            document.getElementById('companies-count').textContent = '0 companies';
            return;
        }

        tbody.innerHTML = list.map(c => `
        <tr data-id="${c.id}">
            <td style="padding: 0.5rem 0.75rem;">${c.id}</td>
            <td style="padding: 0.5rem 0.75rem;">
                <img src="${c.image_resource_url}" alt="" style="width: 32px; height: 32px; object-fit: contain; border-radius: 4px; background: var(--bg-secondary);">
            </td>
            <td style="padding: 0.5rem 0.75rem; font-weight: 600;">${esc(c.name)}</td>
            <td style="padding: 0.5rem 0.75rem;">${esc(c.table_number || '-')}</td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">
                <span class="badge-active ${c.active ? 'badge-yes' : 'badge-no'}">${c.active ? 'Yes' : 'No'}</span>
            </td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">${c.queue_count}</td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">${c.completed_count}</td>
            <td style="padding: 0.5rem 0.75rem; text-align: center; white-space: nowrap;">
                <button class="btn-action" onclick="showJobPositions(${c.id}, '${esc(c.name)}')" title="Job Positions">📋</button>
                <button class="btn-action" onclick="showEditCompany(${c.id})">✏️ Edit</button>
                <button class="btn-action btn-danger" onclick="confirmDeleteOne('company', ${c.id}, '${esc(c.name)}')">🗑</button>
            </td>
        </tr>
    `).join('');

        document.getElementById('companies-count').textContent = `${list.length} companies`;
    }

    // ── Candidates ───────────────────────────────────────────────────────────
    async function loadCandidates() {
        const res = await apiCall({ action: 'list_candidates' });
        if (!res.ok) { toast(res.error, true); return; }
        candidatesData = res.candidates;
        renderCandidates(candidatesData);
    }

    function renderCandidates(list) {
        const tbody = document.getElementById('tbody-candidates');
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No candidates found.</td></tr>';
            document.getElementById('candidates-count').textContent = '0 candidates';
            return;
        }

        tbody.innerHTML = list.map(c => `
        <tr data-id="${c.id}">
            <td style="padding: 0.5rem 0.75rem;">${c.id}</td>
            <td style="padding: 0.5rem 0.75rem;">${esc(c.email)}</td>
            <td style="padding: 0.5rem 0.75rem;">${esc(c.display_name || '-')}</td>
            <td style="padding: 0.5rem 0.75rem;">${esc(c.department || '-')}</td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">
                <span class="badge-active ${c.active ? 'badge-yes' : 'badge-no'}" style="cursor: pointer;" onclick="toggleCandidate(${c.id})" title="Click to toggle">
                    ${c.active ? 'Active' : 'Paused'}
                </span>
            </td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">${c.queue_count}</td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">${c.completed_count}</td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">
                <button class="btn-action btn-danger" onclick="confirmDeleteOne('candidate', ${c.id}, '${esc(c.email)}')">🗑</button>
            </td>
        </tr>
    `).join('');

        document.getElementById('candidates-count').textContent = `${list.length} candidates`;
    }

    // ── Operators ────────────────────────────────────────────────────────────
    async function loadOperators() {
        const res = await apiCall({ action: 'list_operators' });
        if (!res.ok) { toast(res.error, true); return; }
        renderOperators(res.operators);
    }

    function renderOperators(list) {
        const tbody = document.getElementById('tbody-operators');
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No operators found. Use install_db.php to initialize.</td></tr>';
            return;
        }

        tbody.innerHTML = list.map(op => `
        <tr>
            <td style="padding: 0.5rem 0.75rem;">${op.id}</td>
            <td style="padding: 0.5rem 0.75rem;"><strong style="text-transform: capitalize;">${esc(op.type)}</strong></td>
            <td style="padding: 0.5rem 0.75rem; color: var(--text-secondary);">${esc(op.reminder || '-')}</td>
            <td style="padding: 0.5rem 0.75rem; text-align: center;">
                <button class="btn-action" onclick="showChangeOpPassword(${op.id}, '${esc(op.type)}')">🔑 Change Password</button>
            </td>
        </tr>
    `).join('');
    }

    function showChangeOpPassword(id, type) {
        document.getElementById('op-password-title').textContent = `👥 Change ${type.charAt(0).toUpperCase() + type.slice(1)} Password`;
        document.getElementById('op-pw-id').value = id;
        document.getElementById('op-pw-new').value = '';
        document.getElementById('dialog-op-password').showModal();
    }

    async function submitOpPassword(e) {
        e.preventDefault();
        const id = document.getElementById('op-pw-id').value;
        const password = document.getElementById('op-pw-new').value;

        const res = await apiCall({ action: 'update_operator_password', id: id, password: password });
        document.getElementById('dialog-op-password').close();
        if (res.ok) toast('Operator password updated!');
        else toast(res.error, true);
    }

    // ── Filter ───────────────────────────────────────────────────────────────
    function filterTable(type) {
        const query = document.getElementById(`filter-${type}`).value.toLowerCase();
        if (type === 'companies') {
            const filtered = companiesData.filter(c =>
                c.name.toLowerCase().includes(query) ||
                (c.table_number || '').toLowerCase().includes(query) ||
                String(c.id).includes(query)
            );
            renderCompanies(filtered);
        } else {
            const filtered = candidatesData.filter(c =>
                c.email.toLowerCase().includes(query) ||
                (c.display_name || '').toLowerCase().includes(query) ||
                (c.department || '').toLowerCase().includes(query) ||
                String(c.id).includes(query)
            );
            renderCandidates(filtered);
        }
    }

    // ── Add / Edit Company ───────────────────────────────────────────────────
    function showAddCompany() {
        document.getElementById('dialog-company-title').textContent = 'Add Company';
        document.getElementById('company-id').value = '';
        document.getElementById('company-name').value = '';
        document.getElementById('company-table').value = '';
        document.getElementById('company-image').value = '';
        document.getElementById('dialog-company').showModal();
    }

    function showEditCompany(id) {
        const c = companiesData.find(x => x.id == id);
        if (!c) return;
        document.getElementById('dialog-company-title').textContent = 'Edit Company';
        document.getElementById('company-id').value = c.id;
        document.getElementById('company-name').value = c.name;
        document.getElementById('company-table').value = c.table_number || '';
        document.getElementById('company-image').value = '';
        document.getElementById('dialog-company').showModal();
    }

    async function submitCompany(e) {
        e.preventDefault();
        const id = document.getElementById('company-id').value;
        const fd = new FormData();
        fd.append('action', id ? 'edit_company' : 'add_company');
        if (id) fd.append('id', id);
        fd.append('name', document.getElementById('company-name').value);
        fd.append('table', document.getElementById('company-table').value);
        const imageInput = document.getElementById('company-image');
        if (imageInput.files.length > 0) fd.append('image', imageInput.files[0]);

        const res = await apiCall(fd, true);
        document.getElementById('dialog-company').close();
        if (res.ok) {
            toast(id ? 'Company updated!' : 'Company added!');
            loadCompanies();
        } else {
            toast(res.error, true);
        }
    }

    // ── Add Candidate ────────────────────────────────────────────────────────
    function showAddCandidate() {
        document.getElementById('candidate-email').value = '';
        document.getElementById('dialog-candidate').showModal();
    }

    async function submitCandidate(e) {
        e.preventDefault();
        const email = document.getElementById('candidate-email').value;
        const res = await apiCall({ action: 'add_candidate', email: email });
        document.getElementById('dialog-candidate').close();
        if (res.ok) {
            toast('Candidate added!');
            loadCandidates();
        } else {
            toast(res.error, true);
        }
    }

    // ── Toggle Candidate ─────────────────────────────────────────────────────
    async function toggleCandidate(id) {
        const res = await apiCall({ action: 'toggle_candidate', id: id });
        if (res.ok) {
            toast('Status toggled!');
            loadCandidates();
        } else {
            toast(res.error, true);
        }
    }

    // ── Job Positions ────────────────────────────────────────────────────────
    let currentJobsIwerId = 0;

    async function showJobPositions(iwerId, companyName) {
        currentJobsIwerId = iwerId;
        document.getElementById('dialog-jobs-title').textContent = `📋 Job Positions — ${companyName}`;
        document.getElementById('job-interviewer-id').value = iwerId;
        document.getElementById('job-title').value = '';
        document.getElementById('job-description').value = '';
        document.getElementById('jobs-list').innerHTML = '<p style="text-align:center; color: var(--text-secondary);">Loading...</p>';
        document.getElementById('dialog-jobs').showModal();
        await loadJobs(iwerId);
    }

    async function loadJobs(iwerId) {
        const res = await apiCall({ action: 'list_jobs', interviewer_id: iwerId });
        const container = document.getElementById('jobs-list');

        if (!res.ok) {
            container.innerHTML = `<p style="color: var(--accent-danger);">${esc(res.error)}</p>`;
            return;
        }

        const jobs = res.jobs || [];
        if (jobs.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No job positions yet.</p>';
            return;
        }

        container.innerHTML = jobs.map(j => `
            <div class="job-card">
                <div class="job-header">
                    <span class="job-title">${esc(j.title)}</span>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        ${j.tag ? `<span class="job-tag">${esc(j.tag)}</span>` : '<span class="job-tag" style="opacity:0.5;">untagged</span>'}
                        <button class="btn-action btn-danger" onclick="deleteJob(${j.id})" style="padding: 0.2rem 0.5rem;">🗑</button>
                    </div>
                </div>
                <div class="job-desc">${esc(j.description)}</div>
            </div>
        `).join('');
    }

    async function submitJob(e) {
        e.preventDefault();
        const iwerId = document.getElementById('job-interviewer-id').value;
        const title = document.getElementById('job-title').value;
        const description = document.getElementById('job-description').value;

        const res = await apiCall({ action: 'add_job', interviewer_id: iwerId, title: title, description: description });
        if (res.ok) {
            toast('Job position added!');
            document.getElementById('job-title').value = '';
            document.getElementById('job-description').value = '';
            await loadJobs(iwerId);
        } else {
            toast(res.error, true);
        }
    }

    async function deleteJob(jobId) {
        if (!confirm('Delete this job position?')) return;
        const res = await apiCall({ action: 'delete_job', job_id: jobId });
        if (res.ok) {
            toast('Job position deleted!');
            await loadJobs(currentJobsIwerId);
        } else {
            toast(res.error, true);
        }
    }

    // ── Delete One ───────────────────────────────────────────────────────────
    function confirmDeleteOne(type, id, name) {
        const dlg = document.getElementById('dialog-confirm');
        document.getElementById('confirm-message').textContent = `Delete ${type}: "${name}"?`;
        document.getElementById('confirm-warning').textContent = 'This will permanently remove all their data including interviews.';
        document.getElementById('confirm-yes').onclick = async () => {
            dlg.close();
            const res = await apiCall({ action: `delete_${type}`, id: id });
            if (res.ok) {
                toast(`${type.charAt(0).toUpperCase() + type.slice(1)} deleted!`);
                type === 'company' ? loadCompanies() : loadCandidates();
            } else {
                toast(res.error, true);
            }
        };
        dlg.showModal();
    }

    // ── Delete All ───────────────────────────────────────────────────────────
    function confirmDeleteAll(type) {
        const label = type === 'companies' ? 'ALL companies' : 'ALL candidates';
        const dlg = document.getElementById('dialog-confirm');
        document.getElementById('confirm-message').textContent = `⚠️ Delete ${label}?`;
        document.getElementById('confirm-warning').textContent = 'This action is IRREVERSIBLE. All data, interviews, and uploaded files will be permanently deleted.';
        document.getElementById('confirm-yes').onclick = () => {
            dlg.close();
            if (confirm(`FINAL CONFIRMATION: Are you absolutely sure you want to delete ${label}? This cannot be undone.`)) {
                doDeleteAll(type);
            }
        };
        dlg.showModal();
    }

    async function doDeleteAll(type) {
        const res = await apiCall({ action: `delete_all_${type}` });
        if (res.ok) {
            toast(`All ${type} deleted!`);
            type === 'companies' ? loadCompanies() : loadCandidates();
        } else {
            toast(res.error, true);
        }
    }

    // ── Change Password ──────────────────────────────────────────────────────
    function showChangePassword() {
        document.getElementById('pw-current').value = '';
        document.getElementById('pw-new').value = '';
        document.getElementById('dialog-password').showModal();
    }

    // ── Test Data ────────────────────────────────────────────────────────────
    async function generateTestData(actionType) {
        if (!confirm('Are you sure you want to generate this test data?')) return;

        toast('Generating data, please wait...', false);
        const res = await apiCall({ action: actionType });
        if (res.ok) {
            toast(res.message);
        } else {
            toast(res.error, true);
        }
    }

    async function submitPassword(e) {
        e.preventDefault();
        const res = await apiCall({
            action: 'change_password',
            current_password: document.getElementById('pw-current').value,
            new_password: document.getElementById('pw-new').value
        });
        document.getElementById('dialog-password').close();
        if (res.ok) toast('Password changed!');
        else toast(res.error, true);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ── Init ─────────────────────────────────────────────────────────────────
    if (document.getElementById('admin-dashboard').style.display !== 'none') {
        loadCompanies();
    }

    // Wire nav Logout link
    document.querySelectorAll('.nav-links a, nav a').forEach(a => {
        if (a.textContent.trim() === 'Logout') {
            a.addEventListener('click', (e) => { e.preventDefault(); doLogout(); });
        }
    });
</script>