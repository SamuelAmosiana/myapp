<?php
/**
 * Login Processing Script for ClassReserve CHAU
 * File: process_login.php
 */

// Start session
session_start();

// Include required files
require_once __DIR__ . '/../classes/Auth.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get and validate input
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    if (empty($role)) {
        throw new Exception('Role is required');
    }
    
    require_once __DIR__ . '/../classes/Auth.php';
    $auth = new Auth();
    
    if ($role === 'student' || $role === 'class_rep') {
        $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
        if (empty($student_id)) {
            throw new Exception('Student ID is required');
        }
        // Validate student ID format (basic validation)
        if (!preg_match('/^[A-Z0-9]+$/i', $student_id)) {
            throw new Exception('Invalid Student ID format');
        }
        // Login using student_id (as both username and password)
        $loginResult = $auth->login($student_id, $role);
        $response = $loginResult;
    } elseif ($role === 'admin' || $role === 'lecturer') {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        if (empty($email) || empty($password)) {
            throw new Exception('Email and password are required');
        }
        // Login using email and password
        $loginResult = $auth->loginWithEmail($email, $password, $role);
        $response = $loginResult;
    } else {
        throw new Exception('Invalid role selected');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    // Log error for debugging
    error_log("Login error: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
exit;
?>