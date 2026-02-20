let calling_time_in_seconds = 0;
let live_timer_interval = null;
let history_expanded = false;

function update_dashboard(data) {
    console.log("Dashboard update received:", data);

    if (!data || typeof data !== 'object') {
        return;
    }

    // Auto-show help on first visit
    if (!localStorage.getItem('dashboard_help_seen')) {
        const helpDialog = document.getElementById('dialog_info');
        if (helpDialog) {
            helpDialog.showModal();
            localStorage.setItem('dashboard_help_seen', 'true');
        }
    }

    calling_time_in_seconds = data.calling_time || 180;
    const companyId = data.company_id;
    if (!companyId) return;

    const interviewers = data.interviewers || [];
    const interviewees = data.interviewees || [];
    const allInterviews = data.all_interviews || [];

    const company = interviewers.find(i => i.id == companyId);

    const myInterviews = allInterviews.filter(iw => iw.id_interviewer == companyId);
    const waiting = myInterviews.filter(iw => iw.state_ === 'ENQUEUED');
    const current = myInterviews.find(iw => ['CALLING', 'DECISION', 'HAPPENING'].includes(iw.state_));
    const completed = myInterviews.filter(iw => iw.state_ === 'COMPLETED').sort((a, b) => b.id - a.id);

    if (company) {
        document.getElementById('company-name').textContent = company.name;
        document.getElementById('company-table').textContent = "Table " + company.table_number;
        document.getElementById('company-logo').src = company.image_resource_url || '/resources/favicon/normal.svg';

        const companyStatusBadge = document.getElementById('company-status-badge');
        if (companyStatusBadge) {
            if (!company.active) {
                companyStatusBadge.textContent = "Paused";
                companyStatusBadge.style.background = "var(--color-status--unavailable)";
            } else if (!current) {
                // Check if anyone in queue is genuinely available
                const anyReady = waiting.some(iw => {
                    const iwee = interviewees.find(i => i.id == iw.id_interviewee);
                    const isGloballyPaused = iwee && !iwee.active;
                    const isBusyElsewhere = allInterviews.some(oi =>
                        oi.id_interviewee == iw.id_interviewee &&
                        ['CALLING', 'DECISION', 'HAPPENING'].includes(oi.state_)
                    );
                    return !isGloballyPaused && !isBusyElsewhere;
                });

                if (waiting.length > 0 && !anyReady) {
                    companyStatusBadge.textContent = "Available";
                    companyStatusBadge.style.background = "#0ea5e9"; // Orange
                } else {
                    companyStatusBadge.textContent = "Available";
                    companyStatusBadge.style.background = "var(--color-status--available)";
                }
            } else if (current.state_ === 'HAPPENING') {
                companyStatusBadge.textContent = "Interviewing";
                companyStatusBadge.style.background = "var(--color-status--happening)";
            } else {
                // CALLING or DECISION
                companyStatusBadge.textContent = "Calling Candidate";
                companyStatusBadge.style.background = "var(--color-status--calling)";
            }
            companyStatusBadge.style.color = "white";
        }
    }

    document.getElementById('queue-count').textContent = waiting.length;
    document.getElementById('completed-count').textContent = completed.length;

    update_current_interview(current, interviewees, companyId, company, waiting, allInterviews);
    update_pause_logic(company, companyId, current);
    update_queue_list(waiting, interviewees, allInterviews, company);
    update_history_list(completed);
}

