<?php
/**
 * Process.php - Backend handler for OJT Journal entries
 *
 * Handles:
 * - OJT entry creation with title, description, and multiple images
 * - Qwen API integration for image analysis and description enhancement
 * - Database operations for OJT journal entries
 */

// Start output buffering to catch any stray output
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Catch fatal errors and return as JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Server error: ' . $error['message']]);
    }
});

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

/**
 * Main request handler
 */
try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'createEntry':
            createOJTEntry();
            break;
        case 'getWeekly':
            getWeeklyReport();
            break;
        case 'delete':
            deleteEntry();
            break;
        case 'generateNarrative':
            generateNarrativeReport();
            break;
        case 'updateDescription':
            updateDescription();
            break;
        case 'updateEntry':
            updateEntry();
            break;
        case 'updateEntryDescription':
            updateEntryDescription();
            break;
        case 'analyzeImagesForEntry':
            analyzeImagesForEntry();
            break;
        case 'updateImageDescription':
            updateImageDescription();
            break;
        case 'regenerateImageAnalysis':
            regenerateImageAnalysis();
            break;
        case 'enhanceDescription':
            enhanceDescription();
            break;
        case 'customizeEntryWithAI':
            customizeEntryWithAI();
            break;
        case 'generateISPSCReport':
            generateISPSCReport();
            break;
        case 'generateDownloadReport':
            generateDownloadReport();
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}

/**
 * Create OJT entry with title, description, and multiple images
 */
function createOJTEntry() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured. Please set your Qwen API key in config.php'], 500);
    }

    $title = $_POST['title'] ?? '';
    $userDescription = $_POST['description'] ?? '';
    $entryDate = $_POST['entry_date'] ?? date('Y-m-d');

    // Sanitize inputs
    $title = htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8');
    $userDescription = htmlspecialchars(trim($userDescription), ENT_QUOTES, 'UTF-8');
    $entryDate = trim($entryDate);

    // Validate title
    if (empty($title)) {
        jsonResponse(['error' => 'Title is required'], 400);
    }
    
    if (strlen($title) < 3) {
        jsonResponse(['error' => 'Title must be at least 3 characters long'], 400);
    }
    
    if (strlen($title) > 200) {
        jsonResponse(['error' => 'Title must not exceed 200 characters'], 400);
    }

    // Validate description length (optional field)
    if (!empty($userDescription) && strlen($userDescription) > 5000) {
        jsonResponse(['error' => 'Description must not exceed 5000 characters'], 400);
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)) {
        jsonResponse(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
    }
    
    // Validate date is not in the future
    $today = date('Y-m-d');
    if ($entryDate > $today) {
        jsonResponse(['error' => 'Entry date cannot be in the future'], 400);
    }
    
    // Validate date is reasonable (not older than 1 year)
    $oneYearAgo = date('Y-m-d', strtotime('-1 year'));
    if ($entryDate < $oneYearAgo) {
        jsonResponse(['error' => 'Entry date is too old. Maximum 1 year back'], 400);
    }

    // Check if images were uploaded
    if (empty($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        jsonResponse(['error' => 'At least one image is required'], 400);
    }
    
    // Validate number of images
    $uploadCount = count($_FILES['images']['name']);
    if ($uploadCount > 10) {
        jsonResponse(['error' => 'Maximum 10 images allowed per entry'], 400);
    }

    $pdo = getDbConnection();

    // Create the OJT entry first
    $stmt = $pdo->prepare("
        INSERT INTO ojt_entries (title, user_description, entry_date, ai_enhanced_description, created_at)
        VALUES (:title, :user_desc, :entry_date, :ai_desc, :created_at)
    ");

    // Generate initial enhanced description (will be updated after AI analysis)
    $initialEnhanced = $userDescription ?: 'Image analysis pending...';

    $stmt->execute([
        ':title' => $title,
        ':user_desc' => $userDescription,
        ':entry_date' => $entryDate,
        ':ai_desc' => $initialEnhanced,
        ':created_at' => date('Y-m-d H:i:s')
    ]);

    $entryId = $pdo->lastInsertId();

    // Process images
    $uploadCount = count($_FILES['images']['name']);
    $imageResults = [];

    for ($i = 0; $i < $uploadCount; $i++) {
        $file = [
            'name' => $_FILES['images']['name'][$i],
            'type' => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'error' => $_FILES['images']['error'][$i],
            'size' => $_FILES['images']['size'][$i]
        ];

        $result = processImage($file, $entryId, $i);
        $imageResults[] = $result;
    }

    // Collect all AI descriptions for enhancement
    $aiDescriptions = array_filter(array_column($imageResults, 'ai_description'));
    
    // Generate enhanced description combining user input and AI analysis
    $enhancedDescription = generateEnhancedDescription($userDescription, $aiDescriptions, $title);
    
    // Update the entry with enhanced description
    $stmt = $pdo->prepare("UPDATE ojt_entries SET ai_enhanced_description = :ai_desc WHERE id = :id");
    $stmt->execute([
        ':ai_desc' => $enhancedDescription,
        ':id' => $entryId
    ]);

    jsonResponse([
        'success' => true,
        'entry_id' => $entryId,
        'images' => $imageResults
    ]);
}

/**
 * Process a single image and save to database
 */
function processImage($file, $entryId, $order) {
    // Validate upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error for file {$file['name']}: " . getUploadErrorMessage($file['error']));
        return ['error' => getUploadErrorMessage($file['error'])];
    }

    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log("File too large: {$file['name']} ({$file['size']} bytes)");
        return ['error' => 'File too large. Max: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }

    // Validate file type - also check by extension as backup
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file['type'], ALLOWED_TYPES) && !in_array($extension, $allowedExtensions)) {
        error_log("Invalid file type: {$file['name']} ({$file['type']})");
        return ['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP'];
    }

    // Ensure upload directory exists and is writable
    if (!is_dir(UPLOAD_DIR)) {
        error_log("Upload directory does not exist: " . UPLOAD_DIR);
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            return ['error' => 'Server configuration error: Cannot create upload directory'];
        }
    }
    
    if (!is_writable(UPLOAD_DIR)) {
        error_log("Upload directory is not writable: " . UPLOAD_DIR);
        return ['error' => 'Server configuration error: Upload directory not writable'];
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'ojt_' . $entryId . '_' . uniqid() . '_' . time() . '.' . $extension;
    $destination = UPLOAD_DIR . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        error_log("Failed to move uploaded file: {$file['tmp_name']} to {$destination}");
        return ['error' => 'Failed to save image. Please try again.'];
    }

    // Verify file was uploaded successfully
    if (!file_exists($destination)) {
        error_log("Uploaded file does not exist at destination: {$destination}");
        return ['error' => 'File upload verification failed'];
    }

    // Analyze image with AI
    $imagePath = 'uploads/' . $filename;
    $aiDescription = analyzeImageWithQwen($destination);

    if (is_array($aiDescription) && isset($aiDescription['error'])) {
        error_log("AI analysis failed for {$filename}: " . $aiDescription['error']);
        $aiDescription = 'Image analysis unavailable';
    }

    // Save image to database
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO entry_images (entry_id, image_path, image_order, ai_description, created_at)
            VALUES (:entry_id, :image_path, :order, :ai_desc, :created_at)
        ");

        $stmt->execute([
            ':entry_id' => $entryId,
            ':image_path' => $imagePath,
            ':order' => $order,
            ':ai_desc' => $aiDescription,
            ':created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'id' => $pdo->lastInsertId(),
            'image_path' => $imagePath,
            'ai_description' => $aiDescription
        ];
    } catch (PDOException $e) {
        error_log("Database error saving image: " . $e->getMessage());
        // Clean up the uploaded file if database insert fails
        if (file_exists($destination)) {
            unlink($destination);
        }
        return ['error' => 'Failed to save image to database'];
    }
}

