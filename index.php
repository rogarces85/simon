<?php
require_once 'includes/auth.php';
Auth::init();

// If logged in, go to dashboard
if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

// Else, show the high-fidelity landing page
include 'views/landing_view.php';
?>