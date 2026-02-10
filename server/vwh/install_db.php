<?php
require_once __DIR__ . '/.private/database.php';

echo "Initializing database...\n";

try {
    $db = database_admin();
    $db->create();
    echo "Database initialization completed.\n";

    // Add default operator
    echo "Adding default operator 'secretary'...\n";
    // operator_add(string $type, string $password, string $reminder)
    if ($db->operator_add('secretary', 'password123', 'default password')) {
        echo "Operator 'secretary' added with password 'password123'.\n";
    } else {
        echo "Operator 'secretary' already exists or failed to add.\n";
    }

    // Add gatekeeper too
    if ($db->operator_add('gatekeeper', 'password123', 'default password')) {
        echo "Operator 'gatekeeper' added with password 'password123'.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
