let calling_time_in_seconds = 0;
let live_timer_interval = null;
let history_expanded = false;
let help_checked = false;

function update_dashboard(data) {
    console.log("Dashboard update received:", data);

    if (!data || typeof data !== 'object') {
        return;
    }

    // Auto-show help ONLY on first login for this specific company
    if (!help_checked && data.company_id) {
        const storageKey = 'help_seen_company_' + data.company_id;
        const helpDialog = document.getElementById('dialog_info');
        if (helpDialog && !localStorage.getItem(storageKey)) {
            helpDialog.showModal();
            localStorage.setItem(storageKey, 'true');
        }
        help_checked = true;
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
            // Note: In the new UI, the header status is less prominent or integrated differently.
            // Keeping for future use if we add a top badge back, but usually handled by the live-session area now.
            if (!company.active) {
                companyStatusBadge.textContent = "Paused";
                companyStatusBadge.className = "comp-status-tag";
                companyStatusBadge.style.background = "var(--color-status--unavailable)";
            } else {
                companyStatusBadge.textContent = "Active";
                companyStatusBadge.className = "comp-status-tag";
                companyStatusBadge.style.background = "var(--color-status--available)";
            }
            companyStatusBadge.style.color = "white";
        }
    }

    document.getElementById('queue-count').textContent = waiting.length;
    document.getElementById('completed-count').textContent = completed.length;

    update_current_interview(current, interviewees, companyId, company, waiting, allInterviews);
    update_pause_logic(company, companyId, current);
    update_queue_list(waiting, interviewees, allInterviews, company);
    update_history_list(completed, interviewees);
}

