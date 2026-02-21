<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Company Logout');
$a->operator_clear();

header('Location: /company_login.php');
exit;
