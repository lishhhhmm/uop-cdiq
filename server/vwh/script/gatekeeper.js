const dialog = document.getElementById('dialog_action');
const form = document.getElementById("dialog_action_form");
const form_input_interview_id = document.getElementById("input_interview_id");
const form_input_interviewer_id = document.getElementById("input_interviewer_id");

const form_button_to_happening = document.getElementById("button_to_happening");
const form_button_to_completed = document.getElementById("button_to_completed");
const form_button_to_completed_and_pause = document.getElementById("button_to_completed_and_pause");
const form_button_to_dequeue = document.getElementById("button_to_dequeue");
const form_button_active_inactive = document.getElementById("button_active_inactive");
const form_button_cancel = document.getElementById("button_cancel");

// Queue info elements
const dialog_action_company = document.getElementById("dialog_action_company");
const dialog_action_status = document.getElementById("dialog_action_status");
const dialog_action_student = document.getElementById("dialog_action_student");
const dialog_queue_list = document.getElementById("dialog_queue_list");
const dialog_history_list = document.getElementById("dialog_history_list");
const dialog_history_container = document.getElementById("dialog_history_container");

// Pause confirmation dialog
const dialog_pause_confirm = document.getElementById("dialog_pause_confirm");
const btn_complete_and_pause = document.getElementById("btn_complete_and_pause");

const container_interviewers = document.getElementById("container_interviewers");

let calling_time_in_seconds = 0;
let all_interviews_data = []; // Store all interviews for queue display
let current_interview_happening = false; // Track if interview is happening for pause logic

const interviewers = {};
const interviewees = {};

function getCandidateExactStateClass(iwee_id) {
    let active_int = all_interviews_data.find(row =>
        row['id_interviewee'] == iwee_id &&
        ['CALLING', 'HAPPENING', 'DECISION'].includes(row['state_'])
    );

    if (active_int) {
        if (active_int['state_'] === 'CALLING') return 'interviewee--calling';
        if (active_int['state_'] === 'HAPPENING') return 'interviewee--happening';
        if (active_int['state_'] === 'DECISION') return 'interviewee--decision';
    }

    let iwee = interviewees[iwee_id];
    if (iwee && iwee.available === false) {
        return 'interviewee--unavailable';
    }
    return 'interviewee--available';
}

class Interview {

    #id;
    #interviewee_id;
    #interviewer_id;
    #state;
    #state_timestamp;

    constructor(row) {
        this.#id = row['id'];
        this.#interviewee_id = row['id_interviewee'];
        this.#interviewer_id = row['id_interviewer'];
        this.#state = row['state_'];
        this.#state_timestamp = Date.parse(row['state_timestamp'] + '+00:00');
    }

    getId() {
        return this.#id;
    }

    getIntervieweeId() {
        return this.#interviewee_id;
    }

    getInterviewerId() {
        return this.#interviewer_id;
    }

    getState() {
        return this.#state;
    }

    getStateTimestamp() {
        return this.#state_timestamp;
    }

}

class Interviewer {

    static noInterview = new Interview({});

    static isNoInterview(interview) {
        return this.noInterview === interview;
    }

    #id = undefined;
    #name = undefined;
    #table = undefined;
    #image_url = undefined;
    #active = undefined;

    #interview;

    #observers = [];

    constructor(row) {
        this.#id = row['id'];
        this.update(row);
        this.updateInterview();
    }

    /**
     * Has to be manually called. Dereferences all outgoing references.
     * @returns previously added observers
     */
    destructor() {
        this.#id = undefined;
        this.#name = undefined;
        this.#table = undefined;
        this.#image_url = undefined;
        this.#active = undefined;

        this.#interview = undefined;

        let observers = this.#observers;
        this.#observers = undefined;

        return observers;
    }

    // ===

