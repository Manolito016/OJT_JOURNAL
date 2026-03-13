<?php
/**
 * Environment Variable Loader
 * Loads environment variables from .env file
 */

/**
 * Load environment variables from .env file
 * @param string $path Path to .env file
 * @return bool
 */
function loadEnv($path = null) {
    if ($path === null) {
        // Look for .env in parent directory (project root)
        $path = __DIR__ . '/../.env';
    }
    
    if (!file_exists($path)) {
        // Try .env.example as fallback
        $examplePath = __DIR__ . '/../.env.example';
        if (file_exists($examplePath)) {
            $path = $examplePath;
        } else {
            error_log('Warning: Neither .env nor .env.example found');
            return false;
        }
    }
    
    if (!is_readable($path)) {
        error_log('Error: Cannot read .env file');
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
    
    return true;
}

/**
 * Get environment variable with optional default
 * @param string $key Environment variable name
 * @param mixed $default Default value if not set
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? null;
    }
    
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    
    // Handle boolean values
    if (strtolower($value) === 'true') {
        return true;
    }
    if (strtolower($value) === 'false') {
        return false;
    }
    
    // Handle numeric values
    if (is_numeric($value)) {
        return $value + 0; // Convert to int or float
    }
    
    return $value;
}
?>
