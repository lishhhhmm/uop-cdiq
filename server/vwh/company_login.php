<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Company Login');
$a->custom_nav = [
    'Home' => '/',
    'Interviews' => '/queues.php'
];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if ($a->company_challenge($token) === false) {
        $error = "Invalid or expired token.";
    }
}

$a->body_main = function () use ($error) { ?>
    <div
        style="max-width: 400px; margin: 2rem auto; padding: 2rem; background: var(--surface-primary); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; margin-bottom: 1.5rem;">Interviewer Access</h2>
        <form method="post" action="/company_login.php" style="display: flex; flex-direction: column; gap: 1rem;">
            <label for="token">
                Enter your 6-digit access token:
                <input type="text" name="token" id="token" placeholder="000000" pattern="\d{6}" required
                    style="width: 100%; padding: 0.75rem; font-size: 1.25rem; text-align: center; letter-spacing: 0.5rem; margin-top: 0.5rem;">
            </label>
            <input type="submit" value="Login"
                style="width: 100%; padding: 0.75rem; background: var(--brand-maroon); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
        </form>
        <?php if (isset($error)) { ?>
            <p style="color: #ff4444; text-align: center; margin-top: 1rem;">
                <?php echo $error; ?>
            </p>
        <?php } ?>
        <p style="font-size: 0.875rem; color: var(--text-secondary); text-align: center; margin-top: 1.5rem;">
            Tokens are provided by the Secretary and are valid for 10 minutes.
        </p>
    </div>
<?php };

$a->assemble();
