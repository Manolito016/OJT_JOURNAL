<?php
/**
 * Configuration file for Weekly Journal Report Generator
 *
 * Environment variables are loaded from .env file
 * DO NOT hardcode sensitive credentials in this file
 */

// Load environment variables from .env file
require_once __DIR__ . '/env.php';
loadEnv();

// Alibaba Qwen API Configuration (OpenRouter)
// Read from environment variables for security
define('QWEN_API_KEY', env('QWEN_API_KEY', ''));
define('QWEN_API_ENDPOINT', env('QWEN_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions'));
define('QWEN_VISION_MODEL', env('QWEN_VISION_MODEL', 'qwen/qwen-2-vl-7b-instruct'));
define('QWEN_TEXT_MODEL', env('QWEN_TEXT_MODEL', 'qwen/qwen-2.5-72b-instruct'));

// Database Configuration
// DB is in parent directory: ../db/journal.db
define('DB_PATH', env('DB_PATH', realpath(__DIR__ . '/../db/journal.db') ?: __DIR__ . '/../db/journal.db'));

// Upload Configuration
// Uploads are in public directory: ../public/uploads/
define('UPLOAD_DIR', env('UPLOAD_DIR', __DIR__ . '/../public/uploads/'));
define('MAX_FILE_SIZE', env('MAX_FILE_SIZE', 5 * 1024 * 1024)); // 5MB max

// Parse allowed types from comma-separated string
$allowedTypesString = env('ALLOWED_TYPES', 'image/jpeg,image/png,image/gif,image/webp');
define('ALLOWED_TYPES', array_map('trim', explode(',', $allowedTypesString)));

/**
 * Get database connection
 * @return PDO SQLite database connection
 */
function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Initialize database if not exists
            initializeDatabase($pdo);

        } catch (PDOException $e) {
            // Return JSON error instead of plain text
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    return $pdo;
}

/**
 * Initialize database tables
 * @param PDO $pdo Database connection
 */
function initializeDatabase($pdo) {
    // Create OJT entries table
    $sql = "CREATE TABLE IF NOT EXISTS ojt_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        user_description TEXT,
        entry_date DATE NOT NULL,
        ai_enhanced_description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);

    // Create entry images table
    $sql = "CREATE TABLE IF NOT EXISTS entry_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entry_id INTEGER NOT NULL,
        image_path TEXT NOT NULL,
        image_order INTEGER DEFAULT 0,
        ai_description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (entry_id) REFERENCES ojt_entries(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);

    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_entry_date ON ojt_entries(entry_date)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_entry_images_entry_id ON entry_images(entry_id)");
    
    // Keep old table for backward compatibility (optional)
    $pdo->exec("CREATE TABLE IF NOT EXISTS journal_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        image_path TEXT NOT NULL,
        ai_description TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

/**
 * Validate API key is set
 * @return bool
 */
function isApiKeyConfigured() {
    return strpos(QWEN_API_KEY, 'sk-or-') === 0 && strlen(QWEN_API_KEY) > 20;
}
?>
