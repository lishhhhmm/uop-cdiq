<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

$a = new Assembler('Companies');

$a->body_main = function () {
    $db = database();
    $companies = $db->retrieve_companies_view();
    ?>
    <div class="container_interviewers">
        <?php if (empty($companies)): ?>
            <p>No companies are appearing in the system yet.</p>
        <?php else: ?>
            <?php foreach ($companies as $company): ?>
                <div class="interviewer">
                    <div class="status_indicator" style="background-color: var(--brand-orange);"></div>
                    <div class="info">
                        <img src="<?= htmlspecialchars($company['image_resource_url']) ?>"
                            alt="<?= htmlspecialchars($company['name']) ?> Logo" class="image">
                        <div class="text">
                            <?= htmlspecialchars($company['name']) ?>
                            <br>
                            <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: normal;">Table </span>
                            <span class="called-number"
                                style="font-size: 1rem; margin-top: 2px;"><?= htmlspecialchars($company['table_number'] ?: '-') ?></span>
                        </div>
                    </div>
                    <div class="status_information"
                        style="justify-content: flex-start; align-items: stretch; text-align: left; padding: 1rem; overflow-y: auto;">
                        <?php if (!empty($company['jobs'])): ?>
                            <p
                                style="margin: 0 0 0.5rem 0; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
                                Positions:</p>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <?php foreach ($company['jobs'] as $job): ?>
                                    <div style="border-left: 3px solid var(--brand-maroon); padding-left: 0.75rem;">
                                        <p
                                            style="margin: 0; font-weight: 700; color: var(--text-primary); font-size: 0.85rem; line-height: 1.2;">
                                            <?= htmlspecialchars($job['title']) ?>
                                        </p>
                                        <p
                                            style="margin: 2px 0 0 0; font-size: 0.75rem; color: var(--text-secondary); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= htmlspecialchars($job['description']) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p
                                style="font-style: italic; color: var(--text-secondary); font-size: 0.85rem; margin: auto; opacity: 0.7;">
                                No positions listed yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php };

$a->assemble();
