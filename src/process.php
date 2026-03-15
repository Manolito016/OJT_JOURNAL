<?php
/**
 * Process.php - Backend handler for OJT Journal entries
 * Updated: All AI prompts follow master rules — student voice, no buzzwords,
 * 150-word hard limit, first person past tense, no motivational closings.
 */

ob_start();

set_time_limit(300);
ini_set('memory_limit', '256M');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

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

// ============================================================
// MAIN ROUTER
// ============================================================

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'createEntry':            createOJTEntry();            break;
        case 'getWeekly':              getWeeklyReport();           break;
        case 'delete':                 deleteEntry();               break;
        case 'generateNarrative':      generateNarrativeReport();   break;
        case 'updateDescription':      updateDescription();         break;
        case 'updateEntry':            updateEntry();               break;
        case 'updateEntryDescription': updateEntryDescription();    break;
        case 'analyzeImagesForEntry':  analyzeImagesForEntry();     break;
        case 'updateImageDescription': updateImageDescription();    break;
        case 'regenerateImageAnalysis':regenerateImageAnalysis();   break;
        case 'enhanceDescription':     enhanceDescription();        break;
        case 'customizeEntryWithAI':   customizeEntryWithAI();      break;
        case 'generateISPSCReport':    generateISPSCReport();       break;
        case 'generateDownloadReport': generateDownloadReport();    break;
        case 'getReportInfo':          getReportInfo();             break;
        case 'saveReportInfo':         saveReportInfo();            break;
        case 'initReportInfo':         initReportInfo();            break;
        case 'generateWithPrompt':     generateWithPrompt();        break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}

// ============================================================
// HELPERS
// ============================================================

function decodeHtml($text) {
    if (is_null($text)) return null;
    return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Strip Markdown formatting from text
 */
function stripMarkdown($text) {
    $text = preg_replace('/^#+\s+/m', '', $text);
    $text = preg_replace('/\*\*(.*?)\*\*/s', '$1', $text);
    $text = preg_replace('/__(.*?)__/s', '$1', $text);
    $text = preg_replace('/\*(.*?)\*/s', '$1', $text);
    $text = preg_replace('/_(.*?)_/s', '$1', $text);
    $text = preg_replace('/```[\s\S]*?```/', '', $text);
    $text = preg_replace('/`([^`]+)`/', '$1', $text);
    $text = preg_replace('/^\s*>\s+/m', '', $text);
    $text = preg_replace('/^\s*[-*]\s+/m', '', $text);
    $text = preg_replace('/^\s*\d+\.\s+/m', '', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

/**
 * Remove banned AI phrases and corporate buzzwords from text.
 * Called on ALL AI output before saving or returning.
 */
function cleanDescription($text) {
    // Remove common unwanted opening patterns
    $openingPatterns = [
        '/^Title:\s*[^\n]+\n/i',
        '/^Here\'s? (a|my|an?)\s+/i',
        '/^In this\s+/i',
        '/^During this\s+/i',
        '/^This (entry|journal|post|report) (covers|describes|shows|discusses)/i',
        '/^Enhanced version:\s*/i',
        '/^ENHANCED VERSION:\s*/i',
    ];
    foreach ($openingPatterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }

    // Remove bullet/numbered list artifacts
    $text = preg_replace('/^[\-\*•]\s*/m', '', $text);
    $text = preg_replace('/^\d+\.\s*/m', '', $text);

    // === BANNED PHRASES — replace with neutral alternatives or remove ===
    $replacements = [
        // Third-person references — replace with first person
        '/\bparticipants\b/i'                          => 'I',
        '/\bthe trainee\b/i'                           => 'I',
        '/\btrainees\b/i'                              => 'we',
        '/\bthe student\b/i'                           => 'I',

        // Corporate buzzwords — remove or simplify
        '/\bcomprehensive\b/i'                         => '',
        '/\benriching\b/i'                             => '',
        '/\brequisite\b/i'                             => 'needed',
        '/\bcompetencies\b/i'                          => 'skills',
        '/\bleveraging\b/i'                            => 'using',
        '/\bleveraged\b/i'                             => 'used',
        '/\bsynergy\b/i'                               => '',
        '/\bcutting-edge\b/i'                          => '',
        '/\bstate-of-the-art\b/i'                      => '',
        '/\bimmersive\b/i'                             => '',
        '/\breal-world scenarios\b/i'                  => 'actual work',
        '/\bfuture endeavors\b/i'                      => 'future work',
        '/\bvaluable insights\b/i'                     => '',
        '/\bseamlessly integrated\b/i'                 => 'combined',
        '/\beffectively equipped\b/i'                  => 'prepared',
        '/\bhighly beneficial\b/i'                     => 'useful',
        '/\bhighly engaging\b/i'                       => 'engaging',
        '/\bin a real-world setting\b/i'               => 'in practice',
        '/\bin conclusion,?\b/i'                       => '',
        '/\bto summarize,?\b/i'                        => '',
        '/\bit is worth noting( that)?\b/i'            => '',
        '/\bfurthermore,?\b/i'                         => '',
        '/\bmoreover,?\b/i'                            => '',
        '/\badditionally,?\b/i'                        => '',

        // Motivational closing sentences — remove entire sentence
        '/I (am|feel) confident that[^.]+\./i'         => '',
        '/I look forward to[^.]+\./i'                  => '',
        '/I am grateful for[^.]+\./i'                  => '',
        '/This experience has equipped me[^.]+\./i'    => '',
        '/Through this experience,? I developed[^.]+\./i' => '',
        '/The overall learning experience was[^.]+\./i'  => '',
        '/I look forward to applying[^.]+\./i'         => '',
        '/the skills (and knowledge )?I acquired[^.]+\./i' => '',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    // Clean up double spaces and triple newlines left by removals
    $text = preg_replace('/  +/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    // Remove lines that are now too short to be meaningful (< 10 chars)
    $lines = explode("\n", $text);
    $lines = array_filter($lines, fn($l) => strlen(trim($l)) >= 10 || trim($l) === '');
    $text = implode("\n", $lines);

    return trim($text);
}

// ============================================================
// CREATE OJT ENTRY
// ============================================================

function createOJTEntry() {
    if (!isApiKeyConfigured()) {
        jsonResponse(['error' => 'API key not configured. Check your .env file.'], 500);
    }

    $title        = trim($_POST['title'] ?? '');
    $userDesc     = trim($_POST['description'] ?? '');
    $entryDate    = trim($_POST['entry_date'] ?? date('Y-m-d'));

    if (empty($title))              jsonResponse(['error' => 'Title is required'], 400);
    if (strlen($title) < 3)        jsonResponse(['error' => 'Title must be at least 3 characters'], 400);
    if (strlen($title) > 200)      jsonResponse(['error' => 'Title must not exceed 200 characters'], 400);
    if (!empty($userDesc) && strlen($userDesc) > 5000)
                                    jsonResponse(['error' => 'Description must not exceed 5000 characters'], 400);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate))
                                    jsonResponse(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
    if ($entryDate > date('Y-m-d')) jsonResponse(['error' => 'Entry date cannot be in the future'], 400);
    if ($entryDate < date('Y-m-d', strtotime('-1 year')))
                                    jsonResponse(['error' => 'Entry date is too old (max 1 year back)'], 400);

    if (empty($_FILES['images']) || empty($_FILES['images']['name'][0]))
        jsonResponse(['error' => 'At least one image is required'], 400);

    $uploadCount = count($_FILES['images']['name']);
    if ($uploadCount > 25) jsonResponse(['error' => 'Maximum 25 images allowed per entry'], 400);

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        INSERT INTO ojt_entries (title, user_description, entry_date, ai_enhanced_description, created_at)
        VALUES (:title, :user_desc, :entry_date, :ai_desc, :created_at)
    ");
    $stmt->execute([
        ':title'      => $title,
        ':user_desc'  => $userDesc,
        ':entry_date' => $entryDate,
        ':ai_desc'    => $userDesc ?: 'Image analysis pending...',
        ':created_at' => date('Y-m-d H:i:s')
    ]);
    $entryId = $pdo->lastInsertId();

    $imageResults = [];
    for ($i = 0; $i < $uploadCount; $i++) {
        $file = [
            'name'     => $_FILES['images']['name'][$i],
            'type'     => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'error'    => $_FILES['images']['error'][$i],
            'size'     => $_FILES['images']['size'][$i],
        ];
        $imageResults[] = processImage($file, $entryId, $i);
    }

    $aiDescriptions = array_filter(array_column($imageResults, 'ai_description'));
    $enhanced = generateEnhancedDescription($userDesc, $aiDescriptions, $title);

    $pdo->prepare("UPDATE ojt_entries SET ai_enhanced_description = :ai_desc WHERE id = :id")
        ->execute([':ai_desc' => $enhanced, ':id' => $entryId]);

    jsonResponse(['success' => true, 'entry_id' => $entryId, 'images' => $imageResults]);
}

// ============================================================
// PROCESS IMAGE
// ============================================================

function processImage($file, $entryId, $order) {
    if ($file['error'] !== UPLOAD_ERR_OK)
        return ['error' => getUploadErrorMessage($file['error'])];

    if ($file['size'] > MAX_FILE_SIZE)
        return ['error' => 'File too large. Max: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file['type'], ALLOWED_TYPES) && !in_array($ext, $allowedExts))
        return ['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP'];

    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true))
            return ['error' => 'Cannot create upload directory'];
    }
    if (!is_writable(UPLOAD_DIR))
        return ['error' => 'Upload directory not writable'];

    $filename    = 'ojt_' . $entryId . '_' . uniqid() . '_' . time() . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination))
        return ['error' => 'Failed to save image. Please try again.'];

    $imagePath     = 'uploads/' . $filename;
    $aiDescription = analyzeImageWithQwen($destination);

    if (is_array($aiDescription) && isset($aiDescription['error'])) {
        error_log("AI analysis failed for {$filename}: " . $aiDescription['error']);
        $aiDescription = 'Image analysis unavailable';
    }

    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO entry_images (entry_id, image_path, image_order, ai_description, created_at)
            VALUES (:entry_id, :image_path, :order, :ai_desc, :created_at)
        ");
        $stmt->execute([
            ':entry_id'   => $entryId,
            ':image_path' => $imagePath,
            ':order'      => $order,
            ':ai_desc'    => $aiDescription,
            ':created_at' => date('Y-m-d H:i:s')
        ]);
        return ['id' => $pdo->lastInsertId(), 'image_path' => $imagePath, 'ai_description' => $aiDescription];
    } catch (PDOException $e) {
        error_log("DB error saving image: " . $e->getMessage());
        if (file_exists($destination)) unlink($destination);
        return ['error' => 'Failed to save image to database'];
    }
}

