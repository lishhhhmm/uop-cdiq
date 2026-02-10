<?php
require_once __DIR__ . '/.private/database.php';

$db = database();
$update_id = $db->update_happened_recent();

echo "<h1>Seeding Database...</h1>";
echo "<pre>";

// --- Add Interviewers (Companies) ---
$companies = [
    ['name' => 'TechCorp Solutions', 'table' => 'A1'],
    ['name' => 'InnovateLab', 'table' => 'A2'],
    ['name' => 'DataSphere Analytics', 'table' => 'B1'],
    ['name' => 'CloudNine Systems', 'table' => 'B2'],
    ['name' => 'NextGen Robotics', 'table' => 'C1'],
];

foreach ($companies as $company) {
    try {
        $request = new SecretaryAddInterviewer($update_id, $company['name'], $company['table'], null);
        $result = $db->update_handle($request);
        if ($result === true) {
            echo "✓ Added company: {$company['name']} (Table {$company['table']})\n";
            $update_id = $db->update_happened_recent();
        } else {
            echo "✗ Failed to add {$company['name']}: $result\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding {$company['name']}: " . $e->getMessage() . "\n";
    }
}

// --- Add Students (Interviewees) ---
$students = [
    'alice.johnson@university.edu',
    'bob.smith@university.edu',
    'carol.williams@university.edu',
    'david.brown@university.edu',
    'eva.davis@university.edu',
    'frank.miller@university.edu',
    'grace.wilson@university.edu',
    'henry.moore@university.edu',
    'iris.taylor@university.edu',
    'jack.anderson@university.edu',
    'kate.thomas@university.edu',
    'leo.jackson@university.edu',
];

foreach ($students as $email) {
    try {
        $request = new SecretaryAddInterviewee($update_id, $email);
        $result = $db->update_handle($request);
        if ($result === true) {
            echo "✓ Added student: $email\n";
            $update_id = $db->update_happened_recent();
        } else {
            echo "✗ Failed to add $email: $result\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding $email: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Fetching data for queue assignments ---\n";

// Fetch IDs for queue assignment
$data = $db->retrieve('interviewer', 'interviewee');
$interviewers = $data['interviewer'] ?? [];
$interviewees = $data['interviewee'] ?? [];

echo "Found " . count($interviewers) . " companies and " . count($interviewees) . " students.\n";

// Assign some students to queues
if (count($interviewers) > 0 && count($interviewees) > 0) {
    echo "\n--- Adding students to queues ---\n";

    $update_id = $db->update_happened_recent();

    // Student 1 -> Company 1 and 2
    if (isset($interviewees[0]) && isset($interviewers[0]) && isset($interviewers[1])) {
        try {
            $request = new SecretaryEnqueueDequeue($update_id, $interviewees[0]['id'], $interviewers[0]['id'], $interviewers[1]['id']);
            $result = $db->update_handle($request);
            if ($result === true) {
                echo "✓ Enqueued {$interviewees[0]['email']} to {$interviewers[0]['name']} and {$interviewers[1]['name']}\n";
                $update_id = $db->update_happened_recent();
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    // Student 2 -> Company 1, 3
    if (isset($interviewees[1]) && isset($interviewers[0]) && isset($interviewers[2])) {
        try {
            $request = new SecretaryEnqueueDequeue($update_id, $interviewees[1]['id'], $interviewers[0]['id'], $interviewers[2]['id']);
            $result = $db->update_handle($request);
            if ($result === true) {
                echo "✓ Enqueued {$interviewees[1]['email']} to {$interviewers[0]['name']} and {$interviewers[2]['name']}\n";
                $update_id = $db->update_happened_recent();
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    // Student 3 -> Company 2, 4
    if (isset($interviewees[2]) && isset($interviewers[1]) && isset($interviewers[3])) {
        try {
            $request = new SecretaryEnqueueDequeue($update_id, $interviewees[2]['id'], $interviewers[1]['id'], $interviewers[3]['id']);
            $result = $db->update_handle($request);
            if ($result === true) {
                echo "✓ Enqueued {$interviewees[2]['email']} to {$interviewers[1]['name']} and {$interviewers[3]['name']}\n";
                $update_id = $db->update_happened_recent();
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    // Student 4 -> Company 5
    if (isset($interviewees[3]) && isset($interviewers[4])) {
        try {
            $request = new SecretaryEnqueueDequeue($update_id, $interviewees[3]['id'], $interviewers[4]['id']);
            $result = $db->update_handle($request);
            if ($result === true) {
                echo "✓ Enqueued {$interviewees[3]['email']} to {$interviewers[4]['name']}\n";
                $update_id = $db->update_happened_recent();
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n</pre>";
echo "<h2>Seeding Complete!</h2>";
echo "<p><a href='/'>Go to Dashboard</a></p>";
