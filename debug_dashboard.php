<?php
/**
 * Debug Dashboard - ClassReserve CHAU
 */

// Start session
session_start();

// Debug: Show current directory
echo "Current directory: " . __DIR__ . "<br>";

// Debug: Test each include separately
echo "Testing includes...<br>";

try {
    echo "1. Testing Auth.php...<br>";
    require_once __DIR__ . '/classes/Auth.php';
    echo "✅ Auth.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Auth.php failed: " . $e->getMessage() . "<br>";
}

try {
    echo "2. Testing RBAC.php...<br>";
    require_once __DIR__ . '/classes/RBAC.php';
    echo "✅ RBAC.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ RBAC.php failed: " . $e->getMessage() . "<br>";
}

echo "Debug completed.<br>";
?> 