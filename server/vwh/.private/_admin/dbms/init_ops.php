<?php

require_once __DIR__ . '/../../database.php';
$db = database_admin();

$operators = [
    ['type' => 'secretary', 'password' => 'secpass', 'reminder' => 'Secretary Default'],
    ['type' => 'gatekeeper', 'password' => 'gatepass', 'reminder' => 'Gatekeeper Default']
];

foreach ($operators as $op) {
    if ($db->operator_add($op['type'], $op['password'], $op['reminder'])) {
        echo "Operator {$op['type']} added.\n";
    } else {
        echo "Operator {$op['type']} already exists or error.\n";
    }
}
