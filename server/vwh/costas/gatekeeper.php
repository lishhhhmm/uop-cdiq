<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Gatekeeper');

$a->operator_ensure(Operator::Gatekeeper);

$a->custom_nav = [
	'Home' => '/',
	'Secretary' => '/costas/secretary.php',
	'Logout' => '/costas/vasilakis.php?unauthorize'
];

$a->body_main = function () { ?>

	<dialog id="dialog-asd9uih" class="info-dialog" onclick="if(event.target===this)this.close();">
		<button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
		<p><strong>Click on an interviewer</strong> to manage their current interview.</p>
		<p>Pay close attention when an interview is in Decision, as well as during Calling or Happening.</p>
		<p>Available actions:</p>
		<ul>
			<li><strong>Arrived</strong>: Available when an interview is in Calling or Decision. Moves the interview to
				Happening, meaning the candidate has arrived and the interview begins.</li>
			<li><strong>Completed</strong>: Ends the interview successfully.The candidate leaves and the interviewer becomes
				available again.</li>
			<li><strong>Didn't Show Up</strong>: Cancels the interview. The candidate is removed from this interview and may
				re-enqueue with the same interviewer via the Secretary.</li>
			<li><strong>Pause / Resume</strong>: Moves the interviewer to Unavailable from any state (Available, Calling,
				Decision, Happening). If paused during Calling, or Decision, the candidate is returned to the queue. If
				interview is in Happening, then it cannot be paused, only complete and pause which is an option that will
				pop up only if pause is pressed. When paused the interviewer will not call the next candidate until resumed.
				When resumed, the interviewer continues normally from the queue</li>
		</ul>

		<button onclick="document.getElementById('dialog-asd9uih').close();">Got it!</button>
	</dialog>

	<div id="as8u9dji" class="horizontal_buttons">
		<button id="9a8sdfuh" onclick="document.getElementById('dialog-asd9uih').showModal();
			document.getElementById('dialog-asd9uih').scrollTo(0,0);">Information</button>
		<button onclick="document.getElementById('as8u9dji').style.display = 'none';">Hide</button>
	</div>

	<div id="container_interviewers" class="container_interviewers">
		<p id="no_interviewers_message">No Interviewers in the system.</p>
	</div>

	<dialog id="dialog_action" onclick="if(event.target===this)this.close();">
		<form id="dialog_action_form" method="dialog" style="display: flex; flex-direction: column; gap: 0.75rem;">
			<input id="input_interview_id" name="input_interview_id" type="text" value="null" hidden>
			<input id="input_interviewer_id" name="input_interviewer_id" type="text" value="null" hidden>

			<div id="dialog_action_header" style="text-align: center;">
				<p id="dialog_action_company" style="margin: 0; font-weight: 700; font-size: 1.1rem;"></p>
				<p id="dialog_action_status" style="margin: 0.25rem 0 0 0; color: var(--accent-primary); font-weight: 600;">
				</p>
				<p id="dialog_action_student" style="margin: 0.25rem 0 0 0; color: var(--text-secondary);"></p>
			</div>

			<div id="dialog_queue_info"
				style="background: var(--bg-secondary); border-radius: var(--radius-md); padding: 0.75rem; margin: 0.5rem 0;">
				<p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: var(--text-secondary);">Queue:</p>
				<p id="dialog_queue_list" style="margin: 0; line-height: 1.8;"></p>
				<div id="dialog_history_container"
					style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border);">
					<p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: var(--text-secondary);">History (Recently
						Completed):</p>
					<p id="dialog_history_list" style="margin: 0; line-height: 1.8;"></p>
				</div>
			</div>

			<button id="button_to_happening" name="button_to_happening" type="submit" style="width: 100%;">✓
				Arrived</button>
			<button id="button_to_completed" name="button_to_completed" type="submit" style="width: 100%;">✓
				Completed</button>
			<button id="button_to_completed_and_pause" name="button_to_completed_and_pause" type="submit"
				style="display: none;"></button>
			<button id="button_to_dequeue" name="button_to_dequeue" type="submit" style="width: 100%;">✗ Didn't Show
				Up</button>
			<hr>
			<button id="button_active_inactive" name="button_active_inactive" type="submit" style="width: 100%;">⏸ Pause /
				Resume</button>
			<hr>
			<button id="button_cancel" name="button_cancel" type="submit" style="width: 100%;" autofocus>Close</button>
		</form>
	</dialog>

	<!-- Pause Confirmation Dialog -->
	<dialog id="dialog_pause_confirm" onclick="if(event.target===this)this.close();">
		<div style="display: flex; flex-direction: column; gap: 1rem; text-align: center;">
			<p style="margin: 0; font-weight: 600; color: var(--accent-warning);">⚠️ Interview In Progress</p>
			<p style="margin: 0; color: var(--text-secondary);">An interview is currently happening. You must complete or
				cancel it before pausing.</p>
			<div style="display: flex; gap: 0.5rem;">
				<button type="button" id="btn_complete_and_pause"
					style="flex: 1; background: var(--color-status--calling); color: #ffffff;">✓ Complete & Pause</button>
				<button type="button" onclick="document.getElementById('dialog_pause_confirm').close();"
					style="flex: 1;">Cancel</button>
			</div>
		</div>
	</dialog>

<?php };

$a->assemble();

?>
<script src="/script/utilities.js"></script>
<script src="/script/short_polling.js"></script>
<script src="/script/submit.js"></script>
<script src="/script/gatekeeper.js"></script>
<script>
	short_polling(2 /* seconds */, /* for */ 'gatekeeper', /* to retrieve */(data) => {
		update(data);
	});
</script>