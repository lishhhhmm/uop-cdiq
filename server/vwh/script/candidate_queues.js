/**
 * candidate_queues.js
 *
 * Based on queues.js. Differences:
 *   1. Each company card gets a Join / Leave button for the signed-in candidate.
 *   2. If the candidate is being CALLED at a company, that card is highlighted
 *      and floated to the top of the grid.
 *   3. Dialog (click-to-view queue) works the same as queues.js.
 *
 * PHP must inject window.CANDIDATE_INTERVIEWEE_ID before loading this script.
 */

const no_interviewers_message = document.getElementById('no_interviewers_message');
const container_interviewers = document.getElementById('container_interviewers');

let calling_time_in_seconds = 0;

const el_stat_queues = document.getElementById('stat-queues');
const el_stat_completed = document.getElementById('stat-completed');

// Set by PHP: the interviewee row-id of the signed-in candidate
const CANDIDATE_ID = window.CANDIDATE_INTERVIEWEE_ID;

// Latest update_id from polling data — used in join/leave forms
let latest_update_id = window.CANDIDATE_INITIAL_UPDATE_ID ?? 0;

// Track which interviewer (if any) is actively calling this candidate
let candidate_active_interviewer_id = null;

// ─── Data-model classes (identical to queues.js) ────────────────────────────