/**
 * Analyze image using Qwen API via OpenRouter with auto-fallback to other models
 */
function analyzeImageWithQwen($imagePath) {
    // Build list of models to try (primary + fallbacks)
    $modelsToTry = [QWEN_VISION_MODEL];
    
    // Add fallback models if configured
    $fallback1 = env('QWEN_VISION_FALLBACK_1', '');
    $fallback2 = env('QWEN_VISION_FALLBACK_2', '');
    
    if (!empty($fallback1)) {
        $modelsToTry[] = $fallback1;
    }
    if (!empty($fallback2)) {
        $modelsToTry[] = $fallback2;
    }
    
    // Try each model in order until one succeeds
    foreach ($modelsToTry as $model) {
        try {
            $result = tryAnalyzeWithModel($imagePath, $model);
            
            // If successful (not an error array), return the result
            if (!is_array($result) || !isset($result['error'])) {
                // Log which model succeeded (for debugging)
                if ($model !== QWEN_VISION_MODEL) {
                    error_log("Image analysis succeeded with fallback model: {$model}");
                }
                return $result;
            }
            
            // Log failure and try next model
            error_log("Image analysis failed with model {$model}: " . ($result['error'] ?? 'Unknown error'));
            
        } catch (Exception $e) {
            error_log("Exception with model {$model}: " . $e->getMessage());
            // Continue to next model
        }
    }
    
    // All models failed
    error_log("All vision models failed for image analysis");
    return ['error' => 'Unable to analyze image. All configured vision models failed.'];
}

/**
 * Helper function: Try to analyze image with a specific model
 * @param string $imagePath Path to image file
 * @param string $model Model name to use
 * @return string|array Analysis result or error array
 */
