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
                    <a href="/candidate_logout.php" class="btn-outline-sm danger">Sign Out</a>
                </div>
                <span class="cand-profile-email"><?= htmlspecialchars($candidate['email']) ?></span>
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
                            class="profile-chip cv-chip">📄 View CV</a>
                        <button type="button" id="btn-change-cv" class="profile-chip"
                            style="cursor:pointer; background:var(--surface-secondary); border:1px solid var(--border-subtle); color:var(--text-secondary);">🔄
                            Change CV</button>
                    <?php else: ?>
                        <button type="button" id="btn-upload-cv" class="profile-chip cv-chip" style="cursor:pointer;">📄 Upload
                            CV</button>
                    <?php endif; ?>
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
            style="margin: 0 0 0.5rem;">
            <?= htmlspecialchars($flash['message']) ?>
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

    <script src="/script/utilities.js"></script>
    <script src="/script/short_polling.js"></script>
    <script src="/script/candidate_queues.js"></script>

<?php };

$a->assemble();
