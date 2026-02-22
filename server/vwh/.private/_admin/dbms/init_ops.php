<?php

require_once __DIR__ . '/../../database.php';
$db = database_admin();

// This script should be run manually via CLI if needed, or removed.
// It currently adds operators with placeholder passwords that MUST be changed via the OS dashboard.

$operators = [
    ['type' => 'secretary', 'password' => bin2hex(random_bytes(8)), 'reminder' => 'Secretary Default (Please change password immediately)'],
    ['type' => 'gatekeeper', 'password' => bin2hex(random_bytes(8)), 'reminder' => 'Gatekeeper Default (Please change password immediately)']
];

foreach ($operators as $op) {
    if ($db->operator_add($op['type'], $op['password'], $op['reminder'])) {
        echo "Operator {$op['type']} added.\n";
    } else {
        echo "Operator {$op['type']} already exists or error.\n";
    }
}