function tryAnalyzeWithModel($imagePath, $model) {
    // Convert image to base64
    $imageData = base64_encode(file_get_contents($imagePath));
    $mimeType = mime_content_type($imagePath);
    $base64Image = 'data:' . $mimeType . ';base64,' . $imageData;

    // Enhanced prompt: detailed, structured analysis for OJT journal
    $prompt = "Analyze this OJT (On-the-Job Training) image in detail:\n\n";
    $prompt .= "1. MAIN SUBJECT: Describe the primary activity, person, or object shown\n";
    $prompt .= "2. TOOLS/TECHNOLOGIES: Identify any software, hardware, tools, or equipment visible\n";
    $prompt .= "3. LEARNING OBJECTIVE: Explain what skill or knowledge is being developed\n";
    $prompt .= "4. NOTABLE DETAILS: Mention any important elements (collaboration, screens, code, documents, etc.)\n";
    $prompt .= "5. PROFESSIONAL CONTEXT: Describe how this relates to IT training or workplace skills\n\n";
    $prompt .= "Write 2-3 detailed paragraphs in a professional academic tone suitable for an OJT report. Be specific and descriptive.";

    $requestData = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => $base64Image]
                    ],
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'max_tokens' => 350,
        'temperature' => 0.6
    ];

    $ch = curl_init(QWEN_API_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . QWEN_API_KEY,
            'HTTP-Referer: http://localhost:8000',
            'X-Title: OJT Journal Generator'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'API connection failed'];
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $result['message'] ?? $result['error']['message'] ?? 'Unknown API error';
        error_log("API response for model {$model}: " . json_encode($result));
        return ['error' => 'API error (' . $httpCode . '): ' . $errorMsg];
    }

    if (isset($result['choices'][0]['message']['content'])) {
        return trim($result['choices'][0]['message']['content']);
    }

    // Log the full response for debugging
    error_log("Unexpected API response format for model {$model}: " . json_encode($result));
    return ['error' => 'Unexpected API response format. Check logs for details.'];
}

/**
 * Clean up AI-generated description
 * Removes unwanted prefixes, titles, bullet points, etc.
 */
function cleanDescription($text) {
    // Remove "Title:" prefix and anything before first newline
    $text = preg_replace('/^Title:\s*[^\n]+\n/i', '', $text);
    
    // Remove bullet point patterns at the start
    $text = preg_replace('/^[\-\*•]\s*/m', '', $text);
    
    // Remove numbered list patterns at the start
    $text = preg_replace('/^\d+\.\s*/m', '', $text);
    
    // Remove common unwanted phrases at the beginning
    $unwantedPatterns = [
        '/^Here\'s? a\s+/i',
        '/^Here\'s? my\s+/i',
        '/^In this\s+/i',
        '/^During this\s+/i',
        '/^This (entry|journal|post|report) (covers|describes|shows|discusses)/i',
        '/^This (session|meeting|day|week) (covers|describes|shows|discusses)/i',
    ];
    
    foreach ($unwantedPatterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }
    
    // Remove lines that are just labels or headers
    $lines = explode("\n", $text);
    $filteredLines = array_filter($lines, function($line) {
        $trimmed = trim($line);
        // Remove lines that are just bullet points or very short
        if (strlen($trimmed) < 10) return false;
        if (preg_match('/^[\-\*•]$/', $trimmed)) return false;
        return true;
    });
    
    $text = implode("\n", $filteredLines);
    
    // Clean up multiple newlines
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    
    return trim($text);
}

/**
 * Generate enhanced description combining user input and AI analysis
 */
function generateEnhancedDescription($userDescription, $aiDescriptions, $title) {
    if (empty($userDescription) && empty($aiDescriptions)) {
        return 'No description available';
    }

    // If only AI descriptions, combine them
    if (empty($userDescription)) {
        return implode(' ', $aiDescriptions);
    }

    // If only user description, enhance it with AI
    if (empty($aiDescriptions)) {
        return enhanceUserDescriptionWithAI($userDescription, $title);
    }

    // Combine user description with AI image analysis
    $imageContext = implode('. ', $aiDescriptions);
    $enhancedDescription = enhanceUserDescriptionWithAI($userDescription, $title, $imageContext);
    
    return $enhancedDescription;
}

/**
 * Enhance user description using AI
 */
function enhanceUserDescriptionWithAI($userDescription, $title, $imageContext = '') {
    // Optimized prompt: direct, token-efficient
    $prompt = "Enhance this OJT journal entry for a weekly report. Make it professional and detailed.\n\n";
    $prompt .= "Entry: {$userDescription}\n";
    
    if (!empty($imageContext)) {
        $prompt .= "Image context: {$imageContext}\n";
    }
    
    $prompt .= "\nWrite 2 paragraphs: (1) what was done, (2) skills learned. Professional tone. No titles, bullets, or 'Here's/In this'.";

    $requestData = [
        'model' => QWEN_TEXT_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 250,
        'temperature' => 0.5
    ];

    $ch = curl_init(QWEN_API_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . QWEN_API_KEY,
            'HTTP-Referer: http://localhost:8000',
            'X-Title: OJT Journal Generator'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            $aiResponse = trim($result['choices'][0]['message']['content']);
            // Clean up the response
            return cleanDescription($aiResponse);
        }
    }

    // Fallback to user description if AI fails
    return $userDescription;
}