    observerAdd(obs) {
        if (typeof (obs.notify) !== 'function') {
            throw new TypeError('Expecting \'notify\' function on the \'obs\' parameter')
        }

        this.#observers.push(obs);
        obs.notify(this);
        obs.notify(this.#interview);
    }

    observerRemove(obs) {
        let index = this.#observers.indexOf(obs);
        if (index > -1) {
            this.#observers.splice(index, 1);
        }
    }

    #observersNotify(data) {
        this.#observers.forEach((obs) => {
            if (typeof (obs.notify) === 'function') {
                obs.notify(data);
            }
        })
    }

    // ===

    update(row) {
        this.#name = row['name'];
        this.#table = row['table_number'] === '' ? '-' : row['table_number'];
        this.#image_url = row['image_resource_url'];
        this.#active = row['active'];

        this.#observersNotify(this);
    }

    updateInterview(interview = undefined) {
        if (interview === undefined) {
            this.#interview = Interviewer.noInterview;
        }
        else {
            if (interview instanceof Interview === false) {
                throw new TypeError('Parameter must of be object of class \'Interview\'');
            }

            this.#interview = interview;
        }

        this.#observersNotify(this.#interview);
    }

    // ===

    getId() {
        return this.#id;
    }

    getName() {
        return this.#name;
    }

    getTable() {
        return this.#table;
    }

    getImageUrl() {
        return this.#image_url;
    }

    getActive() {
        return this.#active;
    }

    getInterview() {
        return this.#interview;
    }

}

class InterviewerElement {

    #container;
    #info_container;
    #info_img;
    #info_p;
    #status_indicator;
    #status_information;

    #interviewer;

    #live_time_counter_interval_id;

    constructor() {
        let e = this.#container = document.createElement('div');
        e.classList.add('interviewer');

        e = this.#info_container = document.createElement('div');
        e.classList.add('info');
        e = this.#info_img = document.createElement('img');
        e.classList.add('image');
        e = this.#info_p = document.createElement('p');
        e.classList.add('text');

        this.#info_container.append(
            this.#info_img,
            this.#info_p
        );

        e = this.#status_indicator = document.createElement('div');
        e.classList.add('status_indicator');
        e = this.#status_information = document.createElement('p');
        e.classList.add('status_information');

        this.#container.append(
            this.#info_container,
            this.#status_indicator,
            this.#status_information
        );
    }

    get() {
        return this.#container;
    }

    // ===

    notify(data) {
        if (data instanceof Interviewer === true) {
            let iwer = this.#interviewer = data;

            this.#info_img.src = iwer.getImageUrl();
            this.#info_p.innerHTML =
                iwer.getName() + " " +
                "<br>Table: " + iwer.getTable();
        }
        else if (data instanceof Interview === true) {
            clearInterval(this.#live_time_counter_interval_id);

            let iw = data;

            if (Interviewer.isNoInterview(iw)) {
                if (this.#interviewer.getActive() === true) {
                    this.#status_information.innerHTML =
                        Math.random() < 0.97 ? "Available" : "**cricket noises**";
                    this.#status_indicator.classList.add('status_indicator--available');
                }
                else {
                    this.#status_information.innerHTML = "Paused";
                    this.#status_indicator.classList.add('status_indicator--paused');
                }
            }
            else {
                if (iw.getState() === 'DECISION') {
                    this.#status_indicator.classList.add('status_indicator--decision');
                    this.#status_information.innerHTML =
                        'Decision for Candidate ' +
                        iw.getIntervieweeId()
                        ;
                }
                else {
                    let f = () => {
                        switch (iw.getState()) {
                            case 'CALLING':
                                let remaining = iw.getStateTimestamp() + (calling_time_in_seconds * 1000) - Date.now();

                                remaining = formatDuration(remaining > 0 ? remaining : 0);

                                // ---

                                this.#status_indicator.classList.add('status_indicator--calling');
                                this.#status_information.innerHTML =
                                    'Calling Candidate ' +
                                    iw.getIntervieweeId() +
                                    '<br>Remaining: <span>' +
                                    remaining +
                                    '</span>';
                                break;
                            case 'HAPPENING':
                                let elapsed = Date.now() - iw.getStateTimestamp();

                                elapsed = formatDuration(elapsed);

                                // ---

                                this.#status_indicator.classList.add('status_indicator--happening');
                                this.#status_information.innerHTML =
                                    'Happening with Candidate ' +
                                    iw.getIntervieweeId() +
                                    '<br>Elapsed: <span>' +
                                    elapsed +
                                    '</span>';
                                break;

                            default: /* should not come here */ return;
                        }
                    };

                    f();

                    this.#live_time_counter_interval_id = setInterval(f, 500);
                }

            }

            if (this.#status_indicator.classList.length === 3) { // (0) base + (1) old + (2) new
                this.#status_indicator.classList.remove(
                    this.#status_indicator.classList.item(1)
                );
            }
        }
        else {
            console.log("Haha???");
        }
    }

}

