<?php
/**
 * cli_setup.php
 * 
 * Command-line script to initialize the database, tables, and default operators.
 * This should be run ONCE during initial deployment or after a full reset.
 * 
 * Usage:
 *   php cli_setup.php
 * 
 * (Inside Docker):
 *   docker compose exec server php cli_setup.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Error: This script can only be run from the command line (CLI).\n");
}

require_once __DIR__ . '/.private/database.php';

echo "🚀 Starting Database Initialization...\n";

try {
    $db = database_admin();

    // 1. Create Database & Tables
    echo "Creating database and tables...\n";
    $db->create(); // Note: Postgres.create() already prints "Database created" etc.

    // 2. Generate random temporary passwords for operators
    $sec_pass = bin2hex(random_bytes(8));
    $gate_pass = bin2hex(random_bytes(8));

    echo "\nAdding default operators...\n";

    // Add secretary
    if ($db->operator_add('secretary', $sec_pass, 'Initial Setup')) {
        echo "✅ Operator 'secretary' added.\n";
        echo "   PASSWORD: $sec_pass\n";
    } else {
        echo "⚠️ Operator 'secretary' already exists or failed to add.\n";
    }

    // Add gatekeeper
    if ($db->operator_add('gatekeeper', $gate_pass, 'Initial Setup')) {
        echo "✅ Operator 'gatekeeper' added.\n";
        echo "   PASSWORD: $gate_pass\n";
    } else {
        echo "⚠️ Operator 'gatekeeper' already exists or failed to add.\n";
    }

    echo "\n🎉 Initialization finished!\n";
    echo "--------------------------------------------------\n";
    echo "IMPORTANT: Copy these passwords now. You should change them \n";
    echo "immediately via the Superadmin dashboard (/costas/os.php).\n";
    echo "--------------------------------------------------\n";

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
