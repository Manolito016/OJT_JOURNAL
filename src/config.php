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

// ============================================
// AI SYSTEM CONTEXT - MASTER RULES
// Applied to ALL API calls for consistent voice
// ============================================
define('AI_SYSTEM_CONTEXT', 
    "You are helping a Filipino BSIT student write their OJT journal " .
    "for Ilocos Sur Polytechnic State College (ISPSC). " .
    "ALWAYS write in FIRST PERSON PAST TENSE as the student ('I attended', 'I learned', 'I used'). " .
    "NEVER use future tense ('I will', 'I plan to', 'I expect to'). " .
    "NEVER use the word 'participants', 'trainees', or 'trainee'. " .
    "NEVER write motivational closing sentences. " .
    "NEVER exceed 150 words per entry description. " .
    "NEVER use corporate buzzwords (comprehensive, leveraging, synergy, cutting-edge, immersive, real-world scenarios). " .
    "Always name specific tools, software, and concepts. " .
    "Write simply and honestly like a real college student writing about what they DID today."
);

// ============================================
// LLM Provider Configuration
// Multi-provider setup with automatic fallback
// Provider order: Groq (Primary) → Google (Fallback)
// ============================================

// Primary: Groq - FASTEST INFERENCE (100+ tokens/second)
define('GROQ_API_KEY', env('GROQ_API_KEY', ''));
define('GROQ_API_ENDPOINT', env('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'));
define('GROQ_TEXT_MODEL', env('GROQ_TEXT_MODEL', 'llama-3.3-70b-versatile'));
define('GROQ_VISION_MODEL', env('GROQ_VISION_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct'));

// Fallback 1: Google AI Studio (Gemini) - 250K tokens/min, 250 requests/day free
define('GOOGLE_API_KEY', env('GOOGLE_API_KEY', ''));
define('GOOGLE_API_ENDPOINT', env('GOOGLE_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'));
// Model fallback configuration (auto-switches when rate limit reached)
define('GEMINI_PRIMARY_MODEL', env('GEMINI_PRIMARY_MODEL', 'gemini-2.5-flash'));
define('GEMINI_FALLBACK_MODEL_1', env('GEMINI_FALLBACK_MODEL_1', 'gemini-3.1-flash-lite'));
define('GEMINI_FALLBACK_MODEL_2', env('GEMINI_FALLBACK_MODEL_2', 'gemini-2.5-flash-lite'));
// Legacy support (for backward compatibility)
define('GEMINI_TEXT_MODEL', env('GEMINI_TEXT_MODEL', env('GEMINI_PRIMARY_MODEL', 'gemini-2.5-flash')));
define('GEMINI_VISION_MODEL', env('GEMINI_VISION_MODEL', env('GEMINI_PRIMARY_MODEL', 'gemini-2.5-flash')));

// Legacy OpenRouter/Qwen support (process.php still references QWEN_* constants)
// Map to GROQ as fallback since we switched providers
define('QWEN_API_KEY', env('QWEN_API_KEY', env('GROQ_API_KEY', '')));
define('QWEN_API_ENDPOINT', env('QWEN_API_ENDPOINT', env('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions')));
define('QWEN_TEXT_MODEL', env('QWEN_TEXT_MODEL', env('GROQ_TEXT_MODEL', 'llama-3.3-70b-versatile')));
define('QWEN_VISION_MODEL', env('QWEN_VISION_MODEL', env('GROQ_VISION_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct')));

// DeepSeek (used in process.php analyzeImageWithDeepSeek)
define('DEEPSEEK_API_KEY', env('DEEPSEEK_API_KEY', ''));
define('DEEPSEEK_API_ENDPOINT', env('DEEPSEEK_API_ENDPOINT', 'https://api.deepseek.com/v1/chat/completions'));
define('DEEPSEEK_TEXT_MODEL', env('DEEPSEEK_TEXT_MODEL', 'deepseek-chat'));
define('DEEPSEEK_VISION_MODEL', env('DEEPSEEK_VISION_MODEL', 'deepseek-chat'));

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

    // Create OJT Report Info table (Company Profile & Student Info)
    $sql = "CREATE TABLE IF NOT EXISTS ojt_report_info (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_name TEXT,
        student_course TEXT,
        school_year TEXT,
        company_name TEXT,
        company_location TEXT,
        company_nature_of_business TEXT,
        company_background TEXT,
        ojt_start_date TEXT,
        ojt_end_date TEXT,
        daily_hours TEXT,
        purpose_role TEXT,
        background_action_plan TEXT,
        conclusion TEXT,
        recommendation_students TEXT,
        recommendation_company TEXT,
        recommendation_school TEXT,
        acknowledgment TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Insert default row if not exists
    $checkSql = "SELECT COUNT(*) as count FROM ojt_report_info";
    $result = $pdo->query($checkSql)->fetch();
    if ($result['count'] == 0) {
        $insertSql = "INSERT INTO ojt_report_info (id, student_name, student_course, school_year) VALUES (1, 'JUAN DELA CRUZ', 'Bachelor of Science in Information Technology', 'S.Y. 2025 - 2026')";
        $pdo->exec($insertSql);
    }
}

/**
 * Validate API key is set
 * @return bool
 */
function isApiKeyConfigured() {
    // Check if ANY provider is configured (multi-provider support)
    // Groq is primary, Google is fallback
    return !empty(GROQ_API_KEY) || !empty(GOOGLE_API_KEY);
}

/**
 * Get preferred provider order
 * @return array List of providers in priority order
 */
function getProviderPriority() {
    $providers = [];

    // Groq (highest priority - fastest inference)
    if (!empty(GROQ_API_KEY)) {
        $providers[] = 'groq';
    }

    // Google AI Studio (fallback - best free tier)
    if (!empty(GOOGLE_API_KEY)) {
        $providers[] = 'google';
    }

    return $providers;
}

/**
 * Check if specific provider is configured
 * @param string $provider Provider name ('groq', 'google')
 * @return bool
 */
function isProviderConfigured($provider) {
    switch ($provider) {
        case 'groq':
            return !empty(GROQ_API_KEY);
        case 'google':
            return !empty(GOOGLE_API_KEY);
        default:
            return false;
    }
}
?>
