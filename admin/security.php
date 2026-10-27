<?php
// admin/security.php

session_start();

// ===== SETTINGS =====
$timeout_duration = 1800; // 30 minutes

// Check login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Session hijack protection
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Auto logout after inactivity
if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time();