// ============================================================
// IMAGE ANALYSIS — MULTI-PROVIDER
// ============================================================

/**
 * Master image analysis dispatcher. Tries Groq first, then Google.
 */
function analyzeImageWithQwen($imagePath) {
    $providers = [];
    if (!empty(GROQ_API_KEY))   $providers[] = 'groq';
    if (!empty(GOOGLE_API_KEY)) $providers[] = 'google';

    if (empty($providers)) {
        error_log("No vision providers configured");
        return ['error' => 'No vision-capable providers configured.'];
    }

    foreach ($providers as $provider) {
        try {
            $result = ($provider === 'groq')
                ? analyzeImageWithGroq($imagePath)
                : analyzeImageWithGoogle($imagePath);

            if ($result && (!is_array($result) || !isset($result['error']))) {
                error_log("Image analysis OK: {$provider}");
                return $result;
            }
            error_log("Image analysis failed ({$provider}): " . (is_array($result) ? ($result['error'] ?? '?') : '?'));
        } catch (Exception $e) {
            error_log("Exception ({$provider}): " . $e->getMessage());
        }
    }
    return ['error' => 'All vision providers failed.'];
}

/**
 * Shared image analysis prompt — used by both Groq and Google.
 * Produces short, natural, student-voice descriptions.
 */
function buildImageAnalysisPrompt(): string {
    return AI_SYSTEM_CONTEXT . "\n\n" .
        "Look at this OJT image. Write exactly 2 short paragraphs:\n" .
        "Paragraph 1: What is happening in the image (name any software, tools, or UI visible).\n" .
        "Paragraph 2: What skill or concept is being learned.\n\n" .
        "RULES:\n" .
        "- Write in first person ('I attended', 'I worked on')\n" .
        "- Under 80 words total\n" .
        "- Name specific tools (Zoom, VSCode, PowerPoint, etc.) if visible\n" .
        "- Do NOT say 'participants', 'the image shows', or 'the trainee'\n" .
        "- No motivational closing sentence\n" .
        "- Plain text only, no markdown\n\n" .
        "Example: 'Attended a Zoom meeting with the BayanAIhan team. " .
        "The facilitator shared slides about AI bootcamp goals.\n\n" .
        "Learned how virtual team meetings are structured and how screen sharing works in Zoom.'";
}