/**
 * Get weekly report entries
 */
function getWeeklyReport() {
    $pdo = getDbConnection();

    // Get all OJT entries (no date filter)
    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.user_description, e.entry_date, e.ai_enhanced_description, e.created_at
        FROM ojt_entries e
        ORDER BY e.entry_date DESC, e.created_at DESC
    ");

    $stmt->execute();

    $entries = $stmt->fetchAll();

    // Get images for each entry
    foreach ($entries as &$entry) {
        $stmt = $pdo->prepare("
            SELECT id, image_path, image_order, ai_description
            FROM entry_images
            WHERE entry_id = :entry_id
            ORDER BY image_order ASC
        ");
        $stmt->execute([':entry_id' => $entry['id']]);
        $entry['images'] = $stmt->fetchAll();
    }

    // Get date range from entries
    if (count($entries) > 0) {
        $oldestDate = end($entries)['entry_date'];
        $newestDate = $entries[0]['entry_date'];
        $startDate = date('M j, Y', strtotime($oldestDate));
        $endDate = date('M j, Y', strtotime($newestDate));
    } else {
        $startDate = 'N/A';
        $endDate = 'N/A';
    }

    $weekInfo = [
        'start' => $startDate,
        'end' => $endDate,
        'entries' => $entries
    ];

    jsonResponse(['success' => true, 'week' => $weekInfo]);
}

/**
 * Delete an OJT entry and its images
 */
function deleteEntry() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        jsonResponse(['error' => 'Invalid entry ID'], 400);
    }

    $pdo = getDbConnection();

    // Get all image paths for this entry
    $stmt = $pdo->prepare("SELECT image_path FROM entry_images WHERE entry_id = :id");
    $stmt->execute([':id' => $id]);
    $images = $stmt->fetchAll();

    // Delete image files
    foreach ($images as $image) {
        $filePath = __DIR__ . '/' . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Delete images from database (cascade will handle entry deletion)
    $stmt = $pdo->prepare("DELETE FROM entry_images WHERE entry_id = :id");
    $stmt->execute([':id' => $id]);

    // Delete the entry
    $stmt = $pdo->prepare("DELETE FROM ojt_entries WHERE id = :id");
    $stmt->execute([':id' => $id]);

    jsonResponse(['success' => true]);
}

/**
 * Update entry description
 */
function updateDescription() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $description = $data['description'] ?? '';

    if (!$id) {
        jsonResponse(['error' => 'Invalid entry ID'], 400);
    }

    if (empty($description)) {
        jsonResponse(['error' => 'Description cannot be empty'], 400);
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("UPDATE ojt_entries SET ai_enhanced_description = :description WHERE id = :id");
    $stmt->execute([
        ':description' => $description,
        ':id' => $id
    ]);

    jsonResponse(['success' => true]);
}

/**
 * Update entry description (for AI Customize)
 */
function updateEntryDescription() {
    $data = json_decode(file_get_contents('php://input'), true);
    $entryId = $data['entry_id'] ?? null;
    $description = $data['description'] ?? '';

    if (!$entryId) {
        jsonResponse(['error' => 'Invalid entry ID'], 400);
    }

    if (empty($description)) {
        jsonResponse(['error' => 'Description cannot be empty'], 400);
    }

    // Sanitize
    $description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("UPDATE ojt_entries SET ai_enhanced_description = :description WHERE id = :id");
    $stmt->execute([
        ':description' => $description,
        ':id' => $entryId
    ]);

    jsonResponse(['success' => true]);
}

/**
 * Update full OJT entry (title, description, date)
 */
function updateEntry() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $entryDate = $data['entry_date'] ?? '';

    // Sanitize inputs
    $title = htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');
    $entryDate = trim($entryDate);

    // Validate
    if (!$id) {
        jsonResponse(['error' => 'Invalid entry ID'], 400);
    }

    if (empty($title)) {
        jsonResponse(['error' => 'Title is required'], 400);
    }

    if (strlen($title) < 3 || strlen($title) > 200) {
        jsonResponse(['error' => 'Title must be between 3 and 200 characters'], 400);
    }

    if (!empty($description) && strlen($description) > 5000) {
        jsonResponse(['error' => 'Description must not exceed 5000 characters'], 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)) {
        jsonResponse(['error' => 'Invalid date format'], 400);
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        UPDATE ojt_entries 
        SET title = :title, 
            user_description = :user_desc,
            ai_enhanced_description = :ai_desc,
            entry_date = :entry_date 
        WHERE id = :id
    ");

    $stmt->execute([
        ':title' => $title,
        ':user_desc' => $description,
        ':ai_desc' => $description, // Also update enhanced description
        ':entry_date' => $entryDate,
        ':id' => $id
    ]);

    jsonResponse(['success' => true]);
}

