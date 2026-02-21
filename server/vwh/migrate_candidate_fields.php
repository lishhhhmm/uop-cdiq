<?php

/**
 * migrate_candidate_fields.php
 * 
 * Run this ONCE on an existing database to add candidate self-registration columns
 * to the interviewee table and create the cv storage directory.
 * 
 * Usage: visit /migrate_candidate_fields.php in your browser (then delete it after running).
 * 
 * Safe to run multiple times — uses IF NOT EXISTS / DO NOTHING patterns.
 */

require_once __DIR__ . '/.private/database.php';

$config = require __DIR__ . '/.private/config.php';
$conf = $config['dbms'];

try {
    $pdo = new PDO(
        "pgsql:host={$conf['host']};dbname={$conf['dbname']}",
        $conf['user'],
        $conf['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $columns = [
        'google_sub' => 'TEXT',
        'display_name' => 'TEXT',
        'avatar_url' => 'TEXT',
        'department' => 'TEXT',
        'masters' => 'TEXT',
        'interests' => 'TEXT',
        'cv_resource_url' => 'TEXT',
    ];

    echo "<pre>\n";
    echo "Running candidate field migration...\n\n";

    foreach ($columns as $col => $type) {
        $check = $pdo->query(
            "SELECT 1 FROM information_schema.columns
             WHERE table_name = 'interviewee' AND column_name = '{$col}';"
        );

        if ($check->fetchColumn()) {
            echo "  SKIP  : Column '{$col}' already exists.\n";
        } else {
            $pdo->exec("ALTER TABLE interviewee ADD COLUMN {$col} {$type};");
            echo "  ADDED : Column '{$col}' ({$type})\n";
        }
    }

    // Add UNIQUE constraint on google_sub if not already present
    $uc = $pdo->query(
        "SELECT 1 FROM information_schema.table_constraints
         WHERE table_name = 'interviewee'
         AND constraint_name = 'interviewee_google_sub_key';"
    );
    if (!$uc->fetchColumn()) {
        $pdo->exec(
            "ALTER TABLE interviewee
             ADD CONSTRAINT interviewee_google_sub_key UNIQUE (google_sub);"
        );
        echo "  ADDED : UNIQUE constraint on 'google_sub'\n";
    } else {
        echo "  SKIP  : UNIQUE constraint on 'google_sub' already exists.\n";
    }

    // Create CV directory
    $cv_dir = __DIR__ . '/resources/cv';
    if (!is_dir($cv_dir)) {
        mkdir($cv_dir, 0755, true);
        echo "  ADDED : Created /resources/cv/ directory.\n";
    } else {
        echo "  SKIP  : /resources/cv/ directory already exists.\n";
    }

    echo "\nMigration complete. You can delete this file now.\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "<pre>ERROR: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