function analyzeImageWithGroq($imagePath) {
    if (empty(GROQ_API_KEY)) return ['error' => 'Groq API key not configured'];

    $imageData   = base64_encode(file_get_contents($imagePath));
    $mimeType    = mime_content_type($imagePath);
    $base64Image = 'data:' . $mimeType . ';base64,' . $imageData;

    $requestData = [
        'model'    => GROQ_VISION_MODEL,
        'messages' => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image_url', 'image_url' => ['url' => $base64Image]],
                ['type' => 'text',      'text'      => buildImageAnalysisPrompt()]
            ]
        ]],
        'max_tokens'  => 120,
        'temperature' => 0.3
    ];

    $ch = curl_init(GROQ_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . GROQ_API_KEY],
        CURLOPT_POSTFIELDS     => json_encode($requestData),
        CURLOPT_TIMEOUT        => 30
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) return ['error' => 'Groq connection failed'];

    $result = json_decode($response, true);
    if ($httpCode !== 200) {
        $msg = $result['error']['message'] ?? 'HTTP ' . $httpCode;
        error_log("Groq vision error: $msg");
        return ['error' => "Groq error: $msg"];
    }

    if (isset($result['choices'][0]['message']['content'])) {
        return cleanDescription(stripMarkdown(trim($result['choices'][0]['message']['content'])));
    }
    return ['error' => 'Unexpected Groq response'];
}

function analyzeImageWithGoogle($imagePath) {
    if (empty(GOOGLE_API_KEY)) return ['error' => 'Google API key not configured'];

    $modelsToTry = array_filter([
        GEMINI_PRIMARY_MODEL,
        GEMINI_FALLBACK_MODEL_1 ?? '',
        GEMINI_FALLBACK_MODEL_2 ?? ''
    ]);

    $imageData   = base64_encode(file_get_contents($imagePath));
    $requestData = [
        'contents'         => [[
            'parts' => [
                ['text'        => buildImageAnalysisPrompt()],
                ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $imageData]]
            ]
        ]],
        'generationConfig' => ['maxOutputTokens' => 120, 'temperature' => 0.3]
    ];

    foreach ($modelsToTry as $model) {
        $url = GOOGLE_API_ENDPOINT . '/' . $model . ':generateContent?key=' . GOOGLE_API_KEY;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($requestData),
            CURLOPT_TIMEOUT        => 30
        ]);
        $response = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) { error_log("Google vision: no response for $model"); continue; }

        $result = json_decode($response, true);
        if ($httpCode !== 200) {
            $msg = $result['error']['message'] ?? 'HTTP ' . $httpCode;
            error_log("Google vision error ($model): $msg");
            continue;
        }

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return cleanDescription(stripMarkdown(trim($result['candidates'][0]['content']['parts'][0]['text'])));
        }
    }
    return ['error' => 'All Google vision models failed'];
}

// DeepSeek vision (fallback, kept for compatibility)
function analyzeImageWithDeepSeek($imagePath) {
    if (empty(DEEPSEEK_API_KEY)) return ['error' => 'DeepSeek not configured'];
    $imageData   = base64_encode(file_get_contents($imagePath));
    $base64Image = 'data:image/jpeg;base64,' . $imageData;
    $requestData = [
        'model'    => DEEPSEEK_VISION_MODEL,
        'messages' => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image_url', 'image_url' => ['url' => $base64Image]],
                ['type' => 'text',      'text'      => buildImageAnalysisPrompt()]
            ]
        ]],
        'max_tokens' => 120, 'temperature' => 0.3
    ];
    $ch = curl_init(DEEPSEEK_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . DEEPSEEK_API_KEY],
        CURLOPT_POSTFIELDS     => json_encode($requestData), CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode !== 200) return ['error' => 'DeepSeek error'];
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content']))
        return cleanDescription(stripMarkdown(trim($result['choices'][0]['message']['content'])));
    return ['error' => 'Unexpected DeepSeek response'];
}

// ============================================================
// DESCRIPTION GENERATION / ENHANCEMENT
// ============================================================

function generateEnhancedDescription($userDescription, $aiDescriptions, $title) {
    if (empty($userDescription) && empty($aiDescriptions)) return 'No description available';
    if (empty($userDescription)) return cleanDescription(implode(' ', $aiDescriptions));
    if (empty($aiDescriptions))  return enhanceUserDescriptionWithAI($userDescription, $title);
    return enhanceUserDescriptionWithAI($userDescription, $title, implode('. ', $aiDescriptions));
}

function enhanceUserDescriptionWithAI($userDescription, $title, $imageContext = '') {
    $prompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $prompt .= "Rewrite this OJT journal entry. Keep the same facts but make it clearer.\n\n";
    $prompt .= "Entry: {$userDescription}\n";
    if (!empty($imageContext)) $prompt .= "Image context: {$imageContext}\n";
    $prompt .= "\nRULES:\n";
    $prompt .= "- First person, past tense ('I attended', 'I learned')\n";
    $prompt .= "- 2 short paragraphs max, under 100 words\n";
    $prompt .= "- Name specific tools (Zoom, VSCode, etc.)\n";
    $prompt .= "- No buzzwords, no motivational closing\n";
    $prompt .= "- Sound like a student, not a report\n";
    $prompt .= "- Do NOT say 'participants', 'trainees', 'comprehensive'\n";

    $result = callAIAPI($prompt, AI_SYSTEM_CONTEXT);
    return !empty($result) ? cleanDescription($result) : $userDescription;
}

// ============================================================
// TEXT AI — MULTI-PROVIDER
// ============================================================

function callAIAPI($prompt, $systemMessage) {
    $providers = getProviderPriority();
    if (empty($providers)) return 'Content generation unavailable. Configure API keys in .env.';

    foreach ($providers as $provider) {
        try {
            $result = match($provider) {
                'groq'        => callGroqAPI($prompt, $systemMessage),
                'google'      => callGoogleAPI($prompt, $systemMessage),
                'deepseek'    => callDeepSeekAPI($prompt, $systemMessage),
                'openrouter'  => callOpenRouterAPI($prompt, $systemMessage),
                default       => null
            };
            if ($result && !str_contains(strtolower($result), 'not configured') && !str_contains(strtolower($result), 'error')) {
                error_log("Text AI OK: {$provider}");
                return cleanDescription(stripMarkdown($result));
            }
        } catch (Exception $e) {
            error_log("Text AI exception ({$provider}): " . $e->getMessage());
        }
    }
    return 'Content generation unavailable. All providers failed.';
}

