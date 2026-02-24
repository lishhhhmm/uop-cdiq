<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

// Create a custom anonymous class extending Assembler to inject specific header styles
$a = new class ("") extends Assembler {
    protected function head(): void
    {
        parent::head();
        echo '<style>
            /* Center the header logos specifically for the projection screen */
            .nav-main-row {
                justify-content: center !important;
            }
            .header-logos {
                margin: 0 auto !important;
            }
            /* Hide the hamburger and nav links layout completely as they are empty */
            .hamburger, .nav-links {
                display: none !important;
            }
        </style>';
    }
};

$a->custom_nav = []; // No navigation needed for projection screen

$a->body_main = function () { ?>
    <div class="projection-container animate-fade-in"
        style="max-width: 1400px; margin: -2rem auto 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; padding: 0 2rem 2rem 2rem;">

        <!-- Left Side: QR & Registration -->
        <div class="projection-left">
            <h1 style="font-size: 3rem; margin-bottom: 2rem; color: var(--text-primary); text-align: center;">
                Καλωσορίσατε<br>στο Career Fair 2026!</h1>

            <div class="qr-card"
                style="background: var(--bg-card); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); text-align: center; margin-bottom: 3rem; border: 1px solid var(--border);">
                <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--accent-primary);">1. Επισκεφτείτε την
                    πλατφόρμα:</h2>
                <!-- QR Code points to the root URL (apps.careerday.fet.uop.gr) -->
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=https://apps.careerday.fet.uop.gr&color=1e293b&bgcolor=ffffff"
                    alt="Εγγραφή QR Code"
                    style="width: 250px; height: 250px; margin: 0 auto; border-radius: 1rem; border: 4px solid var(--border); box-shadow: var(--shadow-sm);">
                <p style="margin-top: 1.5rem; font-size: 1.2rem; color: var(--text-secondary); font-weight: 600;">Σκανάρετε
                    για να επισκεφτείτε την πλατφόρμα <a
                        href="https://apps.careerday.fet.uop.gr">apps.careerday.fet.uop.gr</a></p>
            </div>

            <div class="instructions-card"
                style="background: var(--bg-card); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border-left: 6px solid var(--accent-secondary);">
                <h2 style="font-size: 1.6rem; margin-bottom: 1rem; color: var(--text-primary);">2. Εγγραφείτε στην
                    Γραμματεία</h2>
                <div style="font-size: 1.15rem; color: var(--text-secondary); line-height: 1.7;">
                    <p style="margin-bottom: 1rem;">Δηλώνετε στην γραμματεία το <strong>mail</strong> καθώς και τις
                        <strong>εταιρίες</strong> στις οποίες επιθυμείτε να
                        δώσετε συνέντευξη.
                    </p>
                    <p style="margin-bottom: 1rem;">Η γραμματεία σας δίνει ένα χαρτάκι με έναν <strong>μοναδικό αύξοντα
                            αριθμό</strong>. Ο αριθμός αυτός χρησιμοποιείται για την κλήση σας, <strong>δεν δηλώνει
                            προτεραιότητα.</strong></p>
                    <p
                        style="color: var(--status-happening); font-weight: 600; padding-top: 0.5rem; border-top: 1px dashed var(--border);">
                        Το χαρτάκι ΠΡΕΠΕΙ να επιδεικνύεται στην είσοδο/έξοδο της βιβλιοθήκης.</p>
                </div><br>
                <h2 style="font-size: 1.6rem; margin-bottom: 1rem; color: var(--text-primary);">2. Εγγραφείτε Ηλεκτρονικά
                </h2>
                <div style="font-size: 1.15rem; color: var(--text-secondary); line-height: 1.7;">
                    <p style="margin-bottom: 1rem;">Εγγραφή μόνο με ακαδημαικό λογαριασμό <strong>@go.uop.gr</strong></p>
                    <p style="margin-bottom: 1rem;">Επισκεφτείτε την πλατφόρμα apps.careerday.fet.uop.gr και ακολουθήστε τις
                        οδηγίες εγγραφής.</p>
                </div>
            </div>
        </div>

        <!-- Right Side: Status UI Legend -->
        <div class="projection-right">
            <h1 style="font-size: 2.5rem; margin-bottom: 2rem; color: var(--text-primary);">Πώς λειτουργεί η Οθόνη Κλήσεων
            </h1>
            <p style="font-size: 1.2rem; color: var(--text-secondary); margin-bottom: 2.5rem; line-height: 1.6;">
                Στην κεντρική οθόνη βλέπετε τις εταιρίες και την τρέχουσα κατάστασή τους. Παρακολουθείτε την οθόνη για να
                δείτε αν εμφανιστεί ο αριθμός σας. Επισκεφτείτε την πλατφόρμα apps.careerday.fet.uop.gr για να δείτε
                περισσότερες πληροφορίες.
            </p>

            <div class="legend-grid" style="display: flex; flex-direction: column; gap: 1.5rem;">

                <!-- Available -->
                <div class="legend-item"
                    style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: flex-start; gap: 1.5rem; border-left: 6px solid var(--color-status--available);">
                    <div
                        style="background: var(--color-status--available); color: white; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase;">
                        Available</div>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.25rem; font-size: 1.2rem;">Διαθέσιμη</h3>
                        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.5; margin:0;">Η εταιρεία
                            είναι διαθέσιμη. Είτε δεν υπάρχουν υποψήφιοι σε αναμονή, είτε οι υποψήφιοι που περιμένουν
                            βρίσκονται αυτή τη στιγμή σε άλλες συνεντεύξεις. Μπορείτε να εγγραφείτε στη γραμματεία και να
                            πάτε <strong>άμεσα</strong> για
                            συνέντευξη.</p>
                    </div>
                </div>
                <!-- Calling -->
                <div class="legend-item"
                    style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: flex-start; gap: 1.5rem; border-left: 6px solid var(--color-status--calling);">
                    <div
                        style="background: var(--color-status--calling); color: white; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase; animation: pulse 2s infinite;">
                        Calling</div>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.25rem; font-size: 1.2rem;">Κλήση για Συνέντευξη!</h3>
                        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.5; margin:0;">Ένας
                            υποψήφιος καλείται αυτή τη στιγμή. Αν αυτός είναι ο αριθμός σας, παρακαλούμε προσέλθετε στον
                            υπεύθυνο υποδοχής (Gatekeeper) για να ξεκινήσει η συνέντευξή σας. Έχετε <strong>3 λεπτά</strong>
                            να προσέλθετε.</p>
                    </div>
                </div>
                <!-- Decision -->
                <div class="legend-item"
                    style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: flex-start; gap: 1.5rem; border-left: 6px solid var(--color-status--decision);">
                    <div
                        style="background: var(--color-status--decision); color: white; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase;">
                        Decision</div>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.25rem; font-size: 1.2rem;">Χρόνος Έληξε</h3>
                        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.5; margin:0;">Η περίοδος
                            αναμονής έληξε. Ο υπεύθυνος (Gatekeeper) θα κρίνει αν η προσέλευσή σας ήταν εμπρόθεσμη. Αν έχετε
                            αυτόν τον αριθμό και δεν έχετε παρουσιαστεί, θεωρείστε εκπρόθεσμος και θα αφαιρεθείτε από τη
                            σειρά προτεραιότητας (μπορείτε να εγγραφείτε ξανά στην ουρά).</p>
                    </div>
                </div>
                <!-- Happening -->
                <div class="legend-item"
                    style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: flex-start; gap: 1.5rem; border-left: 6px solid var(--color-status--happening);">
                    <div
                        style="background: var(--color-status--happening); color: white; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase;">
                        Happening</div>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.25rem; font-size: 1.2rem;">Κατειλημμένη</h3>
                        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.5; margin:0;">Η εταιρία
                            είναι κατειλημμένη, δηλαδή κάποιος άλλος χρήστης δίνει συνέντευξη εκεί.</p>
                    </div>
                </div>
                <!-- Paused -->
                <div class="legend-item"
                    style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: flex-start; gap: 1.5rem; border-left: 6px solid var(--color-status--unavailable);">
                    <div
                        style="background: var(--color-status--unavailable); color: white; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; font-size: 0.9rem; letter-spacing: 0.05em; text-transform: uppercase;">
                        Paused</div>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.25rem; font-size: 1.2rem;">Σε Παύση</h3>
                        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.5; margin:0;">Η εταιρία
                            δεν δέχεται συνεντεύξεις προσωρινά. Η ουρά δεν χάνεται.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        /* Pulse animation for the Calling badge */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Responsive adjustments for smaller projection screens */
        @media (max-width: 1024px) {
            .projection-container {
                grid-template-columns: 1fr !important;
                gap: 2rem !important;
            }
        }
    </style>
<?php };

$a->assemble();
