<?php
require_once 'includes/auth.php';
Auth::init();

// Simple router/entry point
if (Auth::check()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
