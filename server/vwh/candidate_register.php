<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/candidate_auth.php';

// If already logged in, redirect to dashboard
candidate_session_ensure_started();
$existing = candidate_session_get();
if ($existing !== false) {
    header('Location: /candidate_dashboard.php');
    exit;
}

$db = database();

// Get current interviewers and update_id (for optimistic locking)
$dash = $db->candidate_dashboard_view(0);
$update_id       = $dash['update']      ?? 0;
$all_interviewers = $dash['interviewers'] ?? [];

$dev_mode = (CANDIDATE_GOOGLE_CLIENT_ID === 'REPLACE_WITH_YOUR_CLIENT_ID');

// Flash message (e.g. after a failed registration attempt)
$flash = $_SESSION['candidate_flash'] ?? null;
unset($_SESSION['candidate_flash']);

$a = new Assembler('Register');
$a->body_header_title_override = ''; // suppress default h1 — page has its own header

$a->body_main = function () use ($all_interviewers, $update_id, $dev_mode, $flash) { ?>

<main class="candidate-page">
    <div class="candidate-page-inner">

        <!-- ── Page Header ── -->
        <div class="candidate-page-header animate-fade-in">
            <h1 class="gradient-text">Career Fair 2026 · Self Registration</h1>
            <p class="subtitle">Reserve your spot in company interview queues — even before the day starts.</p>
        </div>

        <!-- ── Flash message (errors from failed registration) ── -->
        <?php if ($flash): ?>
        <div class="form-flash form-flash--<?= $flash['type'] === 'error' ? 'error' : 'success' ?> animate-fade-in">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <!-- ── How it works ── -->
        <div class="candidate-card glass animate-fade-in" style="padding: 1.25rem 1.75rem;">
            <div class="reg-info-grid">
                <div class="reg-info-col">
                    <span class="reg-info-icon">📱</span>
                    <strong>Self-Service (this page)</strong>
                    <p>Sign in with your UoP Google account, fill in your profile, pick companies — done. Manage your queues any time from your dashboard.</p>
                </div>
                <div class="reg-info-divider"></div>
                <div class="reg-info-col">
                    <span class="reg-info-icon">🏢</span>
                    <strong>Secretary Desk (always open)</strong>
                    <p>You can still register in person at the Secretary desk on the day of the event. The Secretary can also join, move, or remove you from any queue at any time.</p>
                </div>
            </div>
        </div>

        <!-- ── Sign-In Card ── -->
        <div id="signin-section" class="candidate-card glass animate-fade-in">

            <h2 class="section-title-sm" style="margin-bottom: 0.25rem;">Sign In with UoP Google Account</h2>
            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1.25rem;">
                Only <code>@go.uop.gr</code> student accounts are accepted for online registration.
                Students without a UoP Google account should visit the Secretary desk.
            </p>

            <?php if ($dev_mode): ?>
            <!-- ─── DEV MODE BYPASS (visible only when Google Client ID is not configured) ─── -->
            <div style="border: 2px dashed #f59e0b; border-radius: 10px; padding: 1rem 1.25rem; background: rgba(245,158,11,0.06); margin-bottom: 1.25rem;">
                <p style="font-weight:700; color:#92400e; margin-bottom:0.5rem;">⚠️ Dev Mode — Google Sign-In not configured</p>
                <p style="font-size:0.85rem; color:#92400e; margin-bottom:0.85rem;">
                    Set <code>CANDIDATE_GOOGLE_CLIENT_ID</code> in <code>candidate_auth.php</code> to enable real Google Sign-In.
                    For now, use the form below to test with a fake profile.
                </p>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <input type="text"  id="dev-name"   placeholder="Full name (e.g. Test Student)"
                           style="padding:0.55rem 0.75rem; border-radius:7px; border:1px solid var(--border); background:var(--bg-card); color:var(--text-primary); font-size:0.875rem;" value="Test Student">
                    <input type="email" id="dev-email"  placeholder="@go.uop.gr email"
                           style="padding:0.55rem 0.75rem; border-radius:7px; border:1px solid var(--border); background:var(--bg-card); color:var(--text-primary); font-size:0.875rem;" value="test@go.uop.gr">
                    <input type="hidden" id="dev-sub"   value="dev_sub_12345">
                    <button onclick="activateDevMode()" type="button" class="queue-btn queue-btn--join" style="width:fit-content; padding:0.55rem 1.2rem; border-radius:8px;">
                        Use Dev Profile →
                    </button>
                </div>
            </div>
            <?php else: ?>
            <!-- ─── REAL GOOGLE SIGN-IN ─── -->
            <div id="g_id_onload"
                data-client_id="<?= CANDIDATE_GOOGLE_CLIENT_ID ?>"
                data-callback="handleGoogleSignIn"
                data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                data-type="standard"
                data-shape="pill"
                data-theme="filled_black"
                data-text="signin_with"
                data-size="large"
                data-logo_alignment="left">
            </div>
            <?php endif; ?>

            <div id="signin-error" class="form-error" style="display:none; margin-top:0.75rem;"></div>

            <!-- Secretary hint ─ compact, below sign-in button -->
            <p style="font-size:0.8rem; color:var(--text-secondary); margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border);">
                Don't have a <code>@go.uop.gr</code> account, or prefer to register in person?
                <strong>Head to the Secretary desk</strong> at the event — no account needed.
            </p>
        </div>

        <!-- ── Registration Form (shown after sign-in) ── -->
        <form id="registration-form" class="candidate-card glass animate-fade-in" style="display:none;"
              method="POST" action="/candidate_update.php" enctype="multipart/form-data">
            <input type="hidden" name="action"      value="register">
            <input type="hidden" name="update_id"   value="<?= $update_id ?>">
            <input type="hidden" name="google_token" id="google_token_field" value="">
            <!-- dev-only fields (ignored when real token is present) -->
            <input type="hidden" name="dev_name"    id="dev_name_field"  value="">
            <input type="hidden" name="dev_email"   id="dev_email_field" value="">
            <input type="hidden" name="dev_sub"     id="dev_sub_field"   value="">

            <!-- Profile preview -->
            <div class="user-info" id="user-info-preview">
                <img id="reg-avatar" src="" alt="Photo" class="user-avatar"
                     onerror="this.style.display='none'">
                <div>
                    <p id="reg-name"  class="user-name"></p>
                    <p id="reg-email" class="user-email"></p>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Academic Background</h3>

                <div class="form-group">
                    <label for="dept">Department *</label>
                    <select id="dept" name="dept" required onchange="toggleOtherDept(this.value)">
                        <option value="" disabled selected>Choose your department</option>
                        <option value="Informatics &amp; Telecommunications">Informatics &amp; Telecommunications</option>
                        <option value="Economic Sciences">Economic Sciences</option>
                        <option value="Administrative Science and Technology">Administrative Science and Technology</option>
                        <option value="Digital Systems">Digital Systems</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group" id="other-dept-group" style="display:none;">
                    <label for="other-dept">Specify your department *</label>
                    <input type="text" id="other-dept" name="other_dept" placeholder="Your department name">
                </div>

                <div class="form-group">
                    <label for="masters">Master's Degree <span class="label-optional">(optional)</span></label>
                    <input type="text" id="masters" name="masters" placeholder="e.g. Data Science, Business Administration">
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Career Preferences <span style="font-weight:400; font-size:0.85rem;">(select all that apply)</span></h3>
                <div class="checkbox-grid">
                    <label class="check-chip">
                        <input type="checkbox" name="interests[]" value="Job">
                        <span>💼 Job</span>
                    </label>
                    <label class="check-chip">
                        <input type="checkbox" name="interests[]" value="Internship">
                        <span>🎓 Internship</span>
                    </label>
                    <label class="check-chip">
                        <input type="checkbox" name="interests[]" value="Thesis">
                        <span>📝 Thesis</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">CV <span class="label-optional">(optional)</span></h3>
                <div class="form-group">
                    <label for="cv">Upload your CV as a PDF — companies may view it before the interview</label>
                    <div class="file-drop-zone" id="cv-drop-zone">
                        <input type="file" id="cv" name="cv" accept="application/pdf" class="file-input">
                        <div class="file-drop-label">
                            <span class="file-icon">📄</span>
                            <span id="cv-filename">Click or drag your PDF here</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($all_interviewers)): ?>
            <div class="form-section">
                <h3 class="form-section-title">Choose Companies to Interview With</h3>
                <p class="form-hint">Select the companies you'd like to queue for. You can add or leave queues later from your dashboard.</p>
                <div class="company-picker-grid">
                    <?php foreach ($all_interviewers as $co): ?>
                    <label class="company-pick-card <?= !$co['active'] ? 'inactive' : '' ?>">
                        <input type="checkbox" name="companies[]" value="<?= (int)$co['id'] ?>"
                            <?= !$co['active'] ? 'disabled' : '' ?>>
                        <img src="<?= htmlspecialchars($co['image_resource_url']) ?>"
                             alt="<?= htmlspecialchars($co['name']) ?>" class="company-pick-logo">
                        <span class="company-pick-name"><?= htmlspecialchars($co['name']) ?></span>
                        <?php if (!$co['active']): ?>
                            <span class="company-pick-closed">Closed</span>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div id="reg-error" class="form-error" style="display:none;"></div>

            <button type="submit" class="btn-primary btn-full" id="reg-submit-btn">
                🎯 Complete Registration
            </button>
            <p class="privacy-note">
                You can edit your profile and queues any time from your dashboard after registering.<br>
                The Secretary desk can also modify your queues on request.
            </p>
        </form>

    </div>