form.addEventListener("submit", (event) => {

    if (event.submitter === form_button_cancel) {
        form.reset();
        return;
    }

    // Check if trying to pause during HAPPENING - show confirmation dialog instead
    if (event.submitter === form_button_active_inactive && current_interview_happening) {
        event.preventDefault();
        dialog_pause_confirm.showModal();
        return;
    }

    let confirm_message = (() => {
        let iwer = interviewers[form_input_interviewer_id.value];
        let iwee_id = iwer.getInterview().getIntervieweeId();

        switch (event.submitter) {
            case form_button_to_happening:
                return "moving interviewer '"
                    + iwer.getName() + "' to interview happening with candidate '"
                    + iwee_id + "'";

            case form_button_to_completed:
                return "completing interview with interviewer '"
                    + iwer.getName() + "' and candidate '"
                    + iwee_id + "'";

            case form_button_to_completed_and_pause:
                return "completing interview and PAUSING interviewer '"
                    + iwer.getName() + "'";

            case form_button_to_dequeue:
                return "removing this interview. Candidate " +
                    iwee_id + " will be able to enqueue again via the Secretary at Interviewer '" +
                    iwer.getName() + "'";

            case form_button_active_inactive:
                $s = '';

                if (iwer.getActive() === true) {
                    $s = "pausing Interviewer '" + iwer.getName() + "'.";

                    if (Interviewer.isNoInterview(iwer.getInterview()) === false) {
                        $s += " Candidate " + iwee_id +
                            " will stay ENQUEUED (like it never started CALLING) on the Interviewer.'";
                    }
                }
                else {
                    $s = "unpausing Interviewer '" + iwer.getName() + "'.";
                }

                return $s;
        }

        return null;
    })();

    submiting(form, confirm_message, () => {
        dialog.close();
        form.reset();
    }, event);
});

// Complete & Pause button handler
btn_complete_and_pause.addEventListener('click', () => {
    dialog_pause_confirm.close();
    // Trigger the combined complete & pause action
    form_button_to_completed_and_pause.click();
});

// Click outside to close for dialogs is handled in HTML via onclick

// ===