/**
 * Enhance user description with AI
 */
function enhanceDescription() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured'], 500);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $description = $data['description'] ?? '';

    if (empty($description)) {
        jsonResponse(['error' => 'Description is required'], 400);
    }

    // Sanitize input
    $description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');

    // Optimized prompt for enhancing description
    $prompt = "Enhance this OJT journal entry to make it more professional and detailed. Improve grammar, add structure, and make it suitable for a weekly report. Keep the original meaning but make it more comprehensive.\n\n";
    $prompt .= "Original entry: {$description}\n\n";
    $prompt .= "Enhanced version (2-3 paragraphs, professional tone):";

    $requestData = [
        'model' => QWEN_TEXT_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 300,
        'temperature' => 0.6
    ];

    $ch = curl_init(QWEN_API_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . QWEN_API_KEY,
            'HTTP-Referer: http://localhost:8000',
            'X-Title: OJT Journal Generator'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        jsonResponse(['error' => 'API connection failed'], 500);
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $result['message'] ?? $result['error']['message'] ?? 'Unknown API error';
        jsonResponse(['error' => 'API error: ' . $errorMsg], 500);
    }

    if (isset($result['choices'][0]['message']['content'])) {
        $enhancedDescription = trim($result['choices'][0]['message']['content']);
        
        // Clean up the response (remove "Enhanced version:" prefix if present)
        $enhancedDescription = preg_replace('/^Enhanced version:\s*/i', '', $enhancedDescription);
        
        jsonResponse([
            'success' => true,
            'enhanced_description' => $enhancedDescription
        ]);
    } else {
        jsonResponse(['error' => 'Unexpected API response format'], 500);
    }
}

/**
 * Customize entry description with AI based on user's prompt
 */
function customizeEntryWithAI() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured'], 500);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $entryId = $data['entry_id'] ?? null;
    $currentDescription = $data['current_description'] ?? '';
    $customPrompt = $data['custom_prompt'] ?? '';

    if (empty($currentDescription)) {
        jsonResponse(['error' => 'Description is required'], 400);
    }

    if (empty($customPrompt)) {
        jsonResponse(['error' => 'Custom prompt is required'], 400);
    }

    // Build prompt for AI
    $prompt = "Please customize this OJT journal entry based on the following instruction:\n\n";
    $prompt .= "INSTRUCTION: {$customPrompt}\n\n";
    $prompt .= "ORIGINAL ENTRY:\n{$currentDescription}\n\n";
    $prompt .= "Write 2-3 paragraphs in professional tone:";

    $requestData = [
        'model' => QWEN_TEXT_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 400,
        'temperature' => 0.7
    ];

    $ch = curl_init(QWEN_API_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . QWEN_API_KEY,
            'HTTP-Referer: http://localhost:8000',
            'X-Title: OJT Journal Generator'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        jsonResponse(['error' => 'API connection failed'], 500);
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $result['message'] ?? $result['error']['message'] ?? 'Unknown API error';
        jsonResponse(['error' => 'API error: ' . $errorMsg], 500);
    }

    if (isset($result['choices'][0]['message']['content'])) {
        $enhancedDescription = trim($result['choices'][0]['message']['content']);
        $enhancedDescription = preg_replace('/^Enhanced version:\s*/i', '', $enhancedDescription);

        jsonResponse([
            'success' => true,
            'enhanced_description' => $enhancedDescription
        ]);
    } else {
        jsonResponse(['error' => 'Unexpected API response format'], 500);
    }
}

/**
 * Analyze uploaded images and generate title & description for entry
 */