function callGroqAPI($prompt, $systemMessage) {
    if (empty(GROQ_API_KEY)) return 'Groq not configured';
    $requestData = [
        'model'    => GROQ_TEXT_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => AI_SYSTEM_CONTEXT . ' ' . $systemMessage],
            ['role' => 'user',   'content' => $prompt]
        ],
        'max_tokens'  => 200,
        'temperature' => 0.4
    ];
    $ch = curl_init(GROQ_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . GROQ_API_KEY],
        CURLOPT_POSTFIELDS     => json_encode($requestData), CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode !== 200) throw new Exception('Groq text error: HTTP ' . $httpCode);
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content']))
        return trim($result['choices'][0]['message']['content']);
    throw new Exception('Unexpected Groq text response');
}

function callGoogleAPI($prompt, $systemMessage) {
    if (empty(GOOGLE_API_KEY)) return 'Google not configured';
    $requestData = [
        'contents'         => [['parts' => [['text' => AI_SYSTEM_CONTEXT . ' ' . $systemMessage . "\n\n" . $prompt]]]],
        'generationConfig' => ['maxOutputTokens' => 200, 'temperature' => 0.4]
    ];
    $url = GOOGLE_API_ENDPOINT . '/' . GEMINI_TEXT_MODEL . ':generateContent?key=' . GOOGLE_API_KEY;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($requestData), CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode !== 200) throw new Exception('Google text error: HTTP ' . $httpCode);
    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text']))
        return trim($result['candidates'][0]['content']['parts'][0]['text']);
    throw new Exception('Unexpected Google text response');
}

function callDeepSeekAPI($prompt, $systemMessage) {
    if (empty(DEEPSEEK_API_KEY)) return 'DeepSeek not configured';
    $requestData = [
        'model'    => DEEPSEEK_TEXT_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => AI_SYSTEM_CONTEXT . ' ' . $systemMessage],
            ['role' => 'user',   'content' => $prompt]
        ],
        'max_tokens' => 200, 'temperature' => 0.4
    ];
    $ch = curl_init(DEEPSEEK_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . DEEPSEEK_API_KEY],
        CURLOPT_POSTFIELDS     => json_encode($requestData), CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode !== 200) throw new Exception('DeepSeek text error: HTTP ' . $httpCode);
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content']))
        return trim($result['choices'][0]['message']['content']);
    throw new Exception('Unexpected DeepSeek text response');
}

function callOpenRouterAPI($prompt, $systemMessage) {
    if (empty(QWEN_API_KEY)) return 'OpenRouter not configured';
    $requestData = [
        'model'    => QWEN_TEXT_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => AI_SYSTEM_CONTEXT . ' ' . $systemMessage],
            ['role' => 'user',   'content' => $prompt]
        ],
        'max_tokens' => 200, 'temperature' => 0.4
    ];
    $ch = curl_init(QWEN_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . QWEN_API_KEY,
                                   'HTTP-Referer: http://localhost:8000', 'X-Title: OJT Journal Generator'],
        CURLOPT_POSTFIELDS     => json_encode($requestData), CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode !== 200) throw new Exception('OpenRouter text error: HTTP ' . $httpCode);
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content']))
        return trim($result['choices'][0]['message']['content']);
    throw new Exception('Unexpected OpenRouter text response');
}

// ============================================================
// ENHANCE DESCRIPTION (direct API call, single field)
// ============================================================

function enhanceDescription() {
    if (!isApiKeyConfigured()) jsonResponse(['error' => 'API key not configured'], 500);

    $data        = json_decode(file_get_contents('php://input'), true);
    $description = trim($data['description'] ?? '');

    if (empty($description)) jsonResponse(['error' => 'Description is required'], 400);

    $prompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $prompt .= "Improve this OJT journal entry. Fix grammar. Make it clearer and more specific.\n\n";
    $prompt .= "Entry: {$description}\n\n";
    $prompt .= "RULES:\n";
    $prompt .= "- First person, past tense\n";
    $prompt .= "- 2 short paragraphs, under 120 words\n";
    $prompt .= "- Keep the original meaning and facts\n";
    $prompt .= "- Name specific tools if mentioned\n";
    $prompt .= "- No buzzwords, no motivational closing\n";
    $prompt .= "- Plain text only\n";

    $enhanced = callAIAPI($prompt, AI_SYSTEM_CONTEXT);

    if (empty($enhanced) || str_contains($enhanced, 'unavailable'))
        jsonResponse(['error' => 'AI enhancement failed. Try again.'], 500);

    jsonResponse(['success' => true, 'enhanced_description' => $enhanced]);
}

// ============================================================
// CUSTOMIZE ENTRY WITH AI
// ============================================================

