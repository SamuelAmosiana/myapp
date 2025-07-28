<?php
/**
 * Login Processing Script for ClassReserve CHAU
 * File: process_login.php
 */

// Start session
session_start();

// Include required files
require_once 'classes/Auth.php';

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
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
    
    if (empty($student_id)) {
        throw new Exception('Student ID is required');
    }
    
    // Validate student ID format (basic validation)
    if (!preg_match('/^[A-Z0-9]+$/', $student_id)) {
        throw new Exception('Invalid Student ID format');
    }
    
    // Create Auth instance and attempt login
    $auth = new Auth();
    $loginResult = $auth->login($student_id);
    
    // Return the result
    $response = $loginResult;
    
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