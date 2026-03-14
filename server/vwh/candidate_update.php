<?php

/**
 * candidate_update.php
 * 
 * POST handler for candidate actions:
 *   action=register  — first-time registration / profile update
 *   action=join      — join a company queue
 *   action=leave     — leave an ENQUEUED company queue
 * 
 * All writes go through update_handle() → EXCLUSIVE LOCK → update_id_known check.
 * Concurrency with the Secretary is fully safe.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/candidate_auth.php';

candidate_session_ensure_started();

$action = $_POST['action'] ?? '';
$update_id = (int) ($_POST['update_id'] ?? 0);

$db = database();

// ─────────────────────────────────────────────────
// ACTION: login (auto-login check for returning users)
// ─────────────────────────────────────────────────
if ($action === 'login') {
    $google_token = trim($_POST['google_token'] ?? '');

    $payload = candidate_verify_google_token($google_token);
    if (!is_array($payload)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $payload]);
        exit;
    }
    $google_sub = $payload['sub'] ?? '';
    $email = trim($payload['email'] ?? '');

    $candidate_row = $db->candidate_by_google_sub($google_sub);

    header('Content-Type: application/json');
    if ($candidate_row) {
        // Log them in immediately
        candidate_session_set([
            'google_sub' => $google_sub,
            'email' => $candidate_row['email'],
            'display_name' => $candidate_row['display_name'],
            'avatar_url' => $candidate_row['avatar_url'],
        ]);
        echo json_encode(['success' => true, 'registered' => true]);
    } else {
        $candidate_row = $email !== '' ? $db->candidate_by_email($email) : false;

        if ($candidate_row) {
            $dashboard = $db->candidate_dashboard_view((int) $candidate_row['id']);
            $joined_company_ids = [];
            $joined_company_names = [];

            foreach (($dashboard['interviews'] ?? []) as $interview) {
                $company_id = (int) ($interview['id_interviewer'] ?? 0);
                if ($company_id <= 0) {
                    continue;
                }

                $joined_company_ids[] = $company_id;

                $company_name = trim((string) ($interview['company_name'] ?? ''));
                if ($company_name !== '') {
                    $joined_company_names[] = $company_name;
                }
            }

            $joined_company_ids = array_values(array_unique($joined_company_ids));
            $joined_company_names = array_values(array_unique($joined_company_names));

            echo json_encode([
                'success' => true,
                'registered' => false,
                'email_matched' => true,
                'secretary_origin' => empty($candidate_row['google_sub']),
                'message' => 'We found an existing registration with this email. Your secretary registration and queue selections will be kept. Fill in the rest of your profile to continue to your dashboard.',
                'candidate' => [
                    'id' => (int) $candidate_row['id'],
                    'email' => $candidate_row['email'],
                    'display_name' => $candidate_row['display_name'] ?? '',
                    'avatar_url' => $candidate_row['avatar_url'] ?? '',
                    'department' => $candidate_row['department'] ?? '',
                    'masters' => $candidate_row['masters'] ?? '',
                    'interests' => $candidate_row['interests'] ?? '',
                    'cv_resource_url' => $candidate_row['cv_resource_url'] ?? '',
                    'joined_company_ids' => $joined_company_ids,
                    'joined_company_names' => $joined_company_names,
                ],
            ]);
        } else {
            echo json_encode(['success' => true, 'registered' => false, 'email_matched' => false]);
        }
    }
    exit;
}

// ─────────────────────────────────────────────────
// ACTION: register (initial or update)
// ─────────────────────────────────────────────────
if ($action === 'register') {

    $google_token = trim($_POST['google_token'] ?? '');

    // ── Normal production path: verify Google token ──
    if (empty($google_token)) {
        redirect_with_error('/candidate_register.php', 'Missing Google token. Please sign in again.');
    }

    $payload = candidate_verify_google_token($google_token);

    if (!is_array($payload)) {
        redirect_with_error('/candidate_register.php', 'Authentication failed: ' . $payload);
    }

    $email = $payload['email'] ?? '';
    if (!($payload['email_verified'] ?? false)) {
        redirect_with_error('/candidate_register.php', 'Google account email is not verified.');
    }
    if (!candidate_is_uop_email($email)) {
        redirect_with_error('/candidate_register.php', 'Only @go.uop.gr accounts are accepted.');
    }

    $google_sub = $payload['sub'] ?? '';
    $display_name = $payload['name'] ?? '';
    $avatar_url = $payload['picture'] ?? '';

    // Department
    $dept = trim($_POST['dept'] ?? '');
    if ($dept === 'Other') {
        $dept = trim($_POST['other_dept'] ?? '');
    }
    if (empty($dept)) {
        redirect_with_error('/candidate_register.php', 'Please select your department.');
    }

    $masters = trim($_POST['masters'] ?? '');
    $interests = implode(',', array_map('trim', (array) ($_POST['interests'] ?? [])));

    if (empty($interests)) {
        redirect_with_error('/candidate_register.php', 'Please select at least one career interest.');
    }

    $cv_file = $_FILES['cv'] ?? null;
    $companies = $_POST['companies'] ?? [];

    if ($cv_file && $cv_file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($cv_file['size'] > 1048576) {
            redirect_with_error('/candidate_register.php', 'CV file size must not exceed 1 MB.');
        }
        $mime = @mime_content_type($cv_file['tmp_name']);
        if ($mime !== 'application/pdf') {
            redirect_with_error('/candidate_register.php', 'Only PDF files are allowed for CV.');
        }
    }

    try {
        $request = new CandidateSelfRegister(
            $update_id,
            $email,
            $google_sub,
            $display_name,
            $avatar_url,
            $dept,
            $masters,
            $interests,
            $cv_file,
            $companies
        );
    } catch (InvalidArgumentException $e) {
        redirect_with_error('/candidate_register.php', $e->getMessage());
    }

    $result = $db->update_handle($request);

    if ($result !== true) {
        redirect_with_error('/candidate_register.php', 'Registration error: ' . $result);
    }

    // Store session
    candidate_session_set([
        'google_sub' => $google_sub,
        'email' => $email,
        'display_name' => $display_name,
        'avatar_url' => $avatar_url,
    ]);

    $_SESSION['candidate_flash'] = [
        'type' => 'success',
        'message' => '🎉 Registration complete! Your queues are now active.',
    ];
    header('Location: /candidate_dashboard.php');
    exit;

}

// ─────────────────────────────────────────────────
// ACTION: update_cv (from dashboard)
// ─────────────────────────────────────────────────
if ($action === 'update_cv') {
    $candidate = candidate_session_get();
    if ($candidate === false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $candidate_row = $db->candidate_by_google_sub($candidate['google_sub']);
    if (!$candidate_row) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Candidate not found']);
        exit;
    }

    $cv_file = $_FILES['cv'] ?? null;
    if (!$cv_file || $cv_file['error'] === UPLOAD_ERR_NO_FILE) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        exit;
    }

    if ($cv_file['size'] > 1048576) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CV file size must not exceed 1 MB']);
        exit;
    }
    $mime = @mime_content_type($cv_file['tmp_name']);
    if ($mime !== 'application/pdf') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Only PDF files are allowed for CV']);
        exit;
    }

    try {
        $request = new CandidateUpdateCV($update_id, (int) $candidate_row['id'], $cv_file);
        $result = $db->update_handle($request);

        header('Content-Type: application/json');
        if ($result === true) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $result]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─────────────────────────────────────────────────
// ACTION: join / leave  (must be logged in)
// ─────────────────────────────────────────────────
$candidate = candidate_session_get();
if ($candidate === false) {
    header('Location: /candidate_register.php');
    exit;
}

$candidate_row = $db->candidate_by_google_sub($candidate['google_sub']);
if ($candidate_row === false) {
    redirect_with_flash('/candidate_dashboard.php', 'error', 'Session error. Please sign in again.');
}

$iwee_id = (int) $candidate_row['id'];
$iwer_id = (int) ($_POST['interviewer_id'] ?? 0);

if ($action === 'join') {
    if ($iwer_id <= 0) {
        redirect_with_flash('/candidate_dashboard.php', 'error', 'Invalid company.');
    }
    $request = new CandidateJoinQueue($update_id, $iwee_id, $iwer_id);
    $result = $db->update_handle($request);

    if ($result === true) {
        redirect_with_flash('/candidate_dashboard.php', 'success', '✅ You joined the queue!');
    } else {
        redirect_with_flash('/candidate_dashboard.php', 'error', 'Could not join queue: ' . $result);
    }

} elseif ($action === 'leave') {
    if ($iwer_id <= 0) {
        redirect_with_flash('/candidate_dashboard.php', 'error', 'Invalid company.');
    }
    $request = new CandidateLeaveQueue($update_id, $iwee_id, $iwer_id);
    $result = $db->update_handle($request);

    if ($result === true) {
        redirect_with_flash('/candidate_dashboard.php', 'success', 'You have left the queue.');
    } else {
        redirect_with_flash('/candidate_dashboard.php', 'error', 'Could not leave queue: ' . $result);
    }

} elseif ($action === 'toggle_pause') {

    $request = new CandidateToggleActiveState($update_id, $iwee_id);
    $result = $db->update_handle($request);

    // It's requested via JS fetch so return JSON
    header('Content-Type: application/json');
    if ($result === true) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $result]);
    }
    exit;

} elseif ($action === 'update_profile') {

    $dept = trim($_POST['dept'] ?? '');
    if ($dept === 'Other') {
        $dept = trim($_POST['other_dept'] ?? '');
    }
    if (empty($dept)) {
        redirect_with_error('/candidate_dashboard.php', 'Please select your department.');
    }

    $masters = trim($_POST['masters'] ?? '');
    $interests = implode(',', array_map('trim', (array) ($_POST['interests'] ?? [])));

    if (empty($interests)) {
        redirect_with_error('/candidate_dashboard.php', 'Please select at least one career interest.');
    }

    $request = new CandidateUpdateProfile($update_id, $iwee_id, $dept, $masters, $interests);
    $result = $db->update_handle($request);

    if ($result === true) {
        redirect_with_flash('/candidate_dashboard.php', 'success', 'Profile updated successfully.');
    } else {
        redirect_with_flash('/candidate_dashboard.php', 'error', 'Could not update profile: ' . $result);
    }

} else {
    header('Location: /candidate_dashboard.php');
    exit;
}

// ─────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────
function redirect_with_error(string $url, string $msg): never
{
    candidate_session_ensure_started();
    $_SESSION['candidate_flash'] = ['type' => 'error', 'message' => $msg];
    header('Location: ' . $url);
    exit;
}

function redirect_with_flash(string $url, string $type, string $msg): never
{
    candidate_session_ensure_started();
    $_SESSION['candidate_flash'] = ['type' => $type, 'message' => $msg];
    header('Location: ' . $url);
    exit;
}