function customizeEntryWithAI() {
    try {
        if (!isApiKeyConfigured()) jsonResponse(['error' => 'API key not configured'], 500);

        $data               = json_decode(file_get_contents('php://input'), true);
        $currentDescription = trim($data['current_description'] ?? '');
        $customPrompt       = trim($data['custom_prompt'] ?? '');
        $enhancementStyle   = $data['enhancement_style'] ?? 'professional';

        if (empty($currentDescription)) jsonResponse(['error' => 'Description is required'], 400);
        if (empty($customPrompt))       jsonResponse(['error' => 'Custom prompt is required'], 400);

        $styleTone = match($enhancementStyle) {
            'detailed'   => 'specific and descriptive — name exact tools, tasks, and challenges',
            'concise'    => 'brief and direct — 2 paragraphs max, under 80 words',
            'academic'   => 'reflective — connect what you did to what you learned in school',
            default      => 'clear and straightforward — student voice, not corporate',
        };

        $prompt  = AI_SYSTEM_CONTEXT . "\n\n";
        $prompt .= "Rewrite this OJT entry based on the user's request.\n\n";
        $prompt .= "USER REQUEST: {$customPrompt}\n\n";
        $prompt .= "ORIGINAL ENTRY:\n{$currentDescription}\n\n";
        $prompt .= "TONE: {$styleTone}\n\n";
        $prompt .= "RULES:\n";
        $prompt .= "- First person, past tense\n";
        $prompt .= "- Under 150 words\n";
        $prompt .= "- Keep original facts — do NOT invent new details\n";
        $prompt .= "- No buzzwords (comprehensive, leveraging, synergy, etc.)\n";
        $prompt .= "- No motivational closing sentence\n";
        $prompt .= "- Do NOT say 'participants' or 'trainees'\n";
        $prompt .= "- Plain text only\n";

        $maxRetries = 3;
        $retryDelay = 2;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $ch = curl_init(QWEN_API_ENDPOINT);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json', 'Authorization: Bearer ' . QWEN_API_KEY,
                    'HTTP-Referer: http://localhost:8000', 'X-Title: OJT Journal Generator'
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model'    => QWEN_TEXT_MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => AI_SYSTEM_CONTEXT],
                        ['role' => 'user',   'content' => $prompt]
                    ],
                    'max_tokens' => 200, 'temperature' => 0.4
                ]),
                CURLOPT_TIMEOUT => 30
            ]);
            $response = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 429) {
                if ($attempt < $maxRetries - 1) { sleep($retryDelay); $retryDelay *= 2; continue; }
                jsonResponse(['error' => 'Rate limited. Try again in a moment.'], 503);
                return;
            }

            if ($httpCode !== 200) {
                $result = json_decode($response, true);
                $msg    = $result['error']['message'] ?? 'HTTP ' . $httpCode;
                jsonResponse(['error' => "API error: $msg"], 500);
                return;
            }

            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $enhanced = cleanDescription(stripMarkdown(trim($result['choices'][0]['message']['content'])));
                jsonResponse(['success' => true, 'enhanced_description' => $enhanced]);
                return;
            }
            jsonResponse(['error' => 'Unexpected API response format'], 500);
            return;
        }
        jsonResponse(['error' => 'Max retries exceeded'], 500);

    } catch (Exception $e) {
        error_log('customizeEntryWithAI: ' . $e->getMessage());
        jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
}

// ============================================================
// ANALYZE IMAGES FOR ENTRY (auto-generate title + description)
// ============================================================

function analyzeImagesForEntry() {
    if (!isApiKeyConfigured()) jsonResponse(['error' => 'API key not configured'], 500);
    if (empty($_FILES['images']) || empty($_FILES['images']['name'][0]))
        jsonResponse(['error' => 'No images uploaded'], 400);

    $imageAnalyses  = [];
    $failedAnalyses = 0;

    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        $file = [
            'name'     => $_FILES['images']['name'][$i],
            'type'     => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'error'    => $_FILES['images']['error'][$i],
            'size'     => $_FILES['images']['size'][$i],
        ];
        if ($file['error'] !== UPLOAD_ERR_OK || !file_exists($file['tmp_name'])) { $failedAnalyses++; continue; }
        $mimeType = mime_content_type($file['tmp_name']);
        if (!str_starts_with($mimeType, 'image/')) { $failedAnalyses++; continue; }

        try {
            $analysis = analyzeImageWithQwen($file['tmp_name']);
            if (!is_array($analysis) && !empty($analysis)) $imageAnalyses[] = $analysis;
            else $failedAnalyses++;
        } catch (Exception $e) { $failedAnalyses++; }
    }

    if (empty($imageAnalyses))
        jsonResponse(['error' => 'Could not analyze images. Check API key and vision model access.', 'failed_count' => $failedAnalyses], 500);

    $combinedContext = implode("\n\n", $imageAnalyses);

    // Generate CONCISE title
    $titlePrompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $titlePrompt .= "Based on these image descriptions, write a SHORT OJT journal entry title (5-8 words). ";
    $titlePrompt .= "Be specific. Use action words ('Attending', 'Learning', 'Working on').\n\n";
    $titlePrompt .= "Image descriptions:\n{$combinedContext}\n\nTitle (5-8 words only):";
    $title = callAIAPI($titlePrompt, 'Write a concise OJT entry title. 5-8 words. No buzzwords.');

    // Generate HONEST, SHORT description
    $descPrompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $descPrompt .= "Write an OJT journal entry (2 short paragraphs, under 120 words) based on these image descriptions.\n\n";
    $descPrompt .= "Image descriptions:\n{$combinedContext}\n\n";
    $descPrompt .= "Paragraph 1: What activity was done and what tools were used.\n";
    $descPrompt .= "Paragraph 2: What was learned.\n\n";
    $descPrompt .= "RULES:\n- First person past tense\n- Simple student language\n- Name specific tools\n";
    $descPrompt .= "- No buzzwords, no motivational closing\n- Plain text only\n";
    $description = callAIAPI($descPrompt, AI_SYSTEM_CONTEXT);

    jsonResponse([
        'success'      => true,
        'title'        => $title,
        'description'  => $description,
        'image_count'  => count($imageAnalyses),
        'failed_count' => $failedAnalyses
    ]);
}

// ============================================================
// REGENERATE IMAGE ANALYSIS
// ============================================================

function regenerateImageAnalysis() {
    if (!isApiKeyConfigured()) jsonResponse(['error' => 'API key not configured'], 500);

    $data     = json_decode(file_get_contents('php://input'), true);
    $imageId  = $data['image_id'] ?? null;
    $imageUrl = $data['image_url'] ?? '';

    if (!$imageId || !$imageUrl) jsonResponse(['error' => 'Invalid image data'], 400);

    $imagePath = __DIR__ . '/../' . $imageUrl;
    if (!file_exists($imagePath)) jsonResponse(['error' => 'Image file not found'], 404);

    $description = analyzeImageWithQwen($imagePath);
    if (is_array($description) && isset($description['error']))
        jsonResponse(['error' => $description['error']], 500);

    $pdo = getDbConnection();
    $pdo->prepare("UPDATE entry_images SET ai_description = :description WHERE id = :id")
        ->execute([':description' => $description, ':id' => $imageId]);

    jsonResponse(['success' => true, 'description' => $description]);
}

// ============================================================
// NARRATIVE REPORT
// ============================================================