class Notifier {
    #observers
    constructor() { this.#observers = []; }
    observerAdd(obs) { if (!(obs instanceof Observer)) throw new TypeError("Must be Observer"); this.#observers.push(obs); obs.observe(this); }
    observerRemove(obs) { let i = this.#observers.indexOf(obs); if (i > -1) this.#observers.splice(i, 1); }
    observerRemoveAll() { let o = this.#observers; this.#observers = []; return o; }
    observerNotify(data) { this.#observers.forEach(o => o.observe(data)); }
}

class Observer {
    observe(data) { throw new Error("Implement observe()"); }
}

class Interviewer extends Notifier {
    #id; #name; #table; #image_url; #active;
    #interviews = []; #interviews_completed = []; #interview_current;

    constructor(row) { super(); this.#id = row['id']; this.update(row); }

    update(row) {
        this.#name = row['name'];
        this.#table = row['table_number'] === '' ? '-' : row['table_number'];
        this.#image_url = row['image_resource_url'];
        this.#active = row['active'];
        this.observerNotify(this);
    }

    updateInterviews(all, current) {
        this.#interviews = all.filter(iw => iw.getState() !== 'COMPLETED');
        this.#interviews_completed = all.filter(iw => iw.getState() === 'COMPLETED');
        this.#interview_current = current;
        this.observerNotify({ notifier: this, reason: 'interviews' });
    }

    observerAdd(obs) { super.observerAdd(obs); obs.observe({ notifier: this, reason: 'interviews' }); }

    getId() { return this.#id; }
    getName() { return this.#name; }
    getTable() { return this.#table; }
    getImageUrl() { return this.#image_url; }
    getActive() { return this.#active; }
    getInterviews() { return Array.from(this.#interviews); }
    getInterviewsCompleted() { return Array.from(this.#interviews_completed); }
    getInterviewCurrent() { return this.#interview_current; }
}

class Interviewee {
    #id; #available;
    constructor(row) { this.#id = row['id']; this.update(row); }
    update(row) { this.#available = row['available']; }
    getId() { return this.#id; }
    getAvailable() { return this.#available; }
}

class Interview {
    #id; #interviewee; #interviewer; #state; #state_timestamp;
    constructor(row) { this.#id = row['id']; this.update(row); }
    update(row) {
        this.#interviewee = row['interviewee'];
        this.#interviewer = row['interviewer'];
        this.#state = row['state_'];
        this.#state_timestamp = Date.parse(row['state_timestamp'] + '+00:00');
    }
    getId() { return this.#id; }
    getInterviewee() { return this.#interviewee; }
    getInterviewer() { return this.#interviewer; }
    getState() { return this.#state; }
    getStateTimestamp() { return this.#state_timestamp; }
}

class ManagementOfObjects {
    #of_class; #entries = {};
    constructor(c) { this.#of_class = c; }
    update(rows, on_add, on_update, on_remove) {
        const to_remove = Object.keys(this.#entries);
        rows.forEach(row => {
            let e = this.#entries[row['id']];
            if (e === undefined) {
                e = this.#entries[row['id']] = new this.#of_class(row);
                if (typeof on_add === 'function') on_add(e);
            } else {
                e.update(row);
                to_remove.splice(to_remove.indexOf(e.getId().toString()), 1);
                if (typeof on_update === 'function') on_update(e);
            }
        });
        to_remove.forEach(key => {
            if (typeof on_remove === 'function') on_remove(this.#entries[key]);
            delete this.#entries[key];
        });
    }
    get(id) { return this.#entries[id]; }
    getAll() { return Object.values(this.#entries); }
}

// ─── Element: main grid card ─────────────────────────────────────────────────

class ElementInterviewerCandidate extends Observer {

    #container; #info_container; #info_img; #info_p;
    #status_indicator; #status_information;
    #action_area; // join / leave button row
    #joined_badge; // joined badge element

    #interviewer;
    #live_time_counter_interval_id;

    constructor() {
        super();

        let e = this.#container = document.createElement('div');
        e.classList.add('interviewer');

        // colour bar at top (same as queues.js)
        e = this.#status_indicator = document.createElement('div');
        e.classList.add('status_indicator');
        this.#container.append(e);

        // info row (logo + name + table)
        e = this.#info_container = document.createElement('div');
        e.classList.add('info');
        this.#info_img = document.createElement('img');
        this.#info_img.classList.add('image');
        this.#info_p = document.createElement('p');
        this.#info_p.classList.add('text');
        this.#info_container.append(this.#info_img, this.#info_p);
        this.#container.append(this.#info_container);

        // status info area
        e = this.#status_information = document.createElement('p');
        e.classList.add('status_information');
        this.#container.append(e);

        // candidate-specific action row
        e = this.#action_area = document.createElement('div');
        e.classList.add('candidate-card-actions');
        this.#container.append(e);

        // joined badge — hidden by default, shown only when in queue
        e = this.#joined_badge = document.createElement('div');
        e.classList.add('joined-badge');
        e.innerHTML = '✓ Joined';
        e.style.display = 'none'; // hidden until _renderActionButton shows it
        this.#container.append(e);
    }

    get() { return this.#container; }
    clearIntervals() { clearInterval(this.#live_time_counter_interval_id); }

    // ─── Observer callback ────────────────────────────────────────────────────
    observe(data) {
        if (data instanceof Interviewer) {
            // Interviewer metadata updated
            const iwer = this.#interviewer = data;
            this.#info_img.src = iwer.getImageUrl();
            this.#info_p.innerHTML = iwer.getName() +
                (iwer.getTable() !== '-' ? `<br><small style="font-weight:400;color:var(--text-secondary);">Table ${iwer.getTable()}</small>` : '');
        }
        else if (data?.notifier instanceof Interviewer && data.reason === 'interviews') {
            this.clearIntervals();

            const iwer = data.notifier;
            const iw = iwer.getInterviewCurrent();

            // ── Status indicator colour bar ──────────────────────────────────
            const si = this.#status_indicator;
            const newClass = (() => {
                if (iw === undefined) return iwer.getActive() ? 'status_indicator--available' : 'status_indicator--paused';
                switch (iw.getState()) {
                    case 'CALLING': return 'status_indicator--calling';
                    case 'DECISION': return 'status_indicator--decision';
                    case 'HAPPENING': return 'status_indicator--happening';
                    default: return 'status_indicator--available';
                }
            })();
            // swap modifier class
            [...si.classList].filter(c => c.startsWith('status_indicator--')).forEach(c => si.classList.remove(c));
            si.classList.add(newClass);

            // ── Status information text (same as queues.js) ──────────────────
            const si2 = this.#status_information;
            if (iw === undefined) {
                si2.innerHTML = iwer.getActive()
                    ? (Math.random() < 0.97 ? 'Available' : '**cricket noises**')
                    : 'Paused';
            } else if (iw.getState() === 'DECISION') {
                si2.innerHTML = `Decision for Candidate <span class="called-number">${iw.getInterviewee().getId()}</span>`;
            } else {
                const renderStatus = () => {
                    switch (iw.getState()) {
                        case 'CALLING': {
                            let rem = iw.getStateTimestamp() + calling_time_in_seconds * 1000 - Date.now();
                            const remStr = formatDuration(rem > 0 ? rem : 0);
                            si2.innerHTML = `Calling Candidate <span class="called-number">${iw.getInterviewee().getId()}</span><br>Remaining: <span>${remStr}</span>`;
                            break;
                        }
                        case 'HAPPENING': {
                            let el = Date.now() - iw.getStateTimestamp();
                            const elStr = formatDuration(el);
                            si2.innerHTML = `Happening with Candidate <span class="called-number">${iw.getInterviewee().getId()}</span><br>Elapsed: <span>${elStr}</span>`;
                            break;
                        }
                    }
                };
                renderStatus();
                this.#live_time_counter_interval_id = setInterval(renderStatus, 500);
            }

            // ── Highlight: is THIS candidate being called here? ───────────────
            const candidateIsActive = iw && CANDIDATE_ID != null && String(iw.getInterviewee().getId()) === String(CANDIDATE_ID) &&
                ['CALLING', 'DECISION', 'HAPPENING'].includes(iw.getState());

            if (candidateIsActive) {
                this.#container.classList.add('interviewer--candidate-calling');
                // Only float to top if this card is in the main grid (not inside a dialog)
                if (this.#container.parentNode === container_interviewers) {
                    container_interviewers.prepend(this.#container);
                }
            } else {
                this.#container.classList.remove('interviewer--candidate-calling');
            }

            // ── Join / Leave button ───────────────────────────────────────────
            this._renderActionButton(iwer, iwer.getInterviews(), iwer.getInterviewsCompleted());
        }
    }

    _renderActionButton(iwer, interviews, completedInterviews = []) {
        const area = this.#action_area;
        area.innerHTML = '';

        // Has the candidate already completed an interview here? Block re-joining.
        const hasCompleted = CANDIDATE_ID != null && completedInterviews.some(iw => String(iw.getInterviewee().getId()) === String(CANDIDATE_ID));

        // Is the candidate currently in this company's active queue?
        const myInterview = CANDIDATE_ID != null && interviews.find(iw => String(iw.getInterviewee().getId()) === String(CANDIDATE_ID));

        // Mark card as joined regardless of exact state — and toggle badge
        if (myInterview) {
            this.#container.classList.add('interviewer--joined');
            this.#joined_badge.style.display = '';
        } else {
            this.#container.classList.remove('interviewer--joined');
            this.#joined_badge.style.display = 'none';
        }

        if (hasCompleted) {
            const note = document.createElement('span');
            note.className = 'queue-locked-note';
            note.textContent = '✅ Interview completed.';
            area.append(note);
            return;
        }

        if (!myInterview) {
            // Show Join button
            const btn = document.createElement('button');
            btn.className = 'queue-btn queue-btn--join';
            btn.textContent = '+ Join Queue';
            btn.style.width = '100%';
            btn.addEventListener('click', () => submitCandidateAction('join', iwer.getId()));
            area.append(btn);
        } else if (myInterview.getState() === 'ENQUEUED') {
            // Show Leave Queue button
            const btn = document.createElement('button');
            btn.className = 'queue-btn queue-btn--leave';
            btn.textContent = 'Leave Queue';
            btn.style.width = '100%';
            btn.style.background = '#e11d48';
            btn.style.color = '#ffffff';
            btn.style.border = 'none';
            btn.style.cursor = 'pointer';
            btn.addEventListener('click', () => {
                if (confirm(`Leave the queue for ${iwer.getName()}?`)) {
                    submitCandidateAction('leave', iwer.getId());
                }
            });
            area.append(btn);
        } else {
            // In queue but can't leave (active state)
            const note = document.createElement('span');
            note.className = 'queue-locked-note';
            note.textContent = 'Cannot leave while interview is active.';
            area.append(note);
        }
    }
}

class EmbededElementInterviewerCandidate extends ElementInterviewerCandidate {
    constructor() { super(); }
    // No join/leave buttons inside the dialog
    _renderActionButton() { }
}

// ─── Dialog (identical to queues.js) ────────────────────────────────────────

class ElementDialogInterviewer extends Observer {

    #interviewer_showing;
    #dialog; #embedded; #el_count; #el_list; #el_done_count; #el_done_list;

    constructor() {
        super();
        this.#dialog = document.body.appendChild(document.createElement('dialog'));
        this.#dialog.classList.add('dialog_details');
        this.#dialog.addEventListener('click', e => {
            const r = this.#dialog.getBoundingClientRect();
            if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
                this.#dialog.close();
            }
        });

        this.#embedded = new EmbededElementInterviewerCandidate();
        this.#dialog.appendChild(this.#embedded.get());

        const makeSection = (label, countRef, listRef) => {
            const wrap = this.#dialog.appendChild(document.createElement('div'));
            wrap.classList.add('quueueue');
            const t = wrap.appendChild(document.createElement('div'));
            t.classList.add('title_with_count');
            t.appendChild(document.createElement('h3')).innerHTML = label;
            const count = t.appendChild(document.createElement('h3'));
            count.classList.add('count');
            const list = wrap.appendChild(document.createElement('div'));
            list.classList.add('horizontal_scrollable');
            return [count, list];
        };

        [this.#el_count, this.#el_list] = makeSection('Enqueued', null, null);
        [this.#el_done_count, this.#el_done_list] = makeSection('Completed', null, null);

        const close = this.#dialog.appendChild(document.createElement('button'));
        close.innerHTML = 'Close';
        close.addEventListener('click', () => this.#dialog.close());

        this.#dialog.addEventListener('close', () => {
            this.#interviewer_showing?.observerRemove(this);
            this.#interviewer_showing?.observerRemove(this.#embedded);
            this.#interviewer_showing = undefined;
            this.#embedded.clearIntervals();
        });
    }

    show_as(interviewer) {
        this.#dialog.close();
        this.#interviewer_showing = interviewer;
        this.#interviewer_showing.observerAdd(this.#embedded);
        this.#interviewer_showing.observerAdd(this);
        this.#dialog.showModal();
    }

    observe(data) {
        if (data?.notifier !== this.#interviewer_showing || data.reason !== 'interviews') return;
        const iwer = this.#interviewer_showing;
        const iw_c = iwer.getInterviewCurrent();

        const make = (interview, done = false) => {
            const iwee = interview.getInterviewee();
            const el = document.createElement('p');
            el.classList.add('interviewee');
            el.textContent = iwee.getId();

            // Highlight this candidate's own entry
            if (String(iwee.getId()) === String(CANDIDATE_ID)) el.classList.add('interviewee--self');

            if (done) { el.classList.add('interviewee--completed'); return el; }
            if (interview === iw_c) {
                el.classList.add({
                    CALLING: 'interviewee--calling', DECISION: 'interviewee--decision', HAPPENING: 'interviewee--happening'
                }[interview.getState()] ?? '');
                return el;
            }
            if (iwee.getAvailable() === true) {
                el.classList.add('interviewee--available');
            } else {
                let active_state = null;
                for (let oi of interviews.getAll()) {
                    if (oi.getInterviewee() === iwee && ['CALLING', 'HAPPENING', 'DECISION'].includes(oi.getState())) {
                        active_state = oi.getState();
                        if (active_state === 'HAPPENING') break;
                    }
                }

                if (active_state) {
                    el.classList.add({
                        CALLING: 'interviewee--calling', DECISION: 'interviewee--decision', HAPPENING: 'interviewee--happening'
                    }[active_state] ?? 'interviewee--unavailable');
                } else {
                    el.classList.add('interviewee--unavailable');
                }
            }
            return el;
        };

        // Exclude HAPPENING — they're in the interview room, not in the queue.
        // CALLING and DECISION stay visible: the candidate is still waiting / being called.
        const isHappening = iw_c && iw_c.getState() === 'HAPPENING';
        const waitingInterviews = isHappening
            ? iwer.getInterviews().filter(iw => iw !== iw_c)
            : iwer.getInterviews();
        this.#el_list.replaceChildren(...waitingInterviews.map(iw => make(iw)));
        this.#el_count.innerHTML = `( ${waitingInterviews.length} )`;
        this.#el_done_list.replaceChildren(...iwer.getInterviewsCompleted().map(iw => make(iw, true)));
        this.#el_done_count.innerHTML = `( ${iwer.getInterviewsCompleted().length} )`;

        // Add Join/Leave button below the lists
        if (!this.#dialog.querySelector('.dialog-action-area')) {
            const actionArea = this.#dialog.appendChild(document.createElement('div'));
            actionArea.classList.add('dialog-action-area');
            actionArea.style.marginTop = '1.5rem';
            actionArea.style.paddingTop = '1rem';
            actionArea.style.borderTop = '1px solid var(--border-subtle)';
        }

        const actionArea = this.#dialog.querySelector('.dialog-action-area');
        actionArea.innerHTML = ''; // Clear previous button

        const hasCompleted = CANDIDATE_ID != null && iwer.getInterviewsCompleted().some(iw => String(iw.getInterviewee().getId()) === String(CANDIDATE_ID));
        const myInterview = CANDIDATE_ID != null && iwer.getInterviews().find(iw => String(iw.getInterviewee().getId()) === String(CANDIDATE_ID));

        if (hasCompleted) {
            const note = document.createElement('p');
            note.style.textAlign = 'center';
            note.style.color = 'var(--text-secondary)';
            note.textContent = '✅ Interview completed.';
            actionArea.append(note);
        } else if (!myInterview) {
            const btn = document.createElement('button');
            btn.className = 'btn-primary';
            btn.textContent = '+ Join Queue';
            btn.style.width = '100%';
            btn.addEventListener('click', () => submitCandidateAction('join', iwer.getId()));
            actionArea.append(btn);
        } else if (myInterview.getState() === 'ENQUEUED') {
            const btn = document.createElement('button');
            btn.className = 'btn-outline-sm';
            btn.textContent = 'Leave Queue';
            btn.style.width = '100%';
            btn.style.borderColor = '#e11d48';
            btn.style.color = '#e11d48';
            btn.addEventListener('click', () => {
                if (confirm(`Leave the queue for ${iwer.getName()}?`)) {
                    submitCandidateAction('leave', iwer.getId());
                }
            });
            actionArea.append(btn);
        } else {
            const note = document.createElement('p');
            note.style.textAlign = 'center';
            note.style.color = 'var(--text-secondary)';
            note.textContent = 'Cannot leave while interview is active.';
            actionArea.append(note);
        }
    }

    get() { return this.#dialog; }
}

// ─── Global instances ────────────────────────────────────────────────────────

const interviewers = new ManagementOfObjects(Interviewer);
const interviewees = new ManagementOfObjects(Interviewee);
const interviews = new ManagementOfObjects(Interview);
const dialog_details = new ElementDialogInterviewer();

// ─── Submit join/leave via hidden form (page reload after redirect) ──────────

function submitCandidateAction(action, interviewer_id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/candidate_update.php';
    const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; form.append(i); };
    add('action', action);
    add('update_id', latest_update_id);
    add('interviewer_id', interviewer_id);
    document.body.append(form);
    form.submit();
}

// ─── Update functions ─────────────────────────────────────────────────────────

function update(data) {
    calling_time_in_seconds = data['calling_time'];
    latest_update_id = data['update'] ?? latest_update_id;

    update_interviewers(data['interviewers']);
    update_interviewees(data['interviewees']);
    update_interviews(data['interviews'], data['interviews_current']);

    const hasAny = container_interviewers.querySelectorAll('.interviewer').length > 0;
    no_interviewers_message.style.display = hasAny ? 'none' : '';

    // Update live stats
    let activeQueues = 0, completedCount = 0;
    interviewers.getAll().forEach(iwer => {
        if (CANDIDATE_ID != null && iwer.getInterviews().some(iw => String(iw.getInterviewee().getId()) === String(CANDIDATE_ID))) activeQueues++;
        if (CANDIDATE_ID != null && iwer.getInterviewsCompleted().some(iw => String(iw.getInterviewee().getId()) === String(CANDIDATE_ID))) completedCount++;
    });
    if (el_stat_queues) el_stat_queues.textContent = activeQueues;
    if (el_stat_completed) el_stat_completed.textContent = completedCount;

    // ── Sort Company Cards ──
    // 1. Calling/Decision/Happening with THIS candidate
    // 2. Joined Queues (waiting in line)
    // 3. Unjoined Queues (available to join)
    // 4. Completed Queues
    if (hasAny) {
        const cards = Array.from(container_interviewers.querySelectorAll('.interviewer'));
        cards.sort((a, b) => {
            // Find the Interviewer object for each DOM element.
            // (We match by looking at the company name in the DOM or tracking it.
            // A safer way is to use the globally accessible interviewers array and find
            // which element belongs to which interviewer by checking if the element matches the one created by the observer.
            // However, the ElementInterviewerCandidate observer directly mutates the DOM elements we're looking at.)

            // Fortunately, we added CSS classes that represent these exact states!
            const getRank = (el) => {
                if (el.classList.contains('interviewer--candidate-calling')) return 1;

                // If it has a completed note inside the action area, it's completed
                const actionArea = el.querySelector('.candidate-card-actions');
                const isCompleted = actionArea && actionArea.textContent.includes('Interview completed');
                if (isCompleted) return 4;

                if (el.classList.contains('interviewer--joined')) return 2;

                // Unjoined
                return 3;
            };

            const rankA = getRank(a);
            const rankB = getRank(b);

            if (rankA !== rankB) {
                return rankA - rankB; // Lower rank comes first
            }

            // Fallback: alphabetical sort by company name
            const nameA = a.querySelector('.text')?.textContent?.toLowerCase() || '';
            const nameB = b.querySelector('.text')?.textContent?.toLowerCase() || '';
            return nameA.localeCompare(nameB);
        });

        // Re-append in sorted order
        cards.forEach(card => container_interviewers.appendChild(card));
    }
}

function update_interviewers(rows) {
    interviewers.update(rows,
        (iwer) => {
            const ei = new ElementInterviewerCandidate();
            iwer.observerAdd(ei);
            ei.get().addEventListener('click', e => {
                // Only open dialog if not clicking a button
                if (!e.target.closest('button')) dialog_details.show_as(iwer);
            });
            container_interviewers.appendChild(ei.get());
        },
        undefined,
        (iwer) => {
            iwer.observerRemoveAll().forEach(obs => {
                if (obs instanceof EmbededElementInterviewerCandidate) return;
                if (obs instanceof ElementInterviewerCandidate) obs.get().parentElement?.removeChild(obs.get());
                if (obs instanceof ElementDialogInterviewer) obs.get().close();
            });
        }
    );
}

function update_interviewees(rows) { interviewees.update(rows); }

function update_interviews(rows, rows_current) {
    rows.forEach(row => {
        row['interviewer'] = interviewers.get(row['id_interviewer']);
        row['interviewee'] = interviewees.get(row['id_interviewee']);
    });

    const per_iwer = {};
    interviewers.getAll().forEach(iwer => { per_iwer[iwer.getId()] = []; });

    const on_add_or_update = iw => {
        const iwer = iw.getInterviewer();
        per_iwer[iwer.getId()].push(iw);
    };

    interviews.update(rows, on_add_or_update, on_add_or_update, undefined);

    const current_map = {};
    rows_current.forEach(row => {
        const iw = interviews.get(row['id']);
        const iwer = iw.getInterviewer();
        current_map[iwer.getId()] = iw;
    });

    interviewers.getAll().forEach(iwer => {
        iwer.updateInterviews(per_iwer[iwer.getId()], current_map[iwer.getId()]);
    });
}

// ─── Bootstrap ───────────────────────────────────────────────────────────────

short_polling(2, 'queues', data => update(data));

// ─── CV Preview & Update ───────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const elBtnViewCv = document.getElementById('btn-view-cv');
    const elBtnChangeCv = document.getElementById('btn-change-cv');
    const elBtnUploadCv = document.getElementById('btn-upload-cv');
    const elCvInput = document.getElementById('cv-upload-input');
    const elDialogCv = document.getElementById('dialog_cv_preview');
    const elCvIframe = document.getElementById('cv_iframe');
    const elCvExternal = document.getElementById('btn_cv_external');

    if (elBtnViewCv) {
        elBtnViewCv.addEventListener('click', (e) => {
            e.preventDefault();
            elCvIframe.src = elBtnViewCv.href;
            elCvExternal.href = elBtnViewCv.href;
            elDialogCv.showModal();
        });
    }

    const elBtnTogglePause = document.getElementById('btn-toggle-pause');
    if (elBtnTogglePause) {
        elBtnTogglePause.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('action', 'toggle_pause');
            formData.append('update_id', latest_update_id);

            elBtnTogglePause.disabled = true;
            fetch('/candidate_update.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Could not toggle state: ' + (data.error || 'Unknown error'));
                        elBtnTogglePause.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred.');
                    elBtnTogglePause.disabled = false;
                });
        });
    }

    if (elBtnChangeCv) {
        elBtnChangeCv.addEventListener('click', () => elCvInput.click());
    }
    if (elBtnUploadCv) {
        elBtnUploadCv.addEventListener('click', () => elCvInput.click());
    }

    const elBtnEditProfile = document.getElementById('btn-edit-profile');
    const elDialogEditProfile = document.getElementById('dialog_edit_profile');
    if (elBtnEditProfile && elDialogEditProfile) {
        elBtnEditProfile.addEventListener('click', () => {
            elDialogEditProfile.showModal();
        });
    }

    if (elCvInput) {
        elCvInput.addEventListener('change', () => {
            if (elCvInput.files.length === 0) return;

            const file = elCvInput.files[0];
            if (file.type !== 'application/pdf') {
                alert('Please upload a PDF file.');
                elCvInput.value = '';
                return;
            }
            if (file.size > 1048576) {
                alert('CV file size must not exceed 1 MB limit.');
                elCvInput.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_cv');
            formData.append('update_id', latest_update_id);
            formData.append('cv', file);

            elCvInput.disabled = true;
            if (elBtnChangeCv) elBtnChangeCv.textContent = 'Uploading...';
            if (elBtnUploadCv) elBtnUploadCv.textContent = 'Uploading...';

            fetch('/candidate_update.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload to update the "View CV" link and session
                    } else {
                        alert('Upload failed: ' + (data.error || 'Unknown error'));
                        elCvInput.disabled = false;
                        if (elBtnChangeCv) elBtnChangeCv.textContent = '🔄 Change CV';
                        if (elBtnUploadCv) elBtnUploadCv.textContent = '📄 Upload CV';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred during upload.');
                    elCvInput.disabled = false;
                    if (elBtnChangeCv) elBtnChangeCv.textContent = '🔄 Change CV';
                    if (elBtnUploadCv) elBtnUploadCv.textContent = '📄 Upload CV';
                });
        });
    }

    // CV upload from the Edit Profile dialog
    const elCvInputProfile = document.getElementById('cv-upload-input-profile');
    const elCvProfileStatus = document.getElementById('cv-upload-profile-status');
    if (elCvInputProfile) {
        elCvInputProfile.addEventListener('change', () => {
            if (elCvInputProfile.files.length === 0) return;

            const file = elCvInputProfile.files[0];
            if (file.type !== 'application/pdf') {
                elCvProfileStatus.textContent = '❌ Please select a PDF file.';
                elCvInputProfile.value = '';
                return;
            }
            if (file.size > 1048576) {
                elCvProfileStatus.textContent = '❌ CV file size must not exceed 1 MB.';
                elCvInputProfile.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_cv');
            formData.append('update_id', latest_update_id);
            formData.append('cv', file);

            elCvInputProfile.disabled = true;
            elCvProfileStatus.textContent = '⏳ Uploading...';

            fetch('/candidate_update.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        elCvProfileStatus.textContent = '✅ CV uploaded! Reloading...';
                        setTimeout(() => location.reload(), 800);
                    } else {
                        elCvProfileStatus.textContent = '❌ Upload failed: ' + (data.error || 'Unknown error');
                        elCvInputProfile.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    elCvProfileStatus.textContent = '❌ An error occurred during upload.';
                    elCvInputProfile.disabled = false;
                });
        });
    }
});
