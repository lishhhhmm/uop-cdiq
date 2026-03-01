<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/candidate_auth.php';

$candidate = candidate_require_auth();
$db = database();
$candidate_row = $db->candidate_by_google_sub($candidate['google_sub']);

if ($candidate_row === false) {
    header('Location: /candidate_register.php');
    exit;
}

$data = $db->candidate_dashboard_view((int) $candidate_row['id']);
$update_id = $data['update'] ?? 0;

$flash = $_SESSION['candidate_flash'] ?? null;
unset($_SESSION['candidate_flash']);

$a = new Assembler('My Queues');
$a->body_header_title_override = '';

$a->body_main = function () use ($candidate, $candidate_row, $update_id, $flash) {
    $reg_id = (int) $candidate_row['id'];
    ?>

    <!-- ── Information Section ── -->
    <dialog id="dialog-info" class="info-dialog" onclick="if(event.target===this)this.close();">
        <button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
        <h3>👋 Welcome to your Dashboard!</h3>
        <p>This is where you manage your interviews for the Career Fair. Here is a quick guide:</p>

        <hr>
        <p><strong>📋 Your Profile</strong></p>
        <ul>
            <li>Your <strong>Registration №</strong> is your unique ID. When a company calls this number, it's time for your
                interview!</li>
            <li>Ensure your <strong>CV is uploaded</strong> so companies can review it.</li>
        </ul>

        <hr>
        <p><strong>🚦 Interview Queues</strong></p>
        <ul>
            <li>Browse the list of companies and click <strong>Join Queue</strong> to enter the waiting line.</li>
            <li>You can stay in multiple queues at once.</li>
            <li>If you change your mind, you can <strong>Leave Queue</strong> anytime (unless you are being called).</li>
        </ul>

        <hr>
        <p><strong>✨ Live Status</strong></p>
        <ul>
            <li>The Company that is currently calling you will be highlighted and will always come to the top of this list.
                All other companies will be in a shadowed state.</li>
            <li><span class='av'>Available</span>: The interviewer is free. Either no candidates are waiting, or the waiting
                candidates are currently in other interviews.</li>
            <li><span class='ca'>Calling</span>: A candidate is being called. If this is your number, go to the Gatekeeper
                to start your interview.</li>
            <li><span class='ha'>Happening</span>: The candidate arrived on time and the interview is in progress.</li>
            <li><span class='de'>Decision</span>: The calling period has ended. The Gatekeeper decides whether the candidate
                arrived on time. If this is your number and you have not arrived, you are considered late (you will be
                removed from the queue).</li>
            <li><span class='pa'>Paused</span>: The interviewer is temporarily unavailable and cannot conduct interviews.
                You can still join this queue.</li>
        </ul>

        <hr>
        <p><strong>⏸️ Pause My Profile</strong></p>
        <ul>
            <li>If you need a break or have to step away, you can <strong>Pause</strong> your profile.</li>
            <li>This will make you unavailable for calls. If you were being called, you will be returned to the waiting
                queue without losing your spot.</li>
            <li>Once back, click <strong>Activate Profile</strong> to continue interviewing.</li>
        </ul>

        <hr>
        <p>The dashboard updates automatically. No need to refresh!</p>
        <button onclick="document.getElementById('dialog-info').close();" style="width:100%; margin-top:1rem;">Got
            it!</button>
    </dialog>

    <div id="info-buttons" class="horizontal_buttons" style="margin-bottom: 1rem;">
        <button type="button"
            onclick="document.getElementById('dialog-info').showModal(); document.getElementById('dialog-info').scrollTo(0,0);">What
            is this place?</button>
        <button type="button" onclick="document.getElementById('info-buttons').style.display = 'none';">Hide</button>
    </div>

    <!-- The candidate dashboard uses the same <main> semantics as queues.php.-->

    <!-- ── Profile + Registration ID ── -->
    <div class="cand-profile-bar glass animate-fade-in">
        <div class="cand-profile-bar__left">
            <?php if (!empty($candidate_row['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($candidate_row['avatar_url']) ?>" alt="Avatar" class="dashboard-avatar">
            <?php endif; ?>
            <div class="cand-profile-bar__info">
                <div class="cand-profile-bar__name-row">
                    <span class="cand-profile-name">
                        <?= htmlspecialchars($candidate_row['display_name'] ?? $candidate['email']) ?>
                    </span>
                    <button type="button" id="btn-toggle-pause" class="btn-outline-sm">
                        <?= $candidate_row['active'] ? '⏸ Pause Profile' : '▶ Activate Profile' ?>
                    </button>
                    <a href="/candidate_logout.php" class="btn-outline-sm danger">Sign Out</a>
                </div>
                <span class="cand-profile-email"><?= htmlspecialchars($candidate['email']) ?></span>
                <?php if (!$candidate_row['active']): ?>
                    <div style="color: #e11d48; font-size: 0.85rem; font-weight: 600; margin-top: 0.2rem;">
                        ⏸ Profile is paused. You will not be called for interviews until you activate.
                    </div>
                <?php endif; ?>
                <div class="profile-chips" style="margin-top:0.45rem;">
                    <?php if ($candidate_row['department']): ?>
                        <span class="profile-chip">🎓 <?= htmlspecialchars($candidate_row['department']) ?></span>
                    <?php endif; ?>
                    <?php if ($candidate_row['masters']): ?>
                        <span class="profile-chip">📖 <?= htmlspecialchars($candidate_row['masters']) ?></span>
                    <?php endif; ?>
                    <?php foreach (explode(',', $candidate_row['interests'] ?? '') as $int):
                        $int = trim($int);
                        if (!$int)
                            continue; ?>
                        <span class="profile-chip"><?= htmlspecialchars($int) ?></span>
                    <?php endforeach; ?>
                    <?php if ($candidate_row['cv_resource_url']): ?>
                        <a href="<?= htmlspecialchars($candidate_row['cv_resource_url']) ?>" id="btn-view-cv"
                            class="btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.85rem;">📄 View CV</a>
                        <button type="button" id="btn-change-cv" class="btn-outline-sm">🔄 Change CV</button>
                    <?php else: ?>
                        <button type="button" id="btn-upload-cv" class="btn-primary"
                            style="padding: 0.25rem 0.5rem; font-size: 0.85rem;">📄 Upload CV</button>
                    <?php endif; ?>
                    <button type="button" id="btn-edit-profile" class="btn-outline-sm">✏️ Edit Profile</button>
                    <input type="file" id="cv-upload-input" accept="application/pdf" style="display:none;">
                </div>
            </div>
        </div>

        <!-- Registration ID -->
        <div class="cand-reg-id">
            <span class="cand-reg-id__label">Registration №</span>
            <span class="cand-reg-id__number">#<?= $reg_id ?></span>
            <span class="cand-reg-id__hint">
                Your number on the <a href="/queues.php">queue screen</a>.
                When called, go to the Gatekeeper.
            </span>
        </div>
    </div>

    <!-- ── Flash ── -->
    <?php if ($flash): ?>
        <div class="form-flash form-flash--<?= $flash['type'] === 'error' ? 'error' : 'success' ?> animate-fade-in"
            style="margin: 0 0 0.5rem; position: relative; padding-right: 2.5rem;">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" onclick="this.parentElement.style.display='none'"
                style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: transparent; color: inherit; border: none; font-size: 1.25rem; cursor: pointer; padding: 0; box-shadow: none; outline: none; line-height: 1;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- ── Live Stats Bar (updated by candidate_queues.js) ── -->
    <div class="cand-stats-bar glass">
        <div class="cand-stat">
            <span class="cand-stat__value" id="stat-queues">—</span>
            <span class="cand-stat__label">queues joined</span>
        </div>
        <div class="cand-stat-divider"></div>
        <div class="cand-stat">
            <span class="cand-stat__value" id="stat-completed">—</span>
            <span class="cand-stat__label">interviews completed</span>
        </div>
    </div>

    <!-- ── Company Queue Grid — same structure as queues.php ── -->
    <div id="container_interviewers" class="container_interviewers">
        <p id="no_interviewers_message">Loading company queues…</p>
    </div>

    <!-- Pass candidate identity to JS -->
    <script>
        window.CANDIDATE_INTERVIEWEE_ID = <?= $reg_id ?>;
        window.CANDIDATE_INITIAL_UPDATE_ID = <?= (int) $update_id ?>;
    </script>

    <!-- CV Preview Modal -->
    <dialog id="dialog_cv_preview" onclick="if(event.target===this)this.close();"
        style="width: 90vw; max-width: 1000px; height: 90vh; border: none; border-radius: 12px; padding: 0; box-shadow: 0 10px 50px rgba(0,0,0,0.4); overflow: hidden; background: #525659;">
        <div
            style="background: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle);">
            <h3 style="margin: 0; font-size: 1.2rem; color: var(--brand-maroon);">Your CV</h3>
            <div style="display: flex; gap: 0.75rem;">
                <a id="btn_cv_external" href="#" target="_blank" class="btn-secondary"
                    style="padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;">Open External ↗</a>
                <button onclick="document.getElementById('dialog_cv_preview').close();"
                    style="background: #eee; border: none; font-size: 1.5rem; color: #666; cursor: pointer; line-height: 1; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
        </div>
        <iframe id="cv_iframe" src="" style="width: 100%; height: calc(100% - 60px); border: none;"></iframe>
    </dialog>

    <!-- Edit Profile Modal -->
    <dialog id="dialog_edit_profile" class="info-dialog" onclick="if(event.target===this)this.close();">
        <button class="dialog-close" type="button" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
        <h3>Edit Profile</h3>
        <form id="edit-profile-form" method="POST" action="/candidate_update.php">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="update_id" value="<?= $update_id ?>">

            <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                <label style="display: flex; flex-direction: column; gap: 0.3rem;">
                    <strong>Department</strong>
                    <select id="edit-dept" name="dept" required
                        onchange="document.getElementById('edit-other-dept-wrap').style.display = (this.value === 'Other') ? 'block' : 'none';"
                        style="padding: 0.5rem;">
                        <?php
                        $depts = [
                            "Informatics & Telecommunications",
                            "Economic Sciences",
                            "Administrative Science and Technology",
                            "Digital Systems",
                            "Other"
                        ];
                        $current_dept = $candidate_row['department'];
                        $is_other = !in_array($current_dept, array_slice($depts, 0, 4)) && !empty($current_dept);
                        foreach ($depts as $d) {
                            $sel = ($d === $current_dept || ($d === 'Other' && $is_other)) ? 'selected' : '';
                            echo "<option value=\"$d\" $sel>$d</option>";
                        }
                        ?>
                    </select>
                </label>

                <label id="edit-other-dept-wrap"
                    style="display: <?= $is_other ? 'flex' : 'none' ?>; flex-direction: column; gap: 0.3rem;">
                    <strong>Specify your department</strong>
                    <input type="text" name="other_dept" value="<?= htmlspecialchars($is_other ? $current_dept : '') ?>"
                        style="padding: 0.5rem;">
                </label>

                <label style="display: flex; flex-direction: column; gap: 0.3rem;">
                    <strong>Master's Degree <span style="font-weight:400; font-size:0.85rem;">(optional)</span></strong>
                    <input type="text" name="masters" value="<?= htmlspecialchars($candidate_row['masters'] ?? '') ?>"
                        style="padding: 0.5rem;" placeholder="e.g. Data Science, Business Administration">
                </label>

                <div>
                    <strong>Career Preferences</strong>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                        <?php
                        $ints = explode(',', $candidate_row['interests'] ?? '');
                        $ints = array_map('trim', $ints);
                        $opts = ['Job' => '💼 Job', 'Internship' => '🎓 Internship', 'Thesis' => '📝 Thesis'];
                        foreach ($opts as $val => $label) {
                            $checked = in_array($val, $ints) ? 'checked' : '';
                            echo "<label style='display:flex; align-items:center; gap:0.3rem;'>
                                        <input type='checkbox' name='interests[]' value='$val' $checked> $label
                                      </label>";
                        }
                        ?>
                    </div>
                </div>

                <!-- CV section (uses the existing JS upload flow) -->
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <strong>CV <span style="font-weight:400; font-size:0.85rem;">(optional, PDF only, max 1
                            MB)</span></strong>
                    <?php if (!empty($candidate_row['cv_resource_url'])): ?>
                        <p style="font-size:0.85rem; color:var(--text-secondary); margin:0;">
                            Current: <a href="<?= htmlspecialchars($candidate_row['cv_resource_url']) ?>" target="_blank"
                                style="color:var(--brand-maroon);">View current CV</a>
                        </p>
                    <?php else: ?>
                        <p style="font-size:0.85rem; color:var(--text-secondary); margin:0;">No CV uploaded yet.</p>
                    <?php endif; ?>
                    <input type="file" id="cv-upload-input-profile" accept="application/pdf" style="padding: 0.4rem 0;">
                    <p id="cv-upload-profile-status" style="font-size:0.8rem; color:var(--text-secondary); margin:0;"></p>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 1rem;">Save Profile</button>
            </div>
        </form>
    </dialog>

    <script src="/script/utilities.js?cv=<?= date("YmdHi") ?>"></script>
    <script src="/script/short_polling.js?cv=<?= date("YmdHi") ?>"></script>
    <script src="/script/candidate_queues.js?cv=<?= date("YmdHi") ?>"></script>

<?php };

$a->assemble();