function generateNarrativeReport() {
    if (!isApiKeyConfigured()) jsonResponse(['error' => 'API key not configured'], 500);

    $pdo  = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, title, user_description, entry_date, ai_enhanced_description FROM ojt_entries ORDER BY entry_date ASC");
    $stmt->execute();
    $entries = $stmt->fetchAll();

    if (empty($entries)) jsonResponse(['error' => 'No entries found'], 404);

    $lines = [];
    foreach ($entries as $entry) {
        $date  = date('M j', strtotime($entry['entry_date']));
        $desc  = $entry['ai_enhanced_description'] ?: $entry['user_description'] ?: 'No description';
        $lines[] = "{$date}: {$entry['title']} — " . substr(strip_tags($desc), 0, 100);
    }
    $context = implode("\n", $lines);

    $prompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $prompt .= "Write a SHORT weekly OJT narrative (2 paragraphs, under 120 words).\n\n";
    $prompt .= "Paragraph 1: What activities were done this week (be specific — name tools and topics).\n";
    $prompt .= "Paragraph 2: One real challenge faced and one specific thing learned.\n\n";
    $prompt .= "Entries:\n{$context}\n\n";
    $prompt .= "RULES:\n";
    $prompt .= "- First person, past tense\n";
    $prompt .= "- Under 120 words\n";
    $prompt .= "- Sound like a real student\n";
    $prompt .= "- No motivational closing\n";
    $prompt .= "- No buzzwords\n";

    $narrative = callAIAPI($prompt, AI_SYSTEM_CONTEXT);
    if (empty($narrative) || str_contains($narrative, 'unavailable'))
        jsonResponse(['error' => 'Failed to generate narrative'], 500);

    jsonResponse(['success' => true, 'narrative' => $narrative, 'entry_count' => count($entries)]);
}

// ============================================================
// ISPSC REPORT GENERATION
// ============================================================