function analyzeImagesForEntry() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured'], 500);
    }

    // Check if images were uploaded
    if (empty($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        jsonResponse(['error' => 'No images uploaded'], 400);
    }

    $uploadCount = count($_FILES['images']['name']);
    $imageAnalyses = [];
    $failedAnalyses = 0;

    // Analyze each image
    for ($i = 0; $i < $uploadCount; $i++) {
        $file = [
            'name' => $_FILES['images']['name'][$i],
            'type' => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'error' => $_FILES['images']['error'][$i],
            'size' => $_FILES['images']['size'][$i]
        ];

        if ($file['error'] === UPLOAD_ERR_OK && file_exists($file['tmp_name'])) {
            // Verify it's actually an image before sending to API
            $mimeType = mime_content_type($file['tmp_name']);
            if (!str_starts_with($mimeType, 'image/')) {
                error_log("Skipping non-image file: " . $file['name'] . " (MIME: {$mimeType})");
                $failedAnalyses++;
                continue;
            }

            try {
                // Analyze image directly from temp file
                $analysis = analyzeImageWithQwen($file['tmp_name']);
                if (!is_array($analysis) && !empty($analysis)) {
                    $imageAnalyses[] = $analysis;
                } else {
                    $failedAnalyses++;
                }
            } catch (Exception $e) {
                error_log("Image analysis error: " . $e->getMessage());
                $failedAnalyses++;
            }
        }
    }

    if (empty($imageAnalyses)) {
        jsonResponse([
            'error' => 'Unable to analyze images. This is likely due to:\n1. API key without vision model access\n2. Insufficient API credits for vision models\n3. All vision models temporarily unavailable\n\nTry: Adding credits to your OpenRouter API key or use the "Enhance with AI" button for text-only enhancement.',
            'failed_count' => $failedAnalyses,
            'debug_hint' => 'Check logs/php_errors.log for model-specific errors'
        ], 500);
    }

    try {
        // Generate title and description from all analyses
        $combinedContext = implode("\n\n", $imageAnalyses);

        // Generate title
        $titlePrompt = "Based on these image analyses, create a concise, professional title (5-10 words) for an OJT journal entry:\n\n{$combinedContext}\n\nTitle:";
        $title = callAIAPI($titlePrompt, 'Generate a concise OJT journal entry title');

        // Generate description
        $descPrompt = "Based on these image analyses from an OJT (On-the-Job Training) journal, write a comprehensive 2-3 paragraph description:\n\n{$combinedContext}\n\nWrite in professional academic tone, describing:\n1. What activities were performed\n2. What tools/technologies were used\n3. What skills were learned\n4. The overall learning experience";
        $description = callAIAPI($descPrompt, 'Write a professional OJT journal description');

        jsonResponse([
            'success' => true,
            'title' => $title,
            'description' => $description,
            'image_count' => count($imageAnalyses),
            'failed_count' => $failedAnalyses
        ]);
    } catch (Exception $e) {
        error_log("Title/Description generation error: " . $e->getMessage());
        jsonResponse(['error' => 'Error generating content: ' . $e->getMessage()], 500);
    }
}

/**
 * Update image description
 */
function updateImageDescription() {
    $data = json_decode(file_get_contents('php://input'), true);
    $imageId = $data['image_id'] ?? null;
    $description = $data['description'] ?? '';

    if (!$imageId) {
        jsonResponse(['error' => 'Invalid image ID'], 400);
    }

    if (empty($description)) {
        jsonResponse(['error' => 'Description cannot be empty'], 400);
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE entry_images SET ai_description = :description WHERE id = :id");
    $stmt->execute([
        ':description' => $description,
        ':id' => $imageId
    ]);

    jsonResponse(['success' => true]);
}

/**
 * Regenerate image analysis
 */
function regenerateImageAnalysis() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured'], 500);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $imageId = $data['image_id'] ?? null;
    $imageUrl = $data['image_url'] ?? '';

    if (!$imageId || !$imageUrl) {
        jsonResponse(['error' => 'Invalid image data'], 400);
    }

    // Get the image path and convert to base64
    $imagePath = __DIR__ . '/../' . $imageUrl;
    
    if (!file_exists($imagePath)) {
        jsonResponse(['error' => 'Image file not found'], 404);
    }

    $imageData = base64_encode(file_get_contents($imagePath));
    $mimeType = mime_content_type($imagePath);
    $base64Image = 'data:' . $mimeType . ';base64,' . $imageData;

    // Enhanced prompt for detailed analysis
    $prompt = "Analyze this OJT (On-the-Job Training) image in detail:\n\n";
    $prompt .= "1. MAIN SUBJECT: Describe the primary activity, person, or object shown\n";
    $prompt .= "2. TOOLS/TECHNOLOGIES: Identify any software, hardware, tools, or equipment visible\n";
    $prompt .= "3. LEARNING OBJECTIVE: Explain what skill or knowledge is being developed\n";
    $prompt .= "4. NOTABLE DETAILS: Mention any important elements (collaboration, screens, code, documents, etc.)\n";
    $prompt .= "5. PROFESSIONAL CONTEXT: Describe how this relates to IT training or workplace skills\n\n";
    $prompt .= "Write 2-3 detailed paragraphs in a professional academic tone suitable for an OJT report. Be specific and descriptive.";

    $requestData = [
        'model' => QWEN_VISION_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => $base64Image]
                    ],
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'max_tokens' => 350,
        'temperature' => 0.6
    ];

    $ch = curl_init(QWEN_API_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . QWEN_API_KEY,
            'HTTP-Referer: http://localhost:8000',
            'X-Title: OJT Journal Generator'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        jsonResponse(['error' => 'API connection failed'], 500);
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $result['message'] ?? $result['error']['message'] ?? 'Unknown API error';
        jsonResponse(['error' => 'API error: ' . $errorMsg], 500);
    }

    if (isset($result['choices'][0]['message']['content'])) {
        $description = trim($result['choices'][0]['message']['content']);
        
        // Update database with new analysis
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE entry_images SET ai_description = :description WHERE id = :id");
        $stmt->execute([
            ':description' => $description,
            ':id' => $imageId
        ]);
        
        jsonResponse([
            'success' => true,
            'description' => $description
        ]);
    } else {
        jsonResponse(['error' => 'Unexpected API response format'], 500);
    }
}

