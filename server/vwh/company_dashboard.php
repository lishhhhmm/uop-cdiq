<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperateCompany();
$a->custom_nav = [
    'Home' => '/',
    'Interviews' => '/queues.php'
];

$a->body_main = function () { ?>
    <div class="comp-dashboard-container animate-fade-in">
        <!-- Main Content Area -->
        <div class="comp-main-content">
            <!-- Profile Card -->
            <div class="comp-profile-card">
                <img id="company-logo" src="" alt="Logo" class="comp-profile-card__logo">
                <div class="comp-profile-card__info">
                    <h1 id="company-name"></h1>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                        <span id="company-table" class="comp-table-tag" style="margin-top: 0;"></span>
                        <div id="company-status-badge" class="comp-status-tag" style="font-size: 0.75rem; color: white;">
                        </div>
                    </div>
                </div>
                <div class="comp-header-actions">
                    <a href="/company_logout.php" class="btn-outline-sm danger">Sign Out</a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="comp-stats-grid">
                <div class="comp-stat-card">
                    <span class="comp-stat-card__label">Waiting in Queue</span>
                    <div id="queue-count" class="comp-stat-card__value">0</div>
                </div>
                <div class="comp-stat-card">
                    <span class="comp-stat-card__label">Total Completed</span>
                    <div id="completed-count" class="comp-stat-card__value">0</div>
                </div>
            </div>

            <!-- Live Session Container -->
            <div id="live-session-wrapper">
                <!-- Current Interview Card -->
                <div id="current-interview" class="comp-live-session comp-live-session--active" style="display: none;">
                    <div class="comp-live-session__label">Current Interview Session</div>

                    <div id="interview-state-badge" class="comp-status-tag" style="margin-bottom: 1rem; color: white;">
                    </div>

                    <div class="comp-candidate-display">
                        <div id="interviewee-number" class="comp-candidate-number"></div>
                        <div id="interviewee-email" class="comp-candidate-email"></div>
                    </div>

                    <!-- Detailed Candidate Profile (Toggled by JS) -->
                    <div id="candidate-profile-info" class="glass"
                        style="display:none; margin: 1.5rem auto; max-width: 500px; padding: 1.25rem; border-radius: var(--radius-lg); text-align: left; border: 1px solid var(--border);">
                        <div style="display:flex; align-items:center; gap: 1rem;">
                            <img id="cand-avatar" src="" alt=""
                                style="width:56px; height:56px; border-radius:50%; object-fit:cover; border: 2px solid var(--brand-maroon); display:none;">
                            <div style="flex:1;">
                                <div id="cand-fullname"
                                    style="font-weight:700; color:var(--text-primary); font-size: 1.1rem;"></div>
                                <div id="cand-chips" style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.4rem;">
                                </div>
                            </div>
                            <a id="cand-cv-link" href="#" target="_blank" class="btn-primary"
                                style="padding: 0.45rem 0.9rem; font-size: 0.8rem; border-radius: 6px;">📄 CV</a>
                        </div>
                        <p id="cand-interests"
                            style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;">
                        </p>
                    </div>

                    <div id="interview-timer-container" class="comp-timer-display" style="display:none;">
                        <div id="interview-timer" class="comp-timer-large">10:00</div>
                        <div class="comp-timer-label">Suggested Duration</div>
                    </div>
                </div>

                <!-- No Interview / Available State -->
                <div id="no-current-interview" class="comp-live-session">
                    <div class="comp-empty-state">
                        <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">🏢</span>
                        <p id="no-interview-status-text"
                            style="font-size: 1.25rem; color: var(--text-primary); margin-bottom: 0.5rem;"></p>
                        <p id="no-interview-hint-text"
                            style="font-size: 0.9rem; color: var(--text-secondary); max-width: 320px; margin: 0 auto;"></p>
                    </div>
                </div>
            </div>

            <!-- Global Status Control -->
            <div class="comp-controls">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <button id="btn-pause" class="btn-primary comp-controls__btn">Pause Dashboard</button>
                        <button type="button" class="btn-outline-sm maroon"
                            onclick="document.getElementById('dialog_info').showModal();"
                            style="width: 44px; height: 44px; padding: 0; font-size: 1.2rem;">ⓘ</button>
                    </div>
                    <p id="status-hint" class="comp-controls__hint"></p>
                </div>
            </div>
        </div>

        <!-- Sidebar Area -->
        <div class="comp-sidebar">
            <!-- Queue Section -->
            <div id="queue-container" class="comp-sidebar-section">
                <div class="comp-sidebar-section__title">Queue Status</div>
                <div id="queue-list">
                    <!-- Populated by JS -->
                </div>
            </div>

            <!-- History Section -->
            <div id="history-container" class="comp-sidebar-section">
                <div class="comp-sidebar-section__title">Recent Sessions</div>
                <div id="history-list">
                    <!-- Populated by JS -->
                </div>
                <button id="btn-show-more" class="btn-outline-sm maroon"
                    style="width: 100%; margin-top: 1.25rem; display: none;">See All Completed</button>
            </div>
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
        <div
            style="background: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle);">
            <h3 style="margin: 0; font-size: 1.2rem; color: var(--brand-maroon);" id="cv_preview_title">Candidate CV</h3>
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
    <script src="/script/company_dashboard.js"></script>
    <script>
        short_polling(2, 'company', (data) => {
            update_dashboard(data);
        });
    </script>
<?php };

$a->assemble();
