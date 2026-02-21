<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/candidate_auth.php';

$a = new Assembler('Career Fair 2026 Interview Hub');

$a->body_main_id = 'index-main';

$a->body_main = function () {
	$profile = candidate_session_get();
	?>
	<div class="home-grid animate-fade-in">
		<!-- ── Section 1: The Platform ── -->
		<div class="home-card glass">
			<div class="home-card__icon">🎯</div>
			<div class="home-card__content">
				<h3>The Platform</h3>
				<p>Track all interviews during <strong>Career Fair 2026</strong> in real time. See live company queues and
					current
					interview statuses all in one place.</p>
			</div>
		</div>

		<!-- ── Section 2: How it Works ── -->
		<div class="home-card glass">
			<div class="home-card__icon">📢</div>
			<div class="home-card__content">
				<h3>How it Works</h3>
				<p>When you register, you receive a <strong>Registration Number</strong>. You will be called by this number,
					not by name.
				</p>
				<div class="home-card__highlight">
					When called: Go to the <strong>Gatekeeper</strong> to be guided to your interview table.
				</div>
			</div>
		</div>

		<!-- ── Section 3: Register ── -->
		<div class="home-card glass">
			<div class="home-card__icon">📝</div>
			<div class="home-card__content">
				<h3>Register</h3>
				<p>Register online with your <strong>@go.uop.gr</strong> account,<br>or Register at the
					<strong>Secretary desk</strong> (all
					emails allowed here), then watch the live updates here.
				</p>
				<p style="margin-top: 1rem;">Use the <strong>Suggestions</strong> tab to find companies and positions that
					best match your profile.
				</p>

				<div style="margin-top: 1.5rem; text-align: center;">
					<?php if ($profile): ?>
						<a href="/candidate_dashboard.php" class="btn-primary"
							style="text-decoration: none; width: 100%; color: white;">
							📊 My Queues
						</a>
					<?php else: ?>
						<a href="/candidate_register.php" class="btn-primary"
							style="text-decoration: none; width: 100%; color: white;">
							📝 Register Now
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- ── Section 4: Share ── -->
		<div class="home-card home-card--share glass">
			<div class="home-card__content">
				<h3>Share this Platform</h3>
				<div class="share-actions">
					<img id="current_url_qr" alt="Generating QR code..." class="share-qr">
					<div class="share-text">
						<p>Scan the code or use the link below to share with others.</p>
						<button type="button" class="btn-outline-sm maroon"
							onclick="copy_to_clipboard(window.location.href)">
							<span>🔗 Copy Link</span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="/script/utilities.js"></script>
	<script>qr_generate(window.location.href, document.getElementById('current_url_qr'));</script>
<?php };

$a->assemble();
