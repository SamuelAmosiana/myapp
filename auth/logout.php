<?php
/**
 * Logout Script for ClassReserve CHAU
 * File: auth/logout.php
 */

require_once __DIR__ . '/../classes/Auth.php';

// Create Auth instance and logout
$auth = new Auth();
$auth->logout();

// Redirect to login page with success message
header('Location: login.php?success=' . urlencode('You have been logged out successfully.'));
exit;
?>