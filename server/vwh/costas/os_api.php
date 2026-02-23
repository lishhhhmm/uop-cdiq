<?php
/**
 * OS API — handles all CRUD operations for the superadmin dashboard.
 * All requests must be POST with a valid superadmin session.
 */

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

// ── Auth Config ──────────────────────────────────────────────────────────────
// Default password: superadmin2026
// Change via the dashboard's 🔑 button or regenerate the hash file.

function superadmin_get_hash(): string|null
{
    $hash_file = $_SERVER['DOCUMENT_ROOT'] . '/.private/.superadmin_hash';
    if (file_exists($hash_file)) {
        return trim(file_get_contents($hash_file));
    }
    return null;
}

function superadmin_is_authenticated(): bool
{
    return isset($_SESSION['superadmin_auth']) && $_SESSION['superadmin_auth'] === true;
}

function superadmin_rate_limit_check(): bool
{
    $lockout_until = $_SESSION['superadmin_lockout'] ?? 0;
    if ($lockout_until > time())
        return false;
    if ($lockout_until > 0 && $lockout_until <= time()) {
        $_SESSION['superadmin_attempts'] = 0;
        $_SESSION['superadmin_lockout'] = 0;
    }
    return true;
}

function superadmin_record_failed_attempt(): void
{
    $_SESSION['superadmin_attempts'] = ($_SESSION['superadmin_attempts'] ?? 0) + 1;
    if ($_SESSION['superadmin_attempts'] >= 5) {
        $_SESSION['superadmin_lockout'] = time() + 300;
    }
}

// ── Handle Login ─────────────────────────────────────────────────────────────
if (isset($_POST['superadmin_login'])) {
    header('Content-Type: application/json');
    if (!superadmin_rate_limit_check()) {
        $remaining = ($_SESSION['superadmin_lockout'] ?? 0) - time();
        echo json_encode(['ok' => false, 'error' => "Too many attempts. Try again in {$remaining} seconds."]);
        exit;
    }
    $password = $_POST['password'] ?? '';
    $hash = superadmin_get_hash();

    if ($hash === null) {
        // First time setup
        if (strlen($password) < 8) {
            echo json_encode(['ok' => false, 'error' => 'Initial password must be at least 8 characters']);
            exit;
        }
        $new_hash = password_hash($password, PASSWORD_BCRYPT);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/.private/.superadmin_hash', $new_hash);
        $_SESSION['superadmin_auth'] = true;
        echo json_encode(['ok' => true, 'setup' => true]);
        exit;
    }

    if (password_verify($password, $hash)) {
        $_SESSION['superadmin_auth'] = true;
        $_SESSION['superadmin_attempts'] = 0;
        $_SESSION['superadmin_lockout'] = 0;
        echo json_encode(['ok' => true]);
    } else {
        superadmin_record_failed_attempt();
        $remaining_attempts = 5 - ($_SESSION['superadmin_attempts'] ?? 0);
        echo json_encode(['ok' => false, 'error' => "Wrong password. {$remaining_attempts} attempts remaining."]);
    }
    exit;
}

// ── Handle Logout ────────────────────────────────────────────────────────────
if (isset($_POST['superadmin_logout'])) {
    unset($_SESSION['superadmin_auth']);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// ── All other actions require POST + auth ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

header('Content-Type: application/json');

if (!superadmin_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = database();
$db_admin = database_admin();
$db_jobs = database_jobpositions();

try {
    switch ($action) {

        // ── List Companies ───────────────────────────────────────────────
        case 'list_companies':
            $data = $db->retrieve('interviewer', 'interview');
            $interviewers = $data['interviewer'] ?? [];
            $interviews = $data['interview'] ?? [];

            $queue_counts = [];
            $completed_counts = [];
            foreach ($interviews as $iw) {
                $iwer_id = $iw['id_interviewer'];
                if (in_array($iw['state_'], ['ENQUEUED', 'CALLING', 'DECISION', 'HAPPENING'])) {
                    $queue_counts[$iwer_id] = ($queue_counts[$iwer_id] ?? 0) + 1;
                }
                if ($iw['state_'] === 'COMPLETED') {
                    $completed_counts[$iwer_id] = ($completed_counts[$iwer_id] ?? 0) + 1;
                }
            }

            foreach ($interviewers as &$iwer) {
                $iwer['queue_count'] = $queue_counts[$iwer['id']] ?? 0;
                $iwer['completed_count'] = $completed_counts[$iwer['id']] ?? 0;
            }

            echo json_encode(['ok' => true, 'companies' => $interviewers]);
            break;

        // ── List Candidates ──────────────────────────────────────────────
        case 'list_candidates':
            $data = $db->retrieve('interviewee', 'interview');
            $interviewees = $data['interviewee'] ?? [];
            $interviews = $data['interview'] ?? [];

            $queue_counts = [];
            $completed_counts = [];
            foreach ($interviews as $iw) {
                $iwee_id = $iw['id_interviewee'];
                if (in_array($iw['state_'], ['ENQUEUED', 'CALLING', 'DECISION', 'HAPPENING'])) {
                    $queue_counts[$iwee_id] = ($queue_counts[$iwee_id] ?? 0) + 1;
                }
                if ($iw['state_'] === 'COMPLETED') {
                    $completed_counts[$iwee_id] = ($completed_counts[$iwee_id] ?? 0) + 1;
                }
            }

            foreach ($interviewees as &$iwee) {
                $iwee['queue_count'] = $queue_counts[$iwee['id']] ?? 0;
                $iwee['completed_count'] = $completed_counts[$iwee['id']] ?? 0;
            }

            echo json_encode(['ok' => true, 'candidates' => $interviewees]);
            break;

        // ── Add Company ──────────────────────────────────────────────────
        case 'add_company':
            $name = $_POST['name'] ?? '';
            $table = $_POST['table'] ?? '';
            $image = $_FILES['image'] ?? null;

            if (trim($name) === '')
                throw new Exception('Company name is required');

            $update_id = $db->update_happened_recent();
            $request = new SecretaryAddInterviewer($update_id, $name, $table, $image);
            $result = $db->update_handle($request);

            if ($result !== true) {
                $request->when_dispatch_fails();
                throw new Exception($result);
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Edit Company ─────────────────────────────────────────────────
        case 'edit_company':
            $id = intval($_POST['id'] ?? 0);
            $name = $_POST['name'] ?? '';
            $table = $_POST['table'] ?? '';
            $image = $_FILES['image'] ?? null;

            if ($id <= 0)
                throw new Exception('Invalid company ID');
            if (trim($name) === '')
                throw new Exception('Company name is required');

            $update_id = $db->update_happened_recent();
            $request = new SecretaryEditInterviewer($update_id, $id, $name, $table, $image);
            $result = $db->update_handle($request);

            if ($result !== true) {
                $request->when_dispatch_fails();
                throw new Exception($result);
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Delete Company ───────────────────────────────────────────────
        case 'delete_company':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0)
                throw new Exception('Invalid company ID');

            $data = $db->retrieve('interviewer');
            $logo_url = null;
            foreach ($data['interviewer'] ?? [] as $iwer) {
                if ($iwer['id'] == $id) {
                    $logo_url = $iwer['image_resource_url'] ?? null;
                    break;
                }
            }

            $update_id = $db->update_happened_recent();
            $request = new class ($update_id, $id) extends UpdateRequest {
                private int $iwer_id;
                public function __construct(int $uid, int $id)
                {
                    parent::__construct($uid);
                    $this->iwer_id = $id;
                }
                protected function process(PDO $pdo): void
                {
                    $pdo->query("DELETE FROM interview WHERE id_interviewer = {$this->iwer_id};");
                    $pdo->query("DELETE FROM job WHERE id_interviewer = {$this->iwer_id};");
                    $stmt = $pdo->query("DELETE FROM interviewer WHERE id = {$this->iwer_id};");
                    if ($stmt === false || $stmt->rowCount() === 0) {
                        throw new Exception("Company not found");
                    }
                }
            };
            $result = $db->update_handle($request);
            if ($result !== true)
                throw new Exception($result);

            if ($logo_url && $logo_url !== SecretaryAddInterviewer::iwerImageResourceUrlPlaceholder()) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $logo_url;
                if (file_exists($path))
                    unlink($path);
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Delete All Companies ─────────────────────────────────────────
        case 'delete_all_companies':
            $update_id = $db->update_happened_recent();
            $request = new class ($update_id) extends UpdateRequest {
                protected function process(PDO $pdo): void
                {
                    $pdo->query("DELETE FROM interview WHERE id_interviewer IN (SELECT id FROM interviewer);");
                    $pdo->query("DELETE FROM job;");
                    $pdo->query("DELETE FROM interviewer;");
                }
            };
            $result = $db->update_handle($request);
            if ($result !== true)
                throw new Exception($result);

            $img_dir = $_SERVER['DOCUMENT_ROOT'] . '/resources/images/interviewer/';
            if (is_dir($img_dir)) {
                foreach (glob($img_dir . '*') as $file) {
                    if (is_file($file) && basename($file) !== 'placeholder.svg')
                        unlink($file);
                }
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Add Candidate ────────────────────────────────────────────────
        case 'add_candidate':
            $email = trim($_POST['email'] ?? '');
            if ($email === '')
                throw new Exception('Email is required');

            $update_id = $db->update_happened_recent();
            $request = new SecretaryAddInterviewee($update_id, $email);
            $result = $db->update_handle($request);
            if ($result !== true)
                throw new Exception($result);
            echo json_encode(['ok' => true]);
            break;

        // ── Toggle Candidate Active ──────────────────────────────────────
        case 'toggle_candidate':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0)
                throw new Exception('Invalid candidate ID');

            $update_id = $db->update_happened_recent();
            $request = new SecretaryActiveInactiveFlipInterviewee($update_id, $id);
            $result = $db->update_handle($request);
            if ($result !== true)
                throw new Exception($result);
            echo json_encode(['ok' => true]);
            break;

        // ── Delete Candidate ─────────────────────────────────────────────
        case 'delete_candidate':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0)
                throw new Exception('Invalid candidate ID');

            $data = $db->retrieve('interviewee');
            $cv_url = null;
            foreach ($data['interviewee'] ?? [] as $iwee) {
                if ($iwee['id'] == $id) {
                    $cv_url = $iwee['cv_resource_url'] ?? null;
                    break;
                }
            }

            $update_id = $db->update_happened_recent();
            $request = new class ($update_id, $id) extends UpdateRequest {
                private int $iwee_id;
                public function __construct(int $uid, int $id)
                {
                    parent::__construct($uid);
                    $this->iwee_id = $id;
                }
                protected function process(PDO $pdo): void
                {
                    $pdo->query("DELETE FROM interview WHERE id_interviewee = {$this->iwee_id};");
                    $stmt = $pdo->query("DELETE FROM interviewee WHERE id = {$this->iwee_id};");
                    if ($stmt === false || $stmt->rowCount() === 0) {
                        throw new Exception("Candidate not found");
                    }
                }
            };
            $result = $db->update_handle($request);
            if ($result !== true)
                throw new Exception($result);

            if ($cv_url) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $cv_url;
                if (file_exists($path))
                    unlink($path);
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Delete All Candidates ────────────────────────────────────────
        case 'delete_all_candidates':
            $update_id = $db->update_happened_recent();
            $request = new class ($update_id) extends UpdateRequest {
                protected function process(PDO $pdo): void
                {
                    $pdo->query("DELETE FROM interview WHERE id_interviewee IN (SELECT id FROM interviewee);");
                    $pdo->query("DELETE FROM interviewee;");
                }
            };
            $result = $db->update_handle($request);
            if ($result !== true)
                throw new Exception($result);

            $cv_dir = $_SERVER['DOCUMENT_ROOT'] . '/resources/cv/';
            if (is_dir($cv_dir)) {
                foreach (glob($cv_dir . '*') as $file) {
                    if (is_file($file) && basename($file) !== '.gitignore')
                        unlink($file);
                }
            }
            echo json_encode(['ok' => true]);
            break;

        // ── List Job Positions ───────────────────────────────────────────
        case 'list_jobs':
            $iwer_id = intval($_POST['interviewer_id'] ?? 0);
            if ($iwer_id <= 0)
                throw new Exception('Invalid company ID');

            $result = $db_jobs->retrieve_jobs_of($iwer_id);
            if ($result === false)
                throw new Exception('Failed to retrieve jobs');

            echo json_encode(['ok' => true, 'jobs' => $result['jobs'] ?? [], 'info' => $result['info'] ?? []]);
            break;

        // ── Add Job Position ─────────────────────────────────────────────
        case 'add_job':
            $iwer_id = intval($_POST['interviewer_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($iwer_id <= 0)
                throw new Exception('Invalid company ID');
            if ($title === '')
                throw new Exception('Job title is required');
            if ($description === '')
                throw new Exception('Job description is required');

            if (!$db_jobs->insert_job($title, $description, $iwer_id)) {
                throw new Exception('Failed to add job position');
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Delete Job Position ──────────────────────────────────────────
        case 'delete_job':
            $job_id = intval($_POST['job_id'] ?? 0);
            if ($job_id <= 0)
                throw new Exception('Invalid job ID');

            if (!$db_jobs->delete_job($job_id)) {
                throw new Exception('Failed to delete job position');
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Reset All Data ───────────────────────────────────────────────
        case 'reset_all':
            $update_id = $db->update_happened_recent();
            $request = new class ($update_id) extends UpdateRequest {
                protected function process(PDO $pdo): void
                {
                    $pdo->query("TRUNCATE TABLE interview RESTART IDENTITY CASCADE;");
                    $pdo->query("TRUNCATE TABLE job RESTART IDENTITY CASCADE;");
                    $pdo->query("TRUNCATE TABLE interviewer RESTART IDENTITY CASCADE;");
                    $pdo->query("TRUNCATE TABLE interviewee RESTART IDENTITY CASCADE;");
                    $pdo->query("TRUNCATE TABLE updates RESTART IDENTITY CASCADE;");
                }
            };
            $result = $db->update_handle($request);
            if ($result !== true)
                throw new Exception($result);

            $img_dir = $_SERVER['DOCUMENT_ROOT'] . '/resources/images/interviewer/';
            if (is_dir($img_dir)) {
                foreach (glob($img_dir . '*') as $file) {
                    if (is_file($file) && basename($file) !== 'placeholder.svg')
                        unlink($file);
                }
            }
            $cv_dir = $_SERVER['DOCUMENT_ROOT'] . '/resources/cv/';
            if (is_dir($cv_dir)) {
                foreach (glob($cv_dir . '*') as $file) {
                    if (is_file($file) && basename($file) !== '.gitignore')
                        unlink($file);
                }
            }
            echo json_encode(['ok' => true]);
            break;

        // ── Generate Test Companies ──────────────────────────────────────
        case 'generate_test_companies':
            $company_names = [
                'AlphaTech Solutions',
                'BetaData Analytics',
                'Nexus CyberLabs',
                'Quantum Innovations',
                'Pioneer Systems',
                'Vanguard Networks',
                'Horizon Cloud',
                'Titan Software',
                'Echo Robotics',
                'Omega Financial Services',
                'Apex Strategies',
                'Meridian Media',
                'Summit Engineering',
                'Crest Logistics',
                'Zenith Healthcare',
                'Aura Consulting',
                'Pulse Electronics',
                'Orbit Telecomm',
                'Stellar Aerospace',
                'Nova BioTech'
            ];

            shuffle($company_names);
            $names_to_add = array_slice($company_names, 0, 20);

            $update_id = $db->update_happened_recent();
            $request = new class ($update_id, $names_to_add) extends UpdateRequest {
                private array $names;
                public function __construct(int $uid, array $names)
                {
                    parent::__construct($uid);
                    $this->names = $names;
                }
                protected function process(PDO $pdo): void
                {
                    $values = [];
                    foreach ($this->names as $i => $name) {
                        $table = (string) ($i + 1);
                        $placeholder = SecretaryAddInterviewer::iwerImageResourceUrlPlaceholder();
                        $values[] = "('{$name}', '{$placeholder}', '{$table}', true, true)";
                    }
                    $sql = "INSERT INTO interviewer (name, image_resource_url, table_number, active, available) VALUES " . implode(', ', $values);
                    if ($pdo->query($sql) === false) {
                        throw new Exception("Failed to bulk insert companies");
                    }
                }
            };

            if ($db->update_handle($request) !== true) {
                throw new Exception('Bulk generation failed');
            }

            echo json_encode(['ok' => true, 'message' => 'Generated 20 companies in bulk']);
            break;

        // ── Generate Test Candidates ─────────────────────────────────────
        case 'generate_test_candidates':
            $first_names = ['Giorgos', 'Maria', 'Dimitris', 'Eleni', 'Giannis', 'Anna', 'Kostas', 'Katerina', 'Nikos', 'Vasiliki', 'Panagiotis', 'Sofia', 'Michalis', 'Angeliki', 'Christos'];
            $last_names = ['Papadopoulos', 'Georgiou', 'Dimitriou', 'Karagiannis', 'Mylonas', 'Konstantinou', 'Nikolaou', 'Antoniou', 'Makris', 'Galanis'];
            $departments = ['Computer Engineering', 'Informatics', 'Business Admin', 'Economics', 'Telecommunications'];

            $update_id = $db->update_happened_recent();
            $request = new class ($update_id, $first_names, $last_names, $departments) extends UpdateRequest {
                private array $first;
                private array $last;
                private array $depts;

                public function __construct(int $uid, array $f, array $l, array $d)
                {
                    parent::__construct($uid);
                    $this->first = $f;
                    $this->last = $l;
                    $this->depts = $d;
                }

                protected function process(PDO $pdo): void
                {
                    $values = [];
                    for ($i = 0; $i < 50; $i++) {
                        $f = $this->first[array_rand($this->first)];
                        $l = $this->last[array_rand($this->last)];
                        $d = $this->depts[array_rand($this->depts)];

                        $email = strtolower(substr($f, 0, 1) . $l . rand(10, 999) . '@go.uop.gr');
                        $name = $f . ' ' . $l;
                        $sub = 'test_sub_' . uniqid();
                        $masters = rand(0, 1) ? 'Yes' : 'No';
                        $interests = 'Software Development, AI';

                        $values[] = "('{$email}', '{$sub}', '{$name}', '{$d}', '{$masters}', '{$interests}', true, true)";
                    }

                    $sql = "INSERT INTO interviewee (email, google_sub, display_name, department, masters, interests, active, available) VALUES " . implode(', ', $values) . " ON CONFLICT DO NOTHING;";
                    if ($pdo->query($sql) === false)
                        throw new Exception('Failed to insert test candidates');
                }
            };

            if ($db->update_handle($request) !== true)
                throw new Exception('Generation failed');

            echo json_encode(['ok' => true, 'message' => 'Generated 50 candidates thoroughly']);
            break;

        // ── Generate Test Queues ─────────────────────────────────────────
        case 'generate_test_queues':
            $update_id = $db->update_happened_recent();
            $request = new class ($update_id) extends UpdateRequest {
                protected function process(PDO $pdo): void
                {
                    $iwers = $pdo->query("SELECT id FROM interviewer")->fetchAll(PDO::FETCH_COLUMN);
                    $iwees = $pdo->query("SELECT id FROM interviewee")->fetchAll(PDO::FETCH_COLUMN);

                    if (empty($iwers) || empty($iwees))
                        throw new Exception("Need both companies and candidates to generate queues");

                    $values = [];
                    $ts = $pdo->query('SELECT NOW();')->fetch()['now'];

                    foreach ($iwees as $uid) {
                        // Drop each user into 1 to 4 random queues
                        $queue_count = min(rand(1, 4), count($iwers));
                        $keys = (array) array_rand($iwers, $queue_count);

                        foreach ($keys as $key) {
                            $cid = $iwers[$key];
                            $values[] = "({$cid}, {$uid}, 'ENQUEUED', '{$ts}')";
                        }
                    }

                    $insert = "INSERT INTO interview (id_interviewer, id_interviewee, state_, state_timestamp)
                            VALUES " . implode(', ', $values) . "
                            ON CONFLICT ON CONSTRAINT pair_interviewer_interviewee DO NOTHING;";

                    if ($pdo->query($insert) === false)
                        throw new Exception('Failed to enqueue test candidates');
                }
            };
            if ($db->update_handle($request) !== true)
                throw new Exception('Test Queue generation failed');

            echo json_encode(['ok' => true, 'message' => 'Random queues populated!']);
            break;

        // ── Change Password ──────────────────────────────────────────────
        case 'change_password':
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';

            if (strlen($new) < 6)
                throw new Exception('New password must be at least 6 characters');

            $hash = superadmin_get_hash();
            if (!password_verify($current, $hash))
                throw new Exception('Current password is wrong');

            $new_hash = password_hash($new, PASSWORD_BCRYPT);
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/.private/.superadmin_hash', $new_hash);
            echo json_encode(['ok' => true]);
            break;

        // ── List Operators ───────────────────────────────────────────────
        case 'list_operators':
            $operators = $db_admin->operator_entries();
            // Don't send hashes to frontend
            foreach ($operators as &$op) {
                unset($op['pass']);
            }
            echo json_encode(['ok' => true, 'operators' => $operators]);
            break;

        // ── Update Operator Password ──────────────────────────────────────
        case 'update_operator_password':
            $id = intval($_POST['id'] ?? 0);
            $password = $_POST['password'] ?? '';

            if ($id <= 0)
                throw new Exception('Invalid operator ID');
            if (strlen($password) < 6)
                throw new Exception('Password must be at least 6 characters');

            if (!$db_admin->operator_update_password($id, $password)) {
                throw new Exception('Failed to update operator password');
            }
            echo json_encode(['ok' => true]);
            break;

        default:
            throw new Exception("Unknown action: $action");
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