function interviewer_element_dialog_form_preperation(iwer) {
    form_input_interviewer_id.value = iwer.getId();

    // Populate company name
    dialog_action_company.textContent = iwer.getName();

    form_button_active_inactive.innerText =
        iwer.getActive() === true ? '⏸ Pause' : '▶ Resume';
    form_button_active_inactive.disabled = false;

    display(false, [
        form_button_to_dequeue,
        form_button_to_happening,
        form_button_to_completed
    ]);

    let iw = iwer.getInterview();

    // Get queue for this interviewer (only ENQUEUED, not current)
    let queue = all_interviews_data.filter(row =>
        row['id_interviewer'] == iwer.getId() &&
        row['state_'] === 'ENQUEUED'
    );

    if (queue.length > 0) {
        dialog_queue_list.innerHTML = queue.map(row => '<span class="called-number ' + getCandidateExactStateClass(row['id_interviewee']) + '">' + row['id_interviewee'] + '</span>').join(' ');
    } else {
        dialog_queue_list.innerHTML = '<span style="color: var(--text-secondary); opacity: 0.7;">No one waiting</span>';
    }

    // Get history (recently completed) - limit to last 6, latest first
    let history = all_interviews_data.filter(row =>
        row['id_interviewer'] == iwer.getId() &&
        row['state_'] === 'COMPLETED'
    ).sort((a, b) => b.id - a.id).slice(0, 6);

    if (history.length > 0) {
        dialog_history_list.innerHTML = history.map(row => '<span class="called-number" style="opacity: 0.6; font-size: 0.85rem; margin-right: 4px;">' + row['id_interviewee'] + '</span>').join('');
        dialog_history_container.style.display = 'block';
    } else {
        dialog_history_container.style.display = 'none';
    }

    if (Interviewer.isNoInterview(iw) === false) {
        form_input_interview_id.value = iw.getId();

        let iw_state = iw.getState();

        // Show status prominently
        dialog_action_status.textContent = iw_state;
        dialog_action_status.style.color = iw_state === 'HAPPENING' ? 'var(--accent-danger)' :
            iw_state === 'CALLING' ? 'var(--accent-success)' :
                iw_state === 'DECISION' ? 'var(--accent-warning)' : 'var(--accent-primary)';

        // Show current student
        dialog_action_student.innerHTML = 'Candidate <span class="called-number">' + iw.getIntervieweeId() + '</span>';

        // Track if happening for pause logic
        current_interview_happening = (iw_state === 'HAPPENING');

        if (iw_state === 'CALLING' || iw_state === 'DECISION') {
            display(true, [
                form_button_to_dequeue,
                form_button_to_happening
            ]);
        }
        else if (iw_state === 'HAPPENING') {
            display(true, [
                form_button_to_dequeue,
                form_button_to_completed
            ]);
        }
        else {
            // ??? should not be here in the first place
        }
    } else {
        dialog_action_status.textContent = iwer.getActive() ? 'Available' : 'Paused';
        dialog_action_status.style.color = iwer.getActive() ? 'var(--accent-success)' : 'var(--text-secondary)';
        dialog_action_student.textContent = 'No active interview';
        current_interview_happening = false;
    }
}

function update(data) {
    calling_time_in_seconds = data['calling_time'];
    all_interviews_data = data['all_interviews'] || []; // Use all_interviews for queue display

    update_interviewers(data['interviewers']);
    update_interviewees(data['interviewees']);
    update_interviews(data['interviews']);

    if (interviewers[form_input_interviewer_id.value] !== undefined) {
        let iwer = interviewers[form_input_interviewer_id.value];

        interviewer_element_dialog_form_preperation(iwer);
    }
    else {
        dialog.close();
        form.reset();
    }

    display(
        container_interviewers.childElementCount === 1, // TODO eh lazy solution
        [document.getElementById('no_interviewers_message')]
    );
}

function update_interviewers(rows) {
    let interviewer_ids_to_delete = Object.keys(interviewers);

    rows.forEach((row) => {

        let interviewer = interviewers[row['id']];

        if (interviewer === undefined) {
            interviewer = interviewers[row['id']] =
                new Interviewer(row);

            ie = new InterviewerElement();
            interviewer.observerAdd(ie);
            ie.get().addEventListener('click', (event) => {
                interviewer_element_dialog_form_preperation(interviewer);
                dialog.showModal();
            });

            container_interviewers.appendChild(ie.get());
        }
        else {
            interviewer.update(row);

            interviewer_ids_to_delete.splice(interviewer_ids_to_delete.indexOf(interviewer.getId().toString()), 1);
        }
    });

    interviewer_ids_to_delete.forEach((id) => {
        let elements = interviewers[id].destructor();
        delete interviewers[id];

        elements.forEach((e) => {
            if (e instanceof InterviewerElement === true) {
                e.get().parentElement.removeChild(e.get());
            }
        });
    });
}

function update_interviewees(rows) {
    if (!rows) return;
    rows.forEach(row => {
        interviewees[row['id']] = { available: row['available'] };
    });
}

function update_interviews(rows) {
    let interviewer_ids_without_interview = Object.keys(interviewers);

    rows.forEach(row => {
        let iwer = interviewers[row['id_interviewer']];

        iwer.updateInterview(new Interview(row));
        interviewer_ids_without_interview.splice(interviewer_ids_without_interview.indexOf(iwer.getId().toString()), 1);
    });

    interviewer_ids_without_interview.forEach((id) => {
        interviewers[id].updateInterview(); // default undefined
    });
}
