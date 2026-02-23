<?php

enum Stylesheet: string
{
	case Main = "/style/main.php";
}

class Assembler
{

	private string $body_header_title;

	public string $head_title;
	public Stylesheet $head_stylesheet;
	public string $body_main_id;
	public ?string $body_header_title_override = null;
	public Closure $body_main;
	public ?array $custom_nav = null;

	public function __construct(string $body_header_title)
	{
		$this->head_title = 'UoP Career Fair 2026';
		$this->head_stylesheet = Stylesheet::Main;

		$this->body_header_title = $body_header_title;

		$this->body_main_id = 'some-main';
		$this->body_main = function () { ?>
			<p>This page has no content yet.</p><?php };

		if (session_status() === PHP_SESSION_NONE) {
			$uri = $_SERVER['REQUEST_URI'] ?? '';
			// Sensitive areas like Superadmin/Secretary/Gatekeeper should require login if tab closes
			$is_sensitive = (strpos($uri, '/costas/') !== false || strpos($uri, '/os.php') !== false);

			if ($is_sensitive) {
				ini_set('session.gc_maxlifetime', 3600); // 1 hour idle
				ini_set('session.cookie_lifetime', 0);   // Session-only (expires on tab close)
			} else {
				// Ensure sessions last for the duration of the event (30 days) for candidates
				ini_set('session.gc_maxlifetime', 86400 * 30);
				ini_set('session.cookie_lifetime', 86400 * 30);
			}
			session_start();
		}
	}

	public function assemble(): void
	{
		header('cache-control: no-cache, no-store, must-revalidate');

		// Conditional background logic
		$uri = $_SERVER['REQUEST_URI'];
		$path = parse_url($uri, PHP_URL_PATH);
		$show_bg = ($path === '/' || $path === '/index.php' || strpos($path, '/suggestions.php') === 0);
		$body_class = $show_bg ? 'bg-pattern' : '';
		?>
		<!DOCTYPE html>
		<html lang="en">

		<head><?php $this->head(); ?></head>

		<body class="<?= $body_class ?>"><?php $this->body(); ?></body>

		</html>
		<?php
	}

	protected function head(): void
	{
		?>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="<?= $this->head_stylesheet->value ?>?cachebuster=<?= date("YmdH") ?>">
		<link rel="shortcut icon" href="/resources/favicon/normal.svg?cachebuster=<?= date("YmdH") ?>" type="image/x-icon">
		<?php if ((mt_rand() / mt_getrandmax()) > 0.99) { ?>
			<link rel="stylesheet" href="/style/color-shiny.css?cachebuster=<?= date("YmdH") ?>">
			<link rel="shortcut icon" href="/resources/favicon/shiny.svg?cachebuster=<?= date("YmdH") ?>" type="image/x-icon">
		<?php } ?>
		<title><?= $this->head_title ?></title>
		<script src="/script/header.js" defer></script>
		<?php
	}

	protected function body(): void
	{
		?>

		<nav class="glass-nav">
			<div class="nav-content">
				<div class="nav-main-row">
					<div class="header-logos">
						<a href="https://careerday.fet.uop.gr/" class="logo-link" style="box-shadow: none;">
							<h2 class="animate-fade-in">Career Fair <span class="gradient-text">2026</span></h2>
						</a>
						<a href="https://www.uop.gr/sholi-oikonomias-kai-tehnologias" target="_blank" style="box-shadow: none;">
							<img src="/resources/images/UOP.svg" alt="UoP Logo" class="nav-logo">
						</a>
						<a href="https://unistarthubs.gr/en/" target="_blank" style="box-shadow: none;">
							<img src="/resources/images/UNISTART.svg" alt="UniStart Logo" class="nav-logo">
						</a>
					</div>

					<button class="hamburger" aria-label="Toggle menu">
						<span></span><span></span><span></span>
					</button>
					<div class="nav-links">
						<?= $this->body_header_nav() ?>
					</div>
				</div>
			</div>
		</nav>

		<?php
		$_title = $this->body_header_title_override ?? $this->body_header_title;
		if ($_title !== ''): ?>
			<div style="text-align: center; margin-top: 2rem; margin-bottom: 2rem;">
				<h1 class="text-primary"><?= $_title ?></h1>
			</div>

			<hr>
		<?php endif; ?>

		<main id="<?= $this->body_main_id ?>">
			<?php ($this->body_main)(); ?>
		</main>

		<div class="spacer"></div>

		<hr>

		<footer>
			<p style="text-align: center;">
				<a href="https://www.uop.gr/">University of the Peloponnese</a> © Career Fair 2026 • <strong>Interview
					Hub</strong>
			</p>
		</footer>

		<script>
			// UserWay Accessibility Widget Configuration
			(function () {
				window._userway_config = {
					position: '5', // Standard Bottom Left
					color: '#F7911E', // Career Fair Orange
					language: 'en',
					mobile: true,
					account: 'L5D5s7Yq3N' // Official UoP Account ID
				};

				var s = document.createElement("script");
				s.setAttribute("src", "https://cdn.userway.org/widget.js");
				s.setAttribute("data-account", "L5D5s7Yq3N");
				document.body.appendChild(s);
			})();
		</script>

		<?php
	}

