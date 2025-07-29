<?php
/**
 * Test file to verify include paths are working
 */

echo "Testing include paths...\n";

// Test Auth.php
try {
    require_once __DIR__ . '/classes/Auth.php';
    echo "✅ Auth.php loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Auth.php failed: " . $e->getMessage() . "\n";
}

// Test RBAC.php
try {
    require_once __DIR__ . '/classes/RBAC.php';
    echo "✅ RBAC.php loaded successfully\n";
} catch (Exception $e) {
    echo "❌ RBAC.php failed: " . $e->getMessage() . "\n";
}

// Test Database.php
try {
    require_once __DIR__ . '/config/Database.php';
    echo "✅ Database.php loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Database.php failed: " . $e->getMessage() . "\n";
}

echo "Path test completed.\n";
?> 