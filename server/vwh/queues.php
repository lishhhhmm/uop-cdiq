<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Interviews');

$a->body_main = function () { ?>

	<dialog id="dialog-asd9uih" class="info-dialog" onclick="if(event.target===this)this.close();">
		<button class="dialog-close" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
		<p>Here you can see each interviewer and the current state of their interview.</p>
		<p>Interview states:</p>
		<ul>
			<li><span class='av'>Available</span>: The interviewer is free. Either no candidates are waiting, or the waiting
				candidates are currently in other interviews.</li>
			<li><span class='ca'>Calling</span>:A candidate is being called. If this is your number, go to the Gatekeeper to
				start your interview.</li>
			<li><span class='de'>Decision</span>: The calling period has ended. The Gatekeeper decides whether the candidate
				arrived on time. If this is your number and you have not arrived, you are considered late (you will be
				removed from the queue).</li>
			<li><span class='ha'>Happening</span>: The candidate arrived on time and the interview is in progress.</li>
			<li><span class='pa'>Paused</span>: The interviewer is temporarily unavailable and cannot conduct interviews.
			</li>
		</ul>

		<hr>
		<p>Interview queues:</p>
		<p>Click on any company to see the list of candidates waiting for that interviewer.</p>
		<p>When an interview is in Calling, Decision, or Happening, the active candidate will be highlighted with the same
			color as the interview state.</p>
		<p>All other candidates in the queue will be:
		<ul>
			<li><span class='av'>Available</span>: when the candidate is <strong>not</strong> in an other interview.</li>
			<li><span class='pa'>Unavailable</span>: when the candidate is either in another interview (at any state:
				Calling, Decision,Happening) or got paused by the Secretary.</li>
		</ul>
		<p>When the system selects the next candidate to call, it always chooses the <strong>leftmost available</strong>
			candidate in the queue.</p>

		<hr>

		<p>No page refresh is needed, updates appear automatically.</p>

		<hr>

		<button onclick="document.getElementById('dialog-asd9uih').close();">Got it!</button>
	</dialog>

	<div id="as8u9dji" class="horizontal_buttons">
		<button id="9a8sdfuh" onclick="document.getElementById('dialog-asd9uih').showModal();
			document.getElementById('dialog-asd9uih').scrollTo(0,0);">What is this place?</button>
		<button onclick="document.getElementById('as8u9dji').style.display = 'none';">Hide</button>
	</div>

	<div id="container_interviewers" class="container_interviewers">
		<p id="no_interviewers_message">No Interviewers in the system.</p>
	</div>

	<script src="/script/utilities.js"></script>
	<script src="/script/short_polling.js"></script>
	<script src="/script/queues.js"></script>
	<script>
		// TODO move it back to 5 seconds
		short_polling(2 /* seconds */, /* for */ 'queues', /* to retrieve */(data) => {
			update(data);
		});
	</script>
<?php };

$a->assemble();