function generateISPSCReport() {
    if (!isApiKeyConfigured()) jsonResponse(['error' => 'API key not configured'], 500);

    $pdo  = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, title, user_description, entry_date, ai_enhanced_description FROM ojt_entries ORDER BY entry_date ASC");
    $stmt->execute();
    $entries = $stmt->fetchAll();

    if (empty($entries)) jsonResponse(['error' => 'No entries found. Add OJT entries first.'], 404);

    foreach ($entries as &$entry) {
        $entry['title']                   = decodeHtml($entry['title']);
        $entry['user_description']        = decodeHtml($entry['user_description']);
        $entry['ai_enhanced_description'] = decodeHtml($entry['ai_enhanced_description']);
        $imgStmt = $pdo->prepare("SELECT image_path FROM entry_images WHERE entry_id = :id ORDER BY image_order ASC");
        $imgStmt->execute([':id' => $entry['id']]);
        $entry['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $lines = [];
    foreach ($entries as $e) {
        $date    = date('M j, Y', strtotime($e['entry_date']));
        $desc    = $e['ai_enhanced_description'] ?: $e['user_description'] ?: '';
        $lines[] = "[{$date}] {$e['title']}: " . substr(strip_tags($desc), 0, 150);
    }
    $fullContext = implode("\n\n", $lines);
    $startDate   = date('M j, Y', strtotime($entries[0]['entry_date']));
    $endDate     = date('M j, Y', strtotime(end($entries)['entry_date']));
    $totalDays   = count($entries);

    // --- Chapter I ---
    $ch1Prompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $ch1Prompt .= "Write Chapter I: Company Profile. Three SHORT sections (1-2 sentences each).\n\n";
    $ch1Prompt .= "INTRODUCTION: State the company name and what it does (infer from entries).\n";
    $ch1Prompt .= "DURATION AND TIME: State OJT period from {$startDate} to {$endDate} and daily schedule if mentioned.\n";
    $ch1Prompt .= "PURPOSE/ROLE: Describe your role as a student trainee and 2-3 actual tasks done.\n\n";
    $ch1Prompt .= "ALL ENTRIES:\n{$fullContext}\n\n";
    $ch1Prompt .= "RULES:\n- Simple, direct language\n- 1-2 sentences per section\n";
    $ch1Prompt .= "- No buzzwords\n- Only include facts from the entries\n- Student voice\n";
    $chapter1 = callAIAPI($ch1Prompt, 'Write OJT Company Profile. Be brief, honest, specific.');

    // --- Chapter II Background ---
    $ch2Prompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $ch2Prompt .= "Write BACKGROUND OF ACTION PLAN (2-3 sentences only).\n\n";
    $ch2Prompt .= "Include:\n- Simple preparation done before starting\n";
    $ch2Prompt .= "- Realistic timeline (300 hours ≈ 8 weeks, NOT 6 months)\n";
    $ch2Prompt .= "- 2 actual tasks from the entries\n\n";
    $ch2Prompt .= "ALL ENTRIES:\n{$fullContext}\n\n";
    $ch2Prompt .= "RULES:\n- Under 80 words\n- Realistic timeline only\n- No fake deliverables\n- Student voice\n";
    $chapter2Background = callAIAPI($ch2Prompt, 'Write OJT background. Realistic 8-week timeline. Student voice.');

    // Activities table from actual entries
    $tableRows = [];
    foreach ($entries as $i => $entry) {
        $date    = date('M j, Y', strtotime($entry['entry_date']));
        $remarks = strip_tags($entry['ai_enhanced_description'] ?: $entry['user_description'] ?: 'No description');
        $remarks = substr($remarks, 0, 300);
        $tableRows[] = "| Day " . ($i + 1) . "<br>{$date} | {$entry['title']} | {$remarks} |";
    }
    $activitiesTable = "| Day/Date | Activity | Remarks |\n| --- | --- | --- |\n" . implode("\n", $tableRows);
    $chapter2 = "### BACKGROUND OF THE ACTION PLAN\n\n{$chapter2Background}\n\n### PROGRAM OF ACTIVITIES – PER DAY\n\n{$activitiesTable}";

    // --- Chapter III ---
    $ch3Prompt  = AI_SYSTEM_CONTEXT . "\n\n";
    $ch3Prompt .= "Write Chapter III with two clearly labeled sections.\n\n";
    $ch3Prompt .= "CONCLUSION (2-3 sentences):\n";
    $ch3Prompt .= "- 1-2 specific things actually learned\n";
    $ch3Prompt .= "- One real challenge faced\n";
    $ch3Prompt .= "- How OJT differed from classroom learning\n\n";
    $ch3Prompt .= "RECOMMENDATION (3 sentences — one per audience):\n";
    $ch3Prompt .= "- One practical tip for future OJT students\n";
    $ch3Prompt .= "- One constructive suggestion for the company\n";
    $ch3Prompt .= "- One respectful suggestion for ISPSC\n\n";
    $ch3Prompt .= "ALL ENTRIES:\n{$fullContext}\n\n";
    $ch3Prompt .= "RULES:\n- Be specific (name actual tools and activities)\n";
    $ch3Prompt .= "- No motivational language\n- No 'Congratulations'\n";
    $ch3Prompt .= "- Student voice\n- Under 150 words total\n";
    $chapter3Raw = callAIAPI($ch3Prompt, 'Write OJT conclusion and recommendations. Honest, specific, student voice.');

    // Parse sections
    $conclusion     = '';
    $recommendation = '';
    if (preg_match('/CONCLUSION\s*[\n:]\s*(.*?)(?=RECOMMENDATION|$)/si', $chapter3Raw, $m))
        $conclusion = trim($m[1]);
    if (preg_match('/RECOMMENDATION\s*[\n:]\s*(.*?)(?=CONCLUSION|$)/si', $chapter3Raw, $m))
        $recommendation = trim($m[1]);
    if (empty($conclusion) && empty($recommendation))
        $conclusion = $chapter3Raw;

    jsonResponse([
        'success' => true,
        'report'  => [
            'chapter1'       => $chapter1,
            'chapter2'       => $chapter2,
            'chapter3'       => $chapter3Raw,
            'conclusion'     => $conclusion,
            'recommendation' => $recommendation,
            'entries'        => $entries,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'total_days'     => $totalDays,
            'entry_count'    => count($entries)
        ]
    ]);
}

// ============================================================
// GENERATE WITH PROMPT (used by script.js autoGenerateField)
// ============================================================

function generateWithPrompt() {
    if (!isApiKeyConfigured()) jsonResponse(['error' => 'API key not configured'], 500);

    $data          = json_decode(file_get_contents('php://input'), true);
    $prompt        = trim($data['prompt'] ?? '');
    $systemMessage = trim($data['system_message'] ?? '');

    if (empty($prompt)) jsonResponse(['error' => 'Prompt is required'], 400);

    $result = callAIAPI($prompt, $systemMessage ?: AI_SYSTEM_CONTEXT);

    if (empty($result) || str_contains($result, 'unavailable'))
        jsonResponse(['error' => 'Failed to generate content. All AI providers failed.'], 500);

    jsonResponse(['success' => true, 'narrative' => $result]);
}

// ============================================================
// WEEKLY REPORT
// ============================================================

function getWeeklyReport() {
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.user_description, e.entry_date, e.ai_enhanced_description, e.created_at
        FROM ojt_entries e
        ORDER BY e.entry_date DESC, e.created_at DESC
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();

    foreach ($entries as &$entry) {
        $imgStmt = $pdo->prepare("SELECT id, image_path, image_order, ai_description FROM entry_images WHERE entry_id = :entry_id ORDER BY image_order ASC");
        $imgStmt->execute([':entry_id' => $entry['id']]);
        $entry['images'] = $imgStmt->fetchAll();
    }

    if (count($entries) > 0) {
        $oldestDate    = end($entries)['entry_date'];
        $newestDate    = $entries[0]['entry_date'];
        $startDate     = date('l, F j, Y', strtotime($oldestDate));
        $endDate       = date('l, F j, Y', strtotime($newestDate));
        $dateRangeLabel = $startDate . ' - ' . $endDate;
        $totalDays     = count($entries);
    } else {
        $startDate = $endDate = 'N/A';
        $dateRangeLabel = 'No entries yet';
        $totalDays = 0;
    }

    foreach ($entries as &$entry) {
        $entry['title']                   = decodeHtml($entry['title']);
        $entry['user_description']        = decodeHtml($entry['user_description']);
        $entry['ai_enhanced_description'] = decodeHtml($entry['ai_enhanced_description']);
        if (isset($entry['images'])) {
            foreach ($entry['images'] as &$image)
                $image['ai_description'] = decodeHtml($image['ai_description']);
        }
    }

    jsonResponse(['success' => true, 'week' => [
        'start'      => $startDate,
        'end'        => $endDate,
        'entries'    => $entries,
        'total_days' => $totalDays,
        'date_range' => $dateRangeLabel
    ]]);
}

// ============================================================
// CRUD OPERATIONS
// ============================================================

function deleteEntry() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = $data['id'] ?? null;
    if (!$id) jsonResponse(['error' => 'Invalid entry ID'], 400);

    $pdo  = getDbConnection();
    $stmt = $pdo->prepare("SELECT image_path FROM entry_images WHERE entry_id = :id");
    $stmt->execute([':id' => $id]);
    foreach ($stmt->fetchAll() as $image) {
        $filePath = __DIR__ . '/' . $image['image_path'];
        if (file_exists($filePath)) unlink($filePath);
    }
    $pdo->prepare("DELETE FROM entry_images WHERE entry_id = :id")->execute([':id' => $id]);
    $pdo->prepare("DELETE FROM ojt_entries WHERE id = :id")->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

function updateDescription() {
    $data        = json_decode(file_get_contents('php://input'), true);
    $id          = $data['id'] ?? null;
    $description = trim($data['description'] ?? '');
    if (!$id)            jsonResponse(['error' => 'Invalid entry ID'], 400);
    if (empty($description)) jsonResponse(['error' => 'Description cannot be empty'], 400);
    $pdo = getDbConnection();
    $pdo->prepare("UPDATE ojt_entries SET ai_enhanced_description = :description WHERE id = :id")
        ->execute([':description' => $description, ':id' => $id]);
    jsonResponse(['success' => true]);
}

function updateEntryDescription() {
    $data        = json_decode(file_get_contents('php://input'), true);
    $entryId     = $data['entry_id'] ?? null;
    $description = trim($data['description'] ?? '');
    if (!$entryId)       jsonResponse(['error' => 'Invalid entry ID'], 400);
    if (empty($description)) jsonResponse(['error' => 'Description cannot be empty'], 400);
    $pdo = getDbConnection();
    $pdo->prepare("UPDATE ojt_entries SET ai_enhanced_description = :description WHERE id = :id")
        ->execute([':description' => $description, ':id' => $entryId]);
    jsonResponse(['success' => true]);
}

function updateEntry() {
    $data        = json_decode(file_get_contents('php://input'), true);
    $id          = $data['id'] ?? null;
    $title       = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $entryDate   = trim($data['entry_date'] ?? '');

    if (!$id)    jsonResponse(['error' => 'Invalid entry ID'], 400);
    if (empty($title) || strlen($title) < 3 || strlen($title) > 200)
                 jsonResponse(['error' => 'Title must be 3-200 characters'], 400);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate))
                 jsonResponse(['error' => 'Invalid date format'], 400);

    $pdo = getDbConnection();
    $pdo->prepare("
        UPDATE ojt_entries SET title = :title, user_description = :user_desc,
        ai_enhanced_description = :ai_desc, entry_date = :entry_date WHERE id = :id
    ")->execute([
        ':title'      => $title,
        ':user_desc'  => $description,
        ':ai_desc'    => $description,
        ':entry_date' => $entryDate,
        ':id'         => $id
    ]);
    jsonResponse(['success' => true]);
}

