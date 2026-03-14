<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/candidate_auth.php';

$a = new Assembler('Career Fair 2026 Interview Hub');

$a->body_main_id = 'index-main';

$a->body_main = function () {
	$profile = candidate_session_get();
	?>
	<div class="home-grid animate-fade-in">
		<dialog id="dialog-platform-info" class="info-dialog" onclick="if(event.target===this)this.close();">
			<button class="dialog-close" type="button" onclick="this.closest('dialog').close();" aria-label="Close">×</button>
			<h3>Καλωσορίσατε στο Career Fair 2026!</h3>
			<hr>
			<p><strong>2. Εγγραφείτε στην Γραμματεία</strong></p>
			<p>Δηλώνετε στην γραμματεία το mail καθώς και τις εταιρίες στις οποίες επιθυμείτε να δώσετε συνέντευξη.</p>
			<p>Η γραμματεία σας δίνει ένα χαρτάκι με έναν μοναδικό αύξοντα αριθμό. Ο αριθμός αυτός χρησιμοποιείται για την κλήση σας, δεν δηλώνει προτεραιότητα.</p>
			<p><strong>Το χαρτάκι ΠΡΕΠΕΙ να επιδεικνύεται στην είσοδο/έξοδο της βιβλιοθήκης.</strong></p>
			<hr>
			<p><strong>2. Εγγραφείτε Ηλεκτρονικά</strong></p>
			<p>Εγγραφή μόνο με ακαδημαικό λογαριασμό <code>@go.uop.gr</code></p>
			<p>Επισκεφτείτε την πλατφόρμα <strong>apps.careerday.fet.uop.gr</strong> και ακολουθήστε τις οδηγίες εγγραφής.</p>
			<hr>
			<p><strong>Πώς λειτουργεί η Οθόνη Κλήσεων</strong></p>
			<p>Στην κεντρική οθόνη βλέπετε τις εταιρίες και την τρέχουσα κατάστασή τους. Παρακολουθείτε την οθόνη για να δείτε αν εμφανιστεί ο αριθμός σας. Επισκεφτείτε την πλατφόρμα apps.careerday.fet.uop.gr για να δείτε περισσότερες πληροφορίες.</p>
			<ul>
				<li><span class="av">Available</span> <strong>Διαθέσιμη</strong>: Η εταιρεία είναι διαθέσιμη. Είτε δεν υπάρχουν υποψήφιοι σε αναμονή, είτε οι υποψήφιοι που περιμένουν βρίσκονται αυτή τη στιγμή σε άλλες συνεντεύξεις. Μπορείτε να εγγραφείτε στη γραμματεία και να πάτε άμεσα για συνέντευξη.</li>
				<li><span class="ca">Calling</span> <strong>Κλήση για Συνέντευξη!</strong>: Ένας υποψήφιος καλείται αυτή τη στιγμή. Αν αυτός είναι ο αριθμός σας, παρακαλούμε προσέλθετε στον υπεύθυνο υποδοχής (Gatekeeper) για να ξεκινήσει η συνέντευξή σας. Έχετε 3 λεπτά να προσέλθετε.</li>
				<li><span class="de">Decision</span> <strong>Χρόνος Έληξε</strong>: Η περίοδος αναμονής έληξε. Ο υπεύθυνος (Gatekeeper) θα κρίνει αν η προσέλευσή σας ήταν εμπρόθεσμη. Αν έχετε αυτόν τον αριθμό και δεν έχετε παρουσιαστεί, θεωρείστε εκπρόθεσμος και θα αφαιρεθείτε από τη σειρά προτεραιότητας (μπορείτε να εγγραφείτε ξανά στην ουρά).</li>
				<li><span class="ha">Happening</span> <strong>Κατειλημμένη</strong>: Η εταιρία είναι κατειλημμένη, δηλαδή κάποιος άλλος χρήστης δίνει συνέντευξη εκεί.</li>
				<li><span class="pa">Paused</span> <strong>Σε Παύση</strong>: Η εταιρία δεν δέχεται συνεντεύξεις προσωρινά. Η ουρά δεν χάνεται.</li>
			</ul>
			<button type="button" onclick="document.getElementById('dialog-platform-info').close();" style="width:100%; margin-top:1rem;">Κλείσιμο</button>
		</dialog>
		<!-- ── Section 1: The Platform ── -->
		<div class="home-card glass">
			<div class="home-card__icon">🎯</div>
			<div class="home-card__content">
				<h3>The Platform</h3>
				<p>Track all interviews during <strong>Career Fair 2026</strong> in real time. See live company queues and
					current
					interview statuses all in one place.</p>
				<div style="margin-top: 1rem;">
					<button type="button" class="btn-outline-sm maroon"
						onclick="document.getElementById('dialog-platform-info').showModal(); document.getElementById('dialog-platform-info').scrollTo(0,0);">
						More Info
					</button>
				</div>
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
