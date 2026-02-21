<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperateCompany();
$a->custom_nav = [
    'Home' => '/',
    'Interviews' => '/queues.php'
];

$a->body_main = function () { ?>
    <div id="company-profile"
        style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; padding: 1.5rem; background: var(--surface-primary); border-radius: 12px; border: 1px solid var(--border-subtle);">
        <img id="company-logo" src="" alt="Logo"
            style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; background: var(--surface-secondary);">
        <div style="flex-grow: 1;">
            <h1 id="company-name" style="margin: 0; font-size: 1.5rem; color: var(--text-primary);"></h1>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem;">
                <p id="company-table" style="margin: 0; font-weight: 600; color: var(--accent-primary);"></p>
                <div id="company-status-badge"
                    style="padding: 0.15rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                </div>
            </div>
        </div>
        <a href="/company_logout.php" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem; border: 1px solid var(--border-subtle); background: var(--surface-primary); color: var(--brand-maroon); font-weight: 600; text-decoration: none; border-radius: 6px;">Sign Out</a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
        <div style="padding: 1rem; background: var(--surface-secondary); border-radius: 8px; text-align: center;">
            <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">Waiting</p>
            <p id="queue-count" style="margin: 0.5rem 0 0; font-size: 2rem; font-weight: bold; color: var(--brand-maroon);">
                0</p>
        </div>
        <div style="padding: 1rem; background: var(--surface-secondary); border-radius: 8px; text-align: center;">
            <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">Completed Today</p>
            <p id="completed-count"
                style="margin: 0.5rem 0 0; font-size: 2rem; font-weight: bold; color: var(--brand-green, #4CAF50);">0</p>
        </div>
    </div>

    <div id="current-interview"
        style="display: none; margin-bottom: 2rem; padding: 1.5rem; background: var(--surface-primary); border-radius: 12px; border: 1px solid var(--border-subtle);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <p id="interviewee-email" style="margin: 0; font-weight: 600; color: var(--text-primary);"></p>
                <p id="interviewee-number"
                    style="margin: 0.25rem 0 0; color: var(--brand-maroon); font-weight: 700; font-size: 1.2rem;"></p>
            </div>
            <div id="interview-state-badge"
                style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"></div>
        </div>

        <!-- ── Candidate profile (populated by JS when data available) ── -->
        <div id="candidate-profile-info"
            style="display:none; margin-bottom: 1.25rem; padding: 1rem; background: var(--surface-secondary); border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="display:flex; align-items:center; gap: 1rem; flex-wrap:wrap;">
                <img id="cand-avatar" src="" alt=""
                    style="width:52px;height:52px;border-radius:50%;object-fit:cover;background:var(--surface-primary); display:none;">
                <div style="flex:1; min-width:0;">
                    <p id="cand-fullname" style="margin:0; font-size:1.05rem; font-weight:700; color:var(--text-primary);">
                    </p>
                    <div id="cand-chips" style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.35rem;"></div>
                </div>
                <a id="cand-cv-link" href="#" target="_blank" rel="noopener"
                    style="display:none; padding:0.35rem 0.85rem; border-radius:6px; background:var(--brand-maroon,#7b1a1a); color:#fff; font-size:0.8rem; font-weight:600; text-decoration:none; flex-shrink:0;">
                    📄 CV
                </a>
            </div>
            <p id="cand-interests"
                style="display:none; margin: 0.6rem 0 0; font-size:0.82rem; color:var(--text-secondary); line-height:1.4;">
            </p>
        </div>

        <div id="interview-timer-container" style="margin-bottom: 1.5rem; text-align: center; display: none;">
            <div id="interview-timer"
                style="font-size: 2.5rem; font-weight: 800; font-family: monospace; color: var(--brand-green, #4CAF50);">
                10:00</div>
            <p
                style="margin: 0.25rem 0 0; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">
                Interview Time (Suggestive)</p>
        </div>
    </div>

    <div id="no-current-interview"
        style="margin-bottom: 2rem; padding: 1.5rem; background: var(--surface-primary); border-radius: 12px; border: 1px solid var(--border-subtle); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 150px; opacity: 0.7;">
        <p id="no-interview-status-text"
            style="font-size: 1.2rem; font-weight: 600; color: var(--text-secondary); margin: 0; text-align: center;">
            Waiting for candidates...</p>
        <p id="no-interview-hint-text"
            style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.5rem; text-align: center;">You are
            currently available.<br>The first available candidate in your queue will be automatically called.</p>
    </div>

    <div id="queue-container" style="margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;text-align: center;">Waiting Queue</h3>
        <div id="queue-list" style="display: flex; flex-direction: column; gap: 0.75rem;">
            <!-- Filled via JavaScript -->
        </div>
    </div>

    <div id="history-container" style="margin-bottom: 3rem; opacity: 0.85;">
        <h3
            style="text-align: center; margin-bottom: 1rem; color: var(--text-secondary); font-size: 1.1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.5rem;">
            Recent History</h3>
        <div id="history-list" style="display: flex; flex-direction: column; gap: 0.5rem;">
            <!-- Filled via JavaScript -->
        </div>
        <button id="btn-show-more" class="btn-secondary"
            style="margin-top: 1rem; width: 100%; display: none; background: var(--surface-secondary); color: var(--text-primary); border: 1px solid var(--border-subtle);">Show
            More</button>
    </div>

    <div id="status-controls"
        style="margin-top: 2rem; padding: 2rem; background: var(--surface-primary); border-radius: 12px; text-align: center; border: 1px solid var(--border-subtle);">
        <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button id="btn-pause" class="btn-primary"
                    style="padding: 0.75rem 2.5rem; font-size: 1.1rem; min-width: 250px;">Pause Interviews</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('dialog_info').showModal();"
                    style="border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; background: var(--surface-secondary); color: var(--brand-maroon);">?</button>
            </div>
            <p id="status-hint" style="color: var(--text-secondary); margin: 0; font-size: 0.9rem; max-width: 400px;"></p>
        </div>
    </div>

    <!-- Pause Confirmation Dialog -->
    <dialog id="dialog_pause_confirm" onclick="if(event.target===this)this.close();"
        style="max-width: 400px; border: none; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="display: flex; flex-direction: column; gap: 1.5rem; text-align: center;">
            <p style="margin: 0; font-weight: bold; color: var(--brand-maroon); font-size: 1.25rem;">⚠️ Interview In
                Progress</p>
            <p style="margin: 0; color: var(--text-secondary); line-height: 1.6;">An interview is currently happening. You
                must complete or cancel it before pausing.</p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <button type="button" id="btn_complete_and_pause" class="btn-primary" style="width: 100%;">✓ Complete &
                    Pause</button>
                <button type="button" onclick="document.getElementById('dialog_pause_confirm').close();"
                    class="btn-secondary"
                    style="width: 100%; background: var(--surface-secondary); color: var(--text-primary); border: 1px solid var(--border-subtle);">Cancel</button>
            </div>
        </div>
    </dialog>

    <dialog id="dialog_info" onclick="if(event.target===this)this.close();" class="info-dialog"
        style="max-width: 600px; border: none; border-radius: 12px; padding: 0; box-shadow: 0 10px 40px rgba(0,0,0,0.3); overflow: hidden;">
        <div
            style="background:white ; color: var(--brand-maroon); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.5rem;">Interviewer Guide</h2>
            <button onclick="this.closest('dialog').close();"
                style="background: none; border: none; font-size: 2rem; color: var(--brand-maroon); cursor: pointer; line-height: 1;">×</button>
        </div>
        <div style="padding: 2rem; color: var(--text-primary); line-height: 1.6; max-height: 70vh; overflow-y: auto;">
            <section style="margin-bottom: 2rem;">
                <h3
                    style="color: var(--brand-maroon); border-bottom: 2px solid var(--border-subtle); padding-bottom: 0.5rem;">
                    1. Automatic Calling</h3>
                <p>When you are <strong>Active</strong> and a candidate is available, the system will automatically move
                    them to the <strong>CALLING</strong> state. They have 3 minutes to arrive.</p>
            </section>

            <section style="margin-bottom: 2rem;">
                <h3
                    style="color: var(--brand-maroon); border-bottom: 2px solid var(--border-subtle); padding-bottom: 0.5rem;">
                    2. Interview States</h3>
                <ul style="padding-left: 1.5rem;">
                    <li><strong>CALLING</strong>: Candidate has been notified. Press <strong>"Arrived"</strong> when they
                        reach your table. Or Didn't Show up to call next available candidate.</li>
                    <li><strong>HAPPENING</strong>: The interview is in progress. A suggestive 10-minute timer will appear.
                    </li>
                    <li><strong>DECISION</strong>: The calling period has ended. The Gatekeeper will decide whether the
                        candidate arrived on time. If a candidate doesnt show up he will be removed from the queue.</li>
                </ul>
            </section>

            <section style="margin-bottom: 2rem;">
                <h3
                    style="color: var(--brand-maroon); border-bottom: 2px solid var(--border-subtle); padding-bottom: 0.5rem;">
                    3. The Interview Timer</h3>
                <p>During an active interview, a clock appears. It starts <strong>Green</strong> and turns
                    <strong>Red</strong> after 10 minutes. This is <strong>suggestive</strong> and won't automatically end
                    your interview.
                </p>
            </section>

            <section style="margin-bottom: 2rem;">
                <h3
                    style="color: var(--brand-maroon); border-bottom: 2px solid var(--border-subtle); padding-bottom: 0.5rem;">
                    4. Pausing</h3>
                <p>Use <strong>"Pause Interviews"</strong> to stop receiving new candidates. If you are currentlly in an
                    interview you need to ccomplete before pausing.</p>
            </section>
        </div>
        <div style="padding: 1.5rem; background: var(--surface-secondary); text-align: center;">
            <button onclick="this.closest('dialog').close();" class="btn-primary" style="padding: 0.75rem 3rem;">Ready to
                Start</button>
        </div>
    </dialog>

    <!-- CV Preview Modal -->
    <dialog id="dialog_cv_preview" onclick="if(event.target===this)this.close();"
        style="width: 90vw; max-width: 1000px; height: 90vh; border: none; border-radius: 12px; padding: 0; box-shadow: 0 10px 50px rgba(0,0,0,0.4); overflow: hidden; background: #525659;">
        <div style="background: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle);">
            <h3 style="margin: 0; font-size: 1.2rem; color: var(--brand-maroon);" id="cv_preview_title">Candidate CV</h3>
            <div style="display: flex; gap: 0.75rem;">
                <a id="btn_cv_external" href="#" target="_blank" class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;">Open External ↗</a>
                <button onclick="document.getElementById('dialog_cv_preview').close();"
                    style="background: #eee; border: none; font-size: 1.5rem; color: #666; cursor: pointer; line-height: 1; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
        </div>
        <iframe id="cv_iframe" src="" style="width: 100%; height: calc(100% - 60px); border: none;"></iframe>
    </dialog>

    <script src="/script/utilities.js"></script>
    <script src="/script/short_polling.js"></script>
    <script src="/script/company_dashboard.js"></script>
    <script>
        short_polling(2, 'company', (data) => {
            update_dashboard(data);
        });
    </script>
<?php };

$a->assemble();