</main>

<?php if (!$dev_mode): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>
<script>
function toggleOtherDept(val) {
    document.getElementById('other-dept-group').style.display = (val === 'Other') ? 'block' : 'none';
}

// ── Real Google Sign-In callback ──
function handleGoogleSignIn(response) {
    const token = response.credential;
    const parts = token.split('.');
    const payload = JSON.parse(atob(parts[1]));
    const email = payload.email || '';

    if (!email.endsWith('@go.uop.gr')) {
        const err = document.getElementById('signin-error');
        err.style.display = 'block';
        err.textContent = '⚠️ Only @go.uop.gr accounts can self-register. Please visit the Secretary desk.';
        return;
    }

    // Check if already registered and auto-login
    checkRegisteredAndRedirect(token, null);
}

// ── Dev mode bypass ──
function activateDevMode() {
    const name  = document.getElementById('dev-name').value.trim()  || 'Dev User';
    const email = document.getElementById('dev-email').value.trim() || 'dev@go.uop.gr';
    const sub   = document.getElementById('dev-sub').value;

    if (!email.endsWith('@go.uop.gr')) {
        alert('Dev mode still requires an @go.uop.gr email (for the server-side domain check to pass).');
        return;
    }

    checkRegisteredAndRedirect(null, { name, email, sub });
}