function update_current_interview(current, interviewees, companyId, company, waiting, allInterviews) {
    const currentDiv = document.getElementById('current-interview');
    const noCurrentDiv = document.getElementById('no-current-interview');

    const intervieweeEmail = document.getElementById('interviewee-email');
    const intervieweeNumber = document.getElementById('interviewee-number');
    const stateBadge = document.getElementById('interview-state-badge');

    const noInterviewStatusText = document.getElementById('no-interview-status-text');
    const noInterviewHintText = document.getElementById('no-interview-hint-text');


    const timerContainer = document.getElementById('interview-timer-container');
    const timerDisplay = document.getElementById('interview-timer');

    // Profile block elements
    const profileBlock = document.getElementById('candidate-profile-info');
    const elAvatar = document.getElementById('cand-avatar');
    const elFullname = document.getElementById('cand-fullname');
    const elChips = document.getElementById('cand-chips');
    const elCvLink = document.getElementById('cand-cv-link');
    const elInterests = document.getElementById('cand-interests');

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

    // ── Candidate profile block ──────────────────────────────────────────
    if (profileBlock) {
        const hasName = iwee && iwee.display_name;
        const hasAvatar = iwee && iwee.avatar_url;
        const hasDept = iwee && iwee.department;
        const hasMasters = iwee && iwee.masters;
        const hasInterests = iwee && iwee.interests;
        const hasCv = iwee && iwee.cv_resource_url;
        const hasAnyProfile = hasName || hasAvatar || hasDept || hasMasters || hasInterests || hasCv;

        if (hasAnyProfile) {
            profileBlock.style.display = 'block';

            // Avatar
            if (hasAvatar) {
                elAvatar.src = iwee.avatar_url;
                elAvatar.style.display = 'block';
            } else {
                elAvatar.style.display = 'none';
            }

            // Full name (fallback to email)
            elFullname.textContent = hasName ? iwee.display_name : (iwee ? iwee.email : '');

            // Chips: department + masters
            elChips.innerHTML = '';
            const chipStyle = 'padding:0.2rem 0.6rem; border-radius:6px; font-size:0.75rem; font-weight:700; background:rgba(0,0,0,0.05); color:var(--text-secondary); border: 1px solid var(--border);';
            if (hasDept) {
                const c = document.createElement('span');
                c.setAttribute('style', chipStyle);
                c.textContent = iwee.department;
                elChips.appendChild(c);
            }
            if (hasMasters && iwee.masters.toLowerCase() !== 'no') {
                const c = document.createElement('span');
                c.setAttribute('style', chipStyle + ' color:var(--brand-maroon); background:rgba(157,28,32,0.05);');
                c.textContent = 'MSc: ' + iwee.masters;
                elChips.appendChild(c);
            }

            // Interests
            if (hasInterests) {
                elInterests.textContent = '🎯 Interests: ' + iwee.interests;
                elInterests.style.display = 'block';
            } else {
                elInterests.style.display = 'none';
            }

            // CV link
            if (hasCv) {
                elCvLink.style.display = 'inline-block';
                elCvLink.href = iwee.cv_resource_url;
                elCvLink.onclick = (e) => {
                    e.preventDefault();
                    const modal = document.getElementById('dialog_cv_preview');
                    const iframe = document.getElementById('cv_iframe');
                    const title = document.getElementById('cv_preview_title');
                    const btnExt = document.getElementById('btn_cv_external');

                    title.textContent = "CV: " + (iwee.display_name || iwee.email);
                    btnExt.href = iwee.cv_resource_url;
                    iframe.src = iwee.cv_resource_url;
                    modal.showModal();
                };
            } else {
                elCvLink.style.display = 'none';
            }
        } else {
            profileBlock.style.display = 'none';
        }
    }


    const friendlyState = current.state_ === 'HAPPENING' ? 'Interviewing' :
        (current.state_ === 'CALLING' || current.state_ === 'DECISION') ? 'Waiting for Candidate to Arrive' : current.state_;
    stateBadge.textContent = friendlyState;
    const stateTimestamp = Date.parse(current.state_timestamp + '+00:00');

    if (current.state_ === 'CALLING' || current.state_ === 'DECISION') {
        if (timerContainer) timerContainer.style.display = 'none';
        stateBadge.style.background = current.state_ === 'CALLING' ? 'var(--color-status--calling)' : 'var(--color-status--decision)';
        stateBadge.className = 'comp-status-tag';
        if (current.state_ === 'CALLING') {
            const startTimer = () => {
                let remaining = stateTimestamp + (calling_time_in_seconds * 1000) - Date.now();
                stateBadge.textContent = `Waiting for Candidate to Arrive (${formatDuration(remaining > 0 ? remaining : 0)})`;
            };
            startTimer();
            live_timer_interval = setInterval(startTimer, 1000);
        }
    } else if (current.state_ === 'HAPPENING') {
        if (timerContainer) timerContainer.style.display = 'block';
        stateBadge.style.background = 'var(--color-status--happening)';
        stateBadge.className = 'comp-status-tag';
        const INTERVIEW_LIMIT_SEC = 10 * 60; // 10 minutes

        const startTimer = () => {
            let elapsedMs = Date.now() - stateTimestamp;
            let remainingMs = (INTERVIEW_LIMIT_SEC * 1000) - elapsedMs;

            let timeStr = formatDuration(remainingMs);
            if (timerDisplay) {
                timerDisplay.textContent = timeStr;

                // Color logic
                let remSec = remainingMs / 1000;
                if (remSec > 180) { // > 3 mins left
                    timerDisplay.style.color = "var(--brand-green, #4CAF50)";
                } else if (remSec > 60) { // 1-3 mins left
                    timerDisplay.style.color = "#FFC107"; // Yellow
                } else if (remSec >= 0) { // < 1 min left
                    timerDisplay.style.color = "#FF9800"; // Orange
                } else { // Overdue
                    timerDisplay.style.color = "#F44336"; // Red
                }
            }

            // Also update the badge text
            stateBadge.textContent = `Interviewing (${formatDuration(elapsedMs)})`;
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
        queueList.innerHTML = `
            <div class="comp-empty-state">
                <p>No candidates waiting.</p>
            </div>`;
        return;
    }

    waiting.forEach((iw, index) => {
        const iwee = interviewees.find(i => i.id == iw.id_interviewee);
        const div = document.createElement('div');
        div.className = 'comp-queue-item';

        const otherActiveInterviews = allInterviews.filter(oi =>
            oi.id_interviewee == iw.id_interviewee &&
            ['CALLING', 'DECISION', 'HAPPENING'].includes(oi.state_)
        );

        let statusText = "Ready";
        let statusBg = "var(--color-status--available)";

        if (iwee && !iwee.active) {
            statusText = "Paused";
            statusBg = "var(--color-status--unavailable)";
        } else if (otherActiveInterviews.length > 0) {
            if (otherActiveInterviews.some(oi => oi.state_ === 'HAPPENING')) {
                statusText = "In Interview";
                statusBg = "var(--color-status--happening)";
            } else if (otherActiveInterviews.some(oi => oi.state_ === 'CALLING')) {
                statusText = "Being Called";
                statusBg = "var(--color-status--calling)";
            } else if (otherActiveInterviews.some(oi => oi.state_ === 'DECISION')) {
                statusText = "Checking In";
                statusBg = "var(--color-status--decision)";
            }
        }

        let avatarHtml = '';
        if (iwee && iwee.avatar_url) {
            avatarHtml = `<img src="${iwee.avatar_url}" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid var(--border);">`;
        }

        let mainName = iwee ? (iwee.display_name || iwee.email) : 'Unknown';

        div.innerHTML = `
            <div style="display: flex; align-items: center;">
                ${avatarHtml}
                <div class="comp-queue-item__info">
                    <span class="comp-queue-item__name">
                        ${mainName}
                    </span>
                    <span class="comp-queue-item__meta">Position #${index + 1} • ID #${iw.id_interviewee}</span>
                </div>
            </div>
            <span class="comp-status-tag" style="background:${statusBg}; color:white;">${statusText}</span>
        `;
        queueList.appendChild(div);
    });
}

function update_history_list(completed, interviewees = []) {
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
        const iwee = interviewees.find(i => i.id == iw.id_interviewee);
        const label = iwee
            ? (iwee.display_name || iwee.email || ('Candidate #' + iw.id_interviewee))
            : ('Candidate #' + iw.id_interviewee);

        const div = document.createElement('div');
        div.className = 'comp-history-item';

        let avatarHtml = '';
        if (iwee && iwee.avatar_url) {
            avatarHtml = `<img src="${iwee.avatar_url}" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid var(--border);">`;
        }

        div.innerHTML = `
            <div style="display: flex; align-items: center;">
                ${avatarHtml}
                <div class="comp-history-item__info">
                    <span class="comp-history-item__name">${label}</span>
                    <span class="comp-history-item__meta">ID #${iw.id_interviewee}</span>
                </div>
            </div>
            <span class="comp-status-tag" style="background:var(--color-status--completed); color:white; font-size:0.6rem;">Done</span>
        `;
        historyList.appendChild(div);
    });

    if (completed.length > visibleCount || history_expanded) {
        btnShowMore.style.display = 'block';
        btnShowMore.textContent = history_expanded ? "Show Less" : "Show More (" + (completed.length - visibleCount) + ")";
        btnShowMore.onclick = () => {
            history_expanded = !history_expanded;
            update_history_list(completed, interviewees);
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