function update_current_interview(current, interviewees, companyId, company, waiting, allInterviews) {
    const currentDiv = document.getElementById('current-interview');
    const noCurrentDiv = document.getElementById('no-current-interview');

    const intervieweeEmail = document.getElementById('interviewee-email');
    const intervieweeNumber = document.getElementById('interviewee-number');
    const stateBadge = document.getElementById('interview-state-badge');

    const noInterviewStatusText = document.getElementById('no-interview-status-text');
    const noInterviewHintText = document.getElementById('no-interview-hint-text');

    const btnArrived = document.getElementById('btn-arrived');
    const btnComplete = document.getElementById('btn-complete');
    const btnNoShow = document.getElementById('btn-no-show');

    const timerContainer = document.getElementById('interview-timer-container');
    const timerDisplay = document.getElementById('interview-timer');

    if (live_timer_interval) {
        clearInterval(live_timer_interval);
        live_timer_interval = null;
    }

    if (!current) {
        currentDiv.style.display = 'none';
        noCurrentDiv.style.display = 'flex';
        if (timerContainer) timerContainer.style.display = 'none';

        if (company && !company.active) {
            noInterviewStatusText.textContent = "Dashboard Paused";
            noInterviewHintText.textContent = "Resume your dashboard to start calling candidates from your queue.";
        } else if (waiting.length === 0) {
            noInterviewStatusText.textContent = "Queue is Empty";
            noInterviewHintText.textContent = "No candidates have enqueued for an interview with you yet.";
        } else {
            // Check why none are called
            const anyGloballyPaused = waiting.some(iw => {
                const iwee = interviewees.find(i => i.id == iw.id_interviewee);
                return iwee && !iwee.active;
            });
            const anyBusyElsewhere = waiting.some(iw => {
                return allInterviews.some(oi =>
                    oi.id_interviewee == iw.id_interviewee &&
                    ['CALLING', 'DECISION', 'HAPPENING'].includes(oi.state_)
                );
            });

            if (anyBusyElsewhere && !anyGloballyPaused) {
                noInterviewStatusText.textContent = "All Candidates Busy";
                noInterviewHintText.textContent = "Everyone in your queue is currently in another interview. The system will call the first one available.";
            } else if (anyGloballyPaused && !anyBusyElsewhere) {
                noInterviewStatusText.textContent = "Candidates Paused";
                noInterviewHintText.textContent = "The candidates in your queue are currently marked as 'Not Present' by the Secretary.";
            } else if (anyBusyElsewhere && anyGloballyPaused) {
                noInterviewStatusText.textContent = "Waiting for Candidates";
                noInterviewHintText.textContent = "Candidates in your queue are either in-interview or paused globally.";
            } else {
                noInterviewStatusText.textContent = "Ready & Available";
                noInterviewHintText.textContent = "The system is processing your queue. A candidate should be called shortly.";
            }
        }
        return;
    }

    currentDiv.style.display = 'block';
    noCurrentDiv.style.display = 'none';

    const iwee = interviewees.find(i => i.id == current.id_interviewee);
    intervieweeEmail.textContent = iwee ? iwee.email : 'Unknown Candidate';
    intervieweeNumber.textContent = "Candidate #" + current.id_interviewee;

    btnArrived.style.display = 'none';
    btnComplete.style.display = 'none';
    btnNoShow.style.display = 'none';

    const friendlyState = current.state_ === 'HAPPENING' ? 'Interviewing' :
        (current.state_ === 'CALLING' || current.state_ === 'DECISION') ? 'Waiting for Candidate to Arrive' : current.state_;
    stateBadge.textContent = friendlyState;
    const stateTimestamp = Date.parse(current.state_timestamp + '+00:00');

    if (current.state_ === 'CALLING' || current.state_ === 'DECISION') {
        if (timerContainer) timerContainer.style.display = 'none';
        stateBadge.style.background = current.state_ === 'CALLING' ? 'var(--color-status--calling)' : 'var(--color-status--decision)';
        stateBadge.style.color = '#fff';
        btnArrived.style.display = 'block';
        btnNoShow.style.display = 'block';

        btnArrived.onclick = () => {
            const formData = new FormData();
            formData.append('button_to_happening', 'true');
            formData.append('input_interview_id', current.id);
            submit_action(formData);
        };

        btnNoShow.onclick = () => {
            if (confirm("Mark as NO SHOW? The candidate will be removed from the current queue.")) {
                const formData = new FormData();
                formData.append('button_no_show', 'true');
                formData.append('input_interviewee_id', current.id_interviewee);
                submit_action(formData);
            }
        };

        if (current.state_ === 'CALLING') {
            const startTimer = () => {
                let remaining = stateTimestamp + (calling_time_in_seconds * 1000) - Date.now();
                remaining = Math.max(0, remaining);
                const mins = Math.floor(remaining / 60000);
                const secs = Math.floor((remaining % 60000) / 1000);
                stateBadge.textContent = `Waiting for Candidate to Arrive (${mins}:${secs < 10 ? '0' : ''}${secs})`;
            };
            startTimer();
            live_timer_interval = setInterval(startTimer, 1000);
        }
    } else if (current.state_ === 'HAPPENING') {
        if (timerContainer) timerContainer.style.display = 'block';
        stateBadge.style.background = 'var(--color-status--happening)';
        stateBadge.style.color = '#fff';
        btnComplete.style.display = 'block';

        btnComplete.onclick = () => {
            if (confirm("Mark interview as COMPLETED?")) {
                const formData = new FormData();
                formData.append('button_to_completed', 'true');
                formData.append('input_interview_id', current.id);
                submit_action(formData);
            }
        };

        const INTERVIEW_LIMIT_SEC = 10 * 60; // 10 minutes

        const startTimer = () => {
            let elapsedSec = Math.floor((Date.now() - stateTimestamp) / 1000);
            let remainingSec = INTERVIEW_LIMIT_SEC - elapsedSec;

            let isOverdue = remainingSec < 0;
            let absRemaining = Math.abs(remainingSec);
            let mins = Math.floor(absRemaining / 60);
            let secs = absRemaining % 60;

            let timeStr = (isOverdue ? "-" : "") + mins + ":" + (secs < 10 ? '0' : '') + secs;
            if (timerDisplay) {
                timerDisplay.textContent = timeStr;

                // Color logic
                if (remainingSec > 180) { // > 3 mins left
                    timerDisplay.style.color = "var(--brand-green, #4CAF50)";
                } else if (remainingSec > 60) { // 1-3 mins left
                    timerDisplay.style.color = "#FFC107"; // Yellow
                } else if (remainingSec >= 0) { // < 1 min left
                    timerDisplay.style.color = "#FF9800"; // Orange
                } else { // Overdue
                    timerDisplay.style.color = "#F44336"; // Red
                }
            }

            // Also update the badge text
            let elapsedMins = Math.floor(elapsedSec / 60);
            let elapsedSecsRemaining = elapsedSec % 60;
            stateBadge.textContent = `Interviewing (${elapsedMins}:${elapsedSecsRemaining < 10 ? '0' : ''}${elapsedSecsRemaining})`;
        };
        startTimer();
        live_timer_interval = setInterval(startTimer, 1000);
    }
}

