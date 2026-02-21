<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/candidate_auth.php';

candidate_session_clear();
header('Location: /candidate_register.php');
exit;