function updateImageDescription() {
    $data        = json_decode(file_get_contents('php://input'), true);
    $imageId     = $data['image_id'] ?? null;
    $description = trim($data['description'] ?? '');
    if (!$imageId)       jsonResponse(['error' => 'Invalid image ID'], 400);
    if (empty($description)) jsonResponse(['error' => 'Description cannot be empty'], 400);
    $pdo = getDbConnection();
    $pdo->prepare("UPDATE entry_images SET ai_description = :description WHERE id = :id")
        ->execute([':description' => $description, ':id' => $imageId]);
    jsonResponse(['success' => true]);
}

// ============================================================
// DOWNLOAD REPORT
// ============================================================

function generateDownloadReport() {
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, title, user_description, entry_date, ai_enhanced_description FROM ojt_entries ORDER BY entry_date ASC");
    $stmt->execute();
    $entries = $stmt->fetchAll();

    if (empty($entries)) jsonResponse(['error' => 'No entries found. Add OJT entries first.'], 404);

    foreach ($entries as &$entry) {
        $imgStmt = $pdo->prepare("SELECT image_path FROM entry_images WHERE entry_id = :id ORDER BY image_order ASC");
        $imgStmt->execute([':id' => $entry['id']]);
        $entry['images']                  = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
        $entry['title']                   = decodeHtml($entry['title']);
        $entry['user_description']        = decodeHtml($entry['user_description']);
        $entry['ai_enhanced_description'] = decodeHtml($entry['ai_enhanced_description']);
    }

    $reportInfoStmt = $pdo->prepare("SELECT * FROM ojt_report_info WHERE id = 1");
    $reportInfoStmt->execute();
    $reportInfo = $reportInfoStmt->fetch();

    $decodedReportInfo = [];
    if ($reportInfo) {
        foreach ($reportInfo as $key => $value) {
            if (!is_numeric($key)) $decodedReportInfo[$key] = decodeHtml($value);
        }
    }

    jsonResponse(['success' => true, 'report' => [
        'entries'      => $entries,
        'start_date'   => date('F j, Y', strtotime($entries[0]['entry_date'])),
        'end_date'     => date('F j, Y', strtotime(end($entries)['entry_date'])),
        'total_days'   => count($entries),
        'student_name' => $decodedReportInfo['student_name'] ?? 'JUAN DELA CRUZ',
        'report_info'  => $decodedReportInfo
    ]]);
}

// ============================================================
// REPORT INFO CRUD
// ============================================================

function getReportInfo() {
    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM ojt_report_info WHERE id = 1");
        $stmt->execute();
        $reportInfo = $stmt->fetch();

        if (!$reportInfo) {
            jsonResponse(['success' => true, 'data' => [
                'student_name'               => 'JUAN DELA CRUZ',
                'student_course'             => 'Bachelor of Science in Information Technology',
                'school_year'                => 'S.Y. 2025 - 2026',
                'company_name'               => '',
                'company_location'           => '',
                'company_nature_of_business' => '',
                'company_background'         => '',
                'ojt_start_date'             => '',
                'ojt_end_date'               => '',
                'daily_hours'                => '',
                'purpose_role'               => '',
                'background_action_plan'     => '',
                'conclusion'                 => '',
                'recommendation_students'    => '',
                'recommendation_company'     => '',
                'recommendation_school'      => '',
                'acknowledgment'             => ''
            ]]);
            return;
        }

        $decoded = [];
        foreach ($reportInfo as $key => $value) {
            if (!is_numeric($key)) $decoded[$key] = decodeHtml($value);
        }
        jsonResponse(['success' => true, 'data' => $decoded]);

    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function saveReportInfo() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        $fields = [
            'student_name', 'student_course', 'school_year',
            'company_name', 'company_location', 'company_nature_of_business', 'company_background',
            'ojt_start_date', 'ojt_end_date', 'daily_hours', 'purpose_role',
            'background_action_plan', 'conclusion',
            'recommendation_students', 'recommendation_company', 'recommendation_school',
            'acknowledgment'
        ];

        $values = [];
        foreach ($fields as $field) $values[$field] = trim($data[$field] ?? '');
        $values['student_name']   = $values['student_name']   ?: 'JUAN DELA CRUZ';
        $values['student_course'] = $values['student_course'] ?: 'Bachelor of Science in Information Technology';
        $values['school_year']    = $values['school_year']    ?: 'S.Y. 2025 - 2026';

        $pdo   = getDbConnection();
        $check = $pdo->query("SELECT COUNT(*) as count FROM ojt_report_info WHERE id = 1")->fetch();

        $setClauses = implode(', ', array_map(fn($f) => "{$f} = :{$f}", $fields));
        $sql = $check['count'] > 0
            ? "UPDATE ojt_report_info SET {$setClauses}, updated_at = CURRENT_TIMESTAMP WHERE id = 1"
            : "INSERT INTO ojt_report_info (id, " . implode(', ', $fields) . ") VALUES (1, " . implode(', ', array_map(fn($f) => ":{$f}", $fields)) . ")";

        $params = [];
        foreach ($values as $k => $v) $params[':' . $k] = $v;
        $pdo->prepare($sql)->execute($params);

        jsonResponse(['success' => true, 'message' => 'Report information saved successfully']);

    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
    }
}

function initReportInfo() {
    try {
        $pdo    = getDbConnection();
        $result = $pdo->query("SELECT COUNT(*) as count FROM ojt_report_info WHERE id = 1")->fetch();
        if ($result['count'] == 0) {
            $pdo->prepare("INSERT INTO ojt_report_info (id, student_name, student_course, school_year) VALUES (1, :n, :c, :y)")
                ->execute([':n' => 'JUAN DELA CRUZ', ':c' => 'Bachelor of Science in Information Technology', ':y' => 'S.Y. 2025 - 2026']);
        }
        jsonResponse(['success' => true, 'message' => 'Report info initialized']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// ============================================================
// UTILITY
// ============================================================

function getUploadErrorMessage($code) {
    return [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload stopped by PHP extension'
    ][$code] ?? 'Unknown upload error';
}

function jsonResponse($data, $statusCode = 200) {
    ob_end_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>