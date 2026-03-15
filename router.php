<?php
// Router script for PHP built-in development server

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$queryString = $_SERVER['QUERY_STRING'] ?? '';

error_log("Requested URI: " . $uri . " Query: " . $queryString);

// Remove leading slash for relative path
$uri = ltrim($uri, '/');

// Handle process.php requests - route to src/process.php
if (strpos($uri, 'process.php') === 0) {
    $_GET['action'] = isset($_GET['action']) ? $_GET['action'] : '';
    error_log("Routing to src/process.php with action: " . $_GET['action']);
    include __DIR__ . '/src/process.php';
    return true;
}

// Handle empty path (root)
if ($uri === '' || $uri === '/') {
    $uri = 'index.php';
}

// Build the file path using absolute path
$publicDir = 'C:\Projects\OJT\OJT-AI-Journal-Report-Generator-main-20260312T151557Z-3-001\OJT-AI-Journal-Report-Generator-main\public';
$file = $publicDir . '\\' . str_replace('/', '\\', $uri);

error_log("Looking for file: " . $file);
error_log("File exists: " . (file_exists($file) ? 'yes' : 'no'));

// Check if file exists and is readable
if (file_exists($file) && is_file($file)) {
    error_log("Serving file: " . $file);
    // Let PHP serve the file
    return false;
}

// File not found - return 404
http_response_code(404);
echo "404 Not Found - File: " . htmlspecialchars($file);