function checkRegisteredAndRedirect(token, devProfile) {
    const formData = new FormData();
    formData.append('action', 'login');
    if (token) formData.append('google_token', token);
    if (devProfile) {
        formData.append('dev_name', devProfile.name);
        formData.append('dev_email', devProfile.email);
        formData.append('dev_sub', devProfile.sub);
    }

    const btn = document.getElementById('reg-submit-btn'); // Use button as loading indicator if needed
    
    fetch('/candidate_update.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.registered) {
            window.location.href = '/candidate_dashboard.php';
        } else {
            // Not registered yet, show the form
            if (token) {
                 const parts = token.split('.');
                 const payload = JSON.parse(atob(parts[1]));
                 document.getElementById('google_token_field').value = token;
                 showProfileAndForm(payload.name || '', payload.email || '', payload.picture || '');
            } else {
                 document.getElementById('dev_name_field').value  = devProfile.name;
                 document.getElementById('dev_email_field').value = devProfile.email;
                 document.getElementById('dev_sub_field').value   = devProfile.sub;
                 showProfileAndForm(devProfile.name, devProfile.email, '');
            }
        }
    })
    .catch(err => {
        console.error("Auto-login error:", err);
        // Fallback to normal flow if something breaks
        if (token) {
             const parts = token.split('.');
             const payload = JSON.parse(atob(parts[1]));
             showProfileAndForm(payload.name || '', payload.email || '', payload.picture || '');
        }
    });
}

function showProfileAndForm(name, email, picture) {
    document.getElementById('reg-avatar').src  = picture;
    document.getElementById('reg-name').textContent  = name;
    document.getElementById('reg-email').textContent = email;

    document.getElementById('signin-section').style.display = 'none';
    document.getElementById('registration-form').style.display = 'block';
    document.getElementById('registration-form').scrollIntoView({ behavior: 'smooth' });
}

// ── CV filename display ──
document.addEventListener('DOMContentLoaded', function () {
    const cvInput = document.getElementById('cv');
    const cvLabel = document.getElementById('cv-filename');
    if (cvInput) {
        cvInput.addEventListener('change', function () {
            cvLabel.textContent = this.files[0] ? this.files[0].name : 'Click or drag your PDF here';
        });
    }

    // Client-side submit validation
    const form = document.getElementById('registration-form');
    form && form.addEventListener('submit', function (e) {
        const dept      = document.getElementById('dept').value;
        const interests = form.querySelectorAll('input[name="interests[]"]:checked');
        const token     = document.getElementById('google_token_field').value;
        const devSub    = document.getElementById('dev_sub_field').value;

        document.getElementById('reg-error').style.display = 'none';

        if (!token && !devSub) { showErr('Please sign in first.'); e.preventDefault(); return; }
        if (!dept)             { showErr('Please select your department.'); e.preventDefault(); return; }
        if (dept === 'Other' && !document.getElementById('other-dept').value.trim()) {
            showErr('Please specify your department.'); e.preventDefault(); return;
        }
        if (interests.length === 0) { showErr('Please select at least one career interest.'); e.preventDefault(); return; }

        document.getElementById('reg-submit-btn').disabled    = true;
        document.getElementById('reg-submit-btn').textContent = 'Registering…';
    });

    function showErr(msg) {
        const b = document.getElementById('reg-error');
        b.textContent   = msg;
        b.style.display = 'block';
        b.scrollIntoView({ behavior: 'smooth' });
    }
});
</script>

<?php };

$a->assemble();