/**
 * Generate AI-powered narrative report for all entries
 */
function generateNarrativeReport() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured'], 500);
    }

    $pdo = getDbConnection();

    // Get all entries (no date filter)
    $stmt = $pdo->prepare("
        SELECT id, title, user_description, entry_date, ai_enhanced_description
        FROM ojt_entries
        ORDER BY entry_date ASC
    ");

    $stmt->execute();

    $entries = $stmt->fetchAll();

    if (empty($entries)) {
        jsonResponse(['error' => 'No entries found'], 404);
    }

    // Build concise context from entries
    $entriesContext = [];
    foreach ($entries as $entry) {
        $date = date('M j', strtotime($entry['entry_date']));
        $desc = $entry['ai_enhanced_description'] ?: $entry['user_description'] ?: 'No description';
        $entriesContext[] = "{$date}: {$entry['title']} - " . substr($desc, 0, 150);
    }

    $contextText = implode("\n", $entriesContext);

    // Optimized prompt: concise, direct
    $prompt = "Write a 2-paragraph OJT weekly narrative report:\n";
    $prompt .= "Paragraph 1: Summarize activities and skills developed\n";
    $prompt .= "Paragraph 2: Challenges overcome and professional growth\n\n";
    $prompt .= "Entries:\n{$contextText}\n\n";
    $prompt .= "Professional tone, 100-150 words.";

    $requestData = [
        'model' => QWEN_TEXT_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 300,
        'temperature' => 0.5
    ];

    $ch = curl_init(QWEN_API_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . QWEN_API_KEY,
            'HTTP-Referer: http://localhost:8000',
            'X-Title: OJT Journal Generator'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        jsonResponse(['error' => 'API connection failed: ' . $curlError], 500);
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $result['message'] ?? $result['error']['message'] ?? 'Unknown API error';
        jsonResponse(['error' => 'API error (' . $httpCode . '): ' . $errorMsg], 500);
    }

    if (isset($result['choices'][0]['message']['content'])) {
        $narrative = trim($result['choices'][0]['message']['content']);

        jsonResponse([
            'success' => true,
            'narrative' => $narrative,
            'entry_count' => count($entries)
        ]);
    } else {
        jsonResponse(['error' => 'Unexpected API response format'], 500);
    }
}

/**
 * Generate full ISPSC-formatted OJT Report with all chapters
 */
