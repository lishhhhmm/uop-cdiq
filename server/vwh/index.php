<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Career Fair 2026 Interview Hub');

$a->body_main_id = 'index-main';

$a->body_main = function () { ?>
	<h2>What is this platform?</h2>
	<p>This platform shows all interviews during Career Fair 2026 in real time.<br>
		You can see which interviews are happening now, the interview queues for each company, and the current status of
		every interview — all in one place.</p>

	<h2>How does calling work?</h2>
	<p>When you register, you receive a number.<br>
		<strong>You will be called by this number, not by name.</strong>
	</p>

	<p>When your number is called: Go to the Gatekeeper and you will be guided to your interview room</p>

	<h2>Where do I book interviews?</h2>
	<p>Book your interview at the Secretary desk, then watch the interview updates on this platform.</p>

	<p>This platform can also suggest positions and companies that best match your profile, helping you make the most of
		the Career Fair experience.</p>

	<p><strong>Navigate the menu to view Interviews, Companies or suggestions</strong></p>

	<hr>

	<h2>Share</h2>
	<p>Use the QR code</p>
	<img id="current_url_qr" alt="Generating QR code..." style="max-width: 200px; margin: 0.5rem 0;">
	<script src="/script/utilities.js"></script>
	<script>qr_generate(window.location.href, document.getElementById('current_url_qr'));</script>
	<p>—or—<br>
		<a target="_self" onclick="copy_to_clipboard(window.location.href)"
			style="cursor: pointer; color: var(--brand-maroon); font-weight: 600;">Copy the link and share it</a>
	</p>
<?php };

$a->assemble();