function update_pause_logic(company, companyId, current) {
    const btnPause = document.getElementById('btn-pause');
    const statusHint = document.getElementById('status-hint');
    const dialogPauseConfirm = document.getElementById('dialog_pause_confirm');
    const btnCompleteAndPause = document.getElementById('btn_complete_and_pause');

    if (!company) return;

    if (company.active) {
        btnPause.textContent = "Pause Dashboard";
        btnPause.style.background = "";
        statusHint.textContent = "You are currently ACTIVE. New candidates will be automatically assigned to you.";
    } else {
        btnPause.textContent = "Resume Dashboard";
        btnPause.style.background = "#666";
        statusHint.textContent = "You are currently PAUSED. No new candidates will be assigned until you Resume.";
    }

    btnPause.onclick = () => {
        if (company.active && current && current.state_ === 'HAPPENING') {
            dialogPauseConfirm.showModal();

            btnCompleteAndPause.onclick = () => {
                const formData = new FormData();
                formData.append('button_to_completed_and_pause', 'true');
                formData.append('input_interview_id', current.id);
                formData.append('input_interviewer_id', companyId);
                submit_action(formData);
                dialogPauseConfirm.close();
            };
            return;
        }

        const formData = new FormData();
        formData.append('button_active_inactive', 'true');
        formData.append('input_interviewer_id', companyId);
        submit_action(formData);
    };
}

function update_queue_list(waiting, interviewees, allInterviews, company) {
    const queueList = document.getElementById('queue-list');
    queueList.innerHTML = '';

    if (waiting.length === 0) {
        if (company && company.active) {
            queueList.innerHTML = '<div style="text-align: center; color: var(--text-secondary); margin: 2rem 0; padding: 2rem; border: 1px dashed var(--border-subtle); border-radius: 12px;">' +
                '<p style="font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 600;">Queue is empty</p>' +
                '<p style="font-size: 0.9rem; opacity: 0.8;">No available candidates at the moment.</p>' +
                '</div>';
        } else {
            queueList.innerHTML = '<p style="text-align: center; color: var(--text-secondary); margin: 2rem 0;">No candidates waiting.</p>';
        }
        return;
    }

    waiting.forEach((iw, index) => {
        const iwee = interviewees.find(i => i.id == iw.id_interviewee);
        const div = document.createElement('div');
        div.className = 'queue-item';
        div.style.padding = '0.75rem 1rem';
        div.style.background = 'var(--surface-primary)';
        div.style.borderRadius = '8px';
        div.style.border = '1px solid var(--border-subtle)';
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.alignItems = 'center';
        div.style.marginBottom = '0.5rem';

        const otherActiveInterviews = allInterviews.filter(oi =>
            oi.id_interviewee == iw.id_interviewee &&
            ['CALLING', 'DECISION', 'HAPPENING'].includes(oi.state_)
        );

        let statusText = "Available";
        let statusColor = "var(--color-status--available)";

        if (iwee && !iwee.active) {
            statusText = "Paused";
            statusColor = "var(--color-status--unavailable)";
        } else if (otherActiveInterviews.length > 0) {
            statusText = "In Interview";
            statusColor = "var(--color-status--happening)";
        }

        div.innerHTML = `
            <div style="display: flex; flex-direction: column;">
                <span style="font-weight: 500; color: var(--text-primary);">
                    <span style="color: var(--text-secondary); margin-right: 0.5rem;">${index + 1}.</span>
                    ${iwee ? iwee.email : 'Unknown Candidate'}
                    <span style="color: var(--text-secondary); font-size: 0.8rem; margin-left: 0.5rem;">(#${iw.id_interviewee})</span>
                </span>
            </div>
            <span style="font-size: 0.75rem; font-weight: bold; color: ${statusColor}; text-shadow: 0 0 1px rgba(0,0,0,0.1); text-transform: uppercase;">${statusText}</span>
        `;
        queueList.appendChild(div);
    });
}

function update_history_list(completed) {
    const historyList = document.getElementById('history-list');
    const btnShowMore = document.getElementById('btn-show-more');
    historyList.innerHTML = '';

    if (completed.length === 0) {
        document.getElementById('history-container').style.display = 'none';
        return;
    }
    document.getElementById('history-container').style.display = 'block';

    const visibleCount = history_expanded ? completed.length : 2;
    const toShow = completed.slice(0, visibleCount);

    toShow.forEach(iw => {
        const div = document.createElement('div');
        div.style.padding = '0.5rem 1rem';
        div.style.background = 'var(--surface-secondary)';
        div.style.borderRadius = '6px';
        div.style.fontSize = '0.85rem';
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.color = 'var(--text-secondary)';

        div.innerHTML = `
            <span>Candidate #${iw.id_interviewee}</span>
            <span>Completed</span>
        `;
        historyList.appendChild(div);
    });

    if (completed.length > 2) {
        btnShowMore.style.display = 'block';
        btnShowMore.textContent = history_expanded ? "Show Less" : "Show More (" + (completed.length - 2) + " more)";
        btnShowMore.onclick = () => {
            history_expanded = !history_expanded;
            update_history_list(completed);
        };
    } else {
        btnShowMore.style.display = 'none';
    }
}

function submit_action(formData) {
    const xhr = new XMLHttpRequest();
    const uid = typeof (update_id) !== 'undefined' ? update_id : 0;
    xhr.open('POST', '/_update.php?want_to_make_changes=' + uid);
    xhr.onload = () => {
        if (xhr.status === 200 && xhr.responseText.trim() === 'ok') {
            // refresh via polling
        } else {
            console.error('Action failed:', xhr.responseText);
            alert('Action failed: ' + xhr.responseText);
        }
    };
    xhr.send(formData);
}