function generateISPSCReport() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured'], 500);
    }

    $pdo = getDbConnection();

    // Get all entries
    $stmt = $pdo->prepare("
        SELECT id, title, user_description, entry_date, ai_enhanced_description
        FROM ojt_entries
        ORDER BY entry_date ASC
    ");

    $stmt->execute();
    $entries = $stmt->fetchAll();

    if (empty($entries)) {
        jsonResponse(['error' => 'No entries found. Add some OJT entries first.'], 404);
    }

    // Get images for each entry
    foreach ($entries as &$entry) {
        $stmt = $pdo->prepare("SELECT image_path FROM entry_images WHERE entry_id = :id ORDER BY image_order ASC");
        $stmt->execute([':id' => $entry['id']]);
        $entry['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Build comprehensive context from ALL entries
    $entriesContext = [];
    foreach ($entries as $entry) {
        $date = date('M j, Y', strtotime($entry['entry_date']));
        $desc = $entry['ai_enhanced_description'] ?: $entry['user_description'] ?: 'No description';
        $entriesContext[] = "[$date] {$entry['title']}: " . substr($desc, 0, 200);
    }
    $fullContext = implode("\n\n", $entriesContext);

    // Get date range
    $startDate = date('M j, Y', strtotime($entries[0]['entry_date']));
    $endDate = date('M j, Y', strtotime(end($entries)['entry_date']));
    $totalDays = count($entries);

    // Generate Chapter I: Company Profile (AI-powered based on ALL entries)
    $chapter1Prompt = "From these OJT entries, write Chapter I (3 sections, 2-3 sentences each):\n";
    $chapter1Prompt .= "1. INTRODUCTION - Infer company name, location, nature of business from entries\n";
    $chapter1Prompt .= "2. DURATION - Use the date range from entries\n";
    $chapter1Prompt .= "3. PURPOSE - Infer role and objectives from activities\n\n";
    $chapter1Prompt .= "ALL OJT ENTRIES:\n{$fullContext}\n\n";
    $chapter1Prompt .= "Write Chapter I:";

    $chapter1 = callAIAPI($chapter1Prompt, 'Write OJT Company Profile based on entry context. Formal tone, max 150 words.');

    // Generate Chapter II: Background ONLY (AI-powered) - Activities table will use actual entries
    $chapter2BackgroundPrompt = "From these OJT entries, write BACKGROUND OF ACTION PLAN (2-3 sentences):\n";
    $chapter2BackgroundPrompt .= "Describe the preparation and planning before starting the immersion based on the activities shown.\n\n";
    $chapter2BackgroundPrompt .= "ALL OJT ENTRIES:\n{$fullContext}\n\n";
    $chapter2BackgroundPrompt .= "Write Background section:";

    $chapter2Background = callAIAPI($chapter2BackgroundPrompt, 'Write OJT background section. Formal tone, max 100 words.');

    // Chapter II Activities Table - Use ACTUAL entries with AI-enhanced descriptions
    $activitiesTableRows = [];
    foreach ($entries as $index => $entry) {
        $date = date('M j, Y', strtotime($entry['entry_date']));
        $dayNum = $index + 1;
        $activity = htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8');
        // Use AI-enhanced description (from when entry was created)
        $remarks = htmlspecialchars($entry['ai_enhanced_description'] ?: $entry['user_description'] ?: 'No description', ENT_QUOTES, 'UTF-8');
        $activitiesTableRows[] = "| Day {$dayNum}<br>{$date} | {$activity} | {$remarks} |";
    }
    $activitiesTable = "| Day/Date | Activity | Remarks |\n| --- | --- | --- |\n" . implode("\n", $activitiesTableRows);

    // Combine Chapter II
    $chapter2 = "### BACKGROUND OF THE ACTION PLAN\n\n{$chapter2Background}\n\n### PROGRAM OF ACTIVITIES – PER DAY\n\n{$activitiesTable}";

    // Generate Chapter III: Conclusion & Recommendations (AI-powered based on ALL entries)
    $chapter3Prompt = "From these OJT entries, write Chapter III (2 sections, 2-3 sentences each):\n";
    $chapter3Prompt .= "1. CONCLUSION - Summarize learnings, skills gained, growth based on activities\n";
    $chapter3Prompt .= "2. RECOMMENDATION - Suggestions for: (a) future OJT students, (b) company, (c) ISPSC\n\n";
    $chapter3Prompt .= "ALL OJT ENTRIES:\n{$fullContext}\n\n";
    $chapter3Prompt .= "Write Chapter III:";

    $chapter3 = callAIAPI($chapter3Prompt, 'Write OJT conclusion and recommendations. Formal, concise, max 150 words.');

    jsonResponse([
        'success' => true,
        'report' => [
            'chapter1' => $chapter1,
            'chapter2' => $chapter2,
            'chapter3' => $chapter3,
            'entries' => $entries,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'entry_count' => count($entries),
            'debug_context' => $fullContext // For debugging
        ]
    ]);
}

/**
 * Call AI API for text generation (optimized for token efficiency)
 */
function callAIAPI($prompt, $systemMessage) {
    $requestData = [
        'model' => QWEN_TEXT_MODEL,
        'messages' => [
            ['role' => 'user', 'content' => $systemMessage . "\n\n" . $prompt]
        ],
        'max_tokens' => 500,
        'temperature' => 0.5
    ];

    $ch = curl_init(QWEN_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . QWEN_API_KEY,
            'HTTP-Referer: http://localhost:8000',
            'X-Title: OJT Journal Generator'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
    }

    return 'Content generation unavailable. Please try again.';
}

/**
 * Generate simple download report (non-AI, just entries from database)
 */
function generateDownloadReport() {
    $pdo = getDbConnection();

    // Get all entries
    $stmt = $pdo->prepare("
        SELECT id, title, user_description, entry_date, ai_enhanced_description
        FROM ojt_entries
        ORDER BY entry_date ASC
    ");

    $stmt->execute();
    $entries = $stmt->fetchAll();

    if (empty($entries)) {
        jsonResponse(['error' => 'No entries found. Add some OJT entries first.'], 404);
    }

    // Get images for each entry
    foreach ($entries as &$entry) {
        $stmt = $pdo->prepare("SELECT image_path FROM entry_images WHERE entry_id = :id ORDER BY image_order ASC");
        $stmt->execute([':id' => $entry['id']]);
        $entry['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get date range
    $startDate = date('F j, Y', strtotime($entries[0]['entry_date']));
    $endDate = date('F j, Y', strtotime(end($entries)['entry_date']));
    $totalDays = count($entries);

    // Get student name from first entry title (or use default)
    $studentName = 'JUAN DELA CRUZ';

    jsonResponse([
        'success' => true,
        'report' => [
            'entries' => $entries,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'student_name' => $studentName
        ]
    ]);
}

/**
 * Get human-readable upload error message
 */
function getUploadErrorMessage($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'PHP extension stopped the upload'
    ];

    return $errors[$errorCode] ?? 'Unknown error';
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    // Clear any buffered output
    ob_end_clean();
    
    // Send JSON response
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
