<?php
/**
 * API Router - Handles all backend requests
 * This file proxies requests to the actual process.php in src/
 */

// Start output buffering to catch any stray output
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Include the actual process.php from src directory
try {
    require_once __DIR__ . '/../src/process.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