	protected function body_header_nav(): void
	{
		if ($this->custom_nav !== null) {
			foreach ($this->custom_nav as $label => $link) {
				echo '<a href="' . $link . '">' . $label . '</a>';
			}
			return;
		}
		?>
		<a href="/">Home</a>
		<a href="/queues.php" class="btn-hub">Interviews</a>
		<a href="https://careerday.fet.uop.gr/companies.php">Companies</a>
		<a href="/suggestions.php">Suggestions</a>
		<?php
		// Show candidate nav link dynamically
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/candidate_auth.php';
		$_cand = candidate_session_get();

		// If a company is logged in, show their dashboard button
		if (AssemblerOperate::operator_is(Operator::Company)): ?>
			<a href="/company_dashboard.php" class="cta-nav"
				style="background: linear-gradient(135deg, var(--brand-green) 0%, #5d8f28 100%) !important; box-shadow: 0 4px 15px rgba(136, 197, 64, 0.3);">My
				Dashboard</a>
		<?php endif;

		if ($_cand !== false): ?>
			<a href="/candidate_dashboard.php" class="cta-nav">My Queues</a>
		<?php else: ?>
			<a href="/candidate_register.php" class="cta-nav">Register</a>
		<?php endif;
	?>
	<?php
	}

}

enum Operator: string
{
	case Secretary = 'secretary';
	case Gatekeeper = 'gatekeeper';
	case Company = 'company';
}

class AssemblerOperate extends Assembler
{

	private static string $SESSION_OPERATOR_ARRAY = "session_operator_array_#@)_SASD+)K";

	public function __construct(string $body_header_title)
	{
		parent::__construct($body_header_title);

		$this->head_title = 'Operate: ' . $this->head_title;

		if (
			isset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]) === false
			|| is_array($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]) === false
		) {
			$_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY] = [];
		}
	}

	protected function body_header_nav(): void
	{
		if ($this->custom_nav !== null) {
			foreach ($this->custom_nav as $label => $link) {
				echo '<a href="' . $link . '">' . $label . '</a>';
			}
			return;
		}
		?>
		<a href="/">Home</a>
		<a href="/costas/vasilakis.php">Authorize</a>
		<a href="/costas/vasilakis.php?unauthorize">Unauthorize</a>
		<?php
		$operators = [];

		if ($this->operator_is(Operator::Secretary)) {
			array_push($operators, '<a href="/costas/' . Operator::Secretary->value . '.php">Secretary</a>');
		}
		if ($this->operator_is(Operator::Gatekeeper)) {
			array_push($operators, '<a href="/costas/' . Operator::Gatekeeper->value . '.php">Gatekeeper</a>');
		}

		if (sizeof($operators) > 0) {
			echo '<div style="width: 100%"></div>' . implode($operators);
		}
	?>
	<?php
	}

	public function operator_challenge(string $password): false
	{
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

		$ts = '';

		$type = database()->operator_mapping($password, $ts) ?? '';
		$type = Operator::tryFrom($type);

		if ($type === null) {
			return false;
		}

		$_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][$type->value] = $ts;
		header("Location: /costas/{$type->value}.php");
		exit;
	}

	public function company_challenge(string $token): false
	{
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

		$company_id = database()->company_mapping($token);

		if ($company_id === false) {
			return false;
		}

		$_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][Operator::Company->value] = $company_id;

		// Make session persistent for 30 days
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), $_COOKIE[session_name()], time() + (86400 * 30), "/", "", true, true);
		}

		header("Location: /company_dashboard.php");
		exit;
	}

	public static function operator_is(Operator $operator): bool
	{
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		if (
			isset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]) === false
			|| isset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][$operator->value]) === false
		) {
			return false;
		}

		$session_val = $_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][$operator->value];

		if ($operator === Operator::Company) {
			return database()->company_still_alive(intval($session_val));
		}

		return database()->operator_still_alive($session_val);
	}

	public function operator_ensure(Operator $operator)
	{
		if (AssemblerOperate::operator_is($operator) === false) {
			header('Location: /costas/vasilakis.php');
			exit;
		}
	}

	function operator_clear()
	{
		unset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]);
		$_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY] = [];
	}

	public static function company_id(): ?int
	{
		return $_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][Operator::Company->value] ?? null;
	}

}

class AssemblerOperateCompany extends AssemblerOperate
{

	public function __construct()
	{
		parent::__construct("Company Dashboard");
	}

	public function assemble(): void
	{
		$this->operator_ensure(Operator::Company);
		parent::assemble();
	}

	public function operator_ensure(Operator $operator): void
	{
		if (self::operator_is($operator) === false) {
			header("Location: /company_login.php");
			exit;
		}
	}

	protected function head(): void
	{
		parent::head();
		?>
		<style>
			html {
				scrollbar-gutter: stable;
			}
		</style>
		<?php
	}

}

class AssemblerOperateSecretary extends AssemblerOperate
{

	public function __construct()
	{
		parent::__construct("Secretary");
	}

	protected function head(): void
	{
		parent::head();
		?>
		<style>
			html {
				scrollbar-gutter: stable;
			}
		</style>
		<?php
	}

}
