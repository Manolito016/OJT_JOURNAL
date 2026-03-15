<?php
/**
 * Test Google API Key Connection
 * Run this file directly in browser: http://localhost:8000/test-google-api.php
 */

// Load environment
require_once __DIR__ . '/src/env.php';
loadEnv();

$apiKey = env('GOOGLE_API_KEY', '');
$endpoint = env('GOOGLE_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models');
$model = env('GEMINI_VISION_MODEL', 'gemini-2.0-flash');

echo "<h1>Google API Key Test</h1>";
echo "<pre>";
echo "API Key: " . (empty($apiKey) ? "NOT SET" : substr($apiKey, 0, 15) . "..." . substr($apiKey, -5)) . "\n";
echo "Endpoint: {$endpoint}\n";
echo "Model: {$model}\n";
echo "</pre>";

if (empty($apiKey)) {
    echo "<p style='color: red;'><strong>ERROR:</strong> GOOGLE_API_KEY is not set in .env file!</p>";
    exit;
}

echo "<h2>Test 1: List Available Models</h2>";
$listUrl = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($listUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>";
echo "HTTP Code: {$httpCode}\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $models = $data['models'] ?? [];
    
    echo "✓ API Key is VALID!\n";
    echo "Available models (first 10):\n";
    
    $geminiModels = array_filter($models, function($m) {
        return stripos($m['name'], 'gemini') !== false;
    });
    
    foreach (array_slice($geminiModels, 0, 10) as $model) {
        $name = str_replace('models/', '', $model['name']);
        echo "  - {$name}\n";
    }
    
    // Check if our target model exists
    $targetModelExists = false;
    foreach ($models as $model) {
        $name = str_replace('models/', '', $model['name']);
        if (stripos($name, 'gemini-2.0-flash') !== false) {
            $targetModelExists = true;
            echo "\n✓ Target model 'gemini-2.0-flash' is AVAILABLE!\n";
            break;
        }
    }
    
    if (!$targetModelExists) {
        echo "\n⚠ Target model 'gemini-2.0-flash' NOT FOUND. Available Gemini models:\n";
        foreach ($geminiModels as $model) {
            $name = str_replace('models/', '', $model['name']);
            if (stripos($name, 'flash') !== false) {
                echo "  - {$name}\n";
            }
        }
    }
} else {
    echo "✗ API Key test FAILED!\n";
    $error = json_decode($response, true);
    echo "Error: " . ($error['error']['message'] ?? 'Unknown error') . "\n";
}
echo "</pre>";

echo "<h2>Test 2: Generate Content (Text Only)</h2>";
$textModel = env('GEMINI_TEXT_MODEL', 'gemini-2.0-flash');
$generateUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$textModel}:generateContent?key=" . $apiKey;

$requestData = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Say hello in one word']
            ]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 10,
        'temperature' => 0.1
    ]
];

$ch = curl_init($generateUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>";
echo "HTTP Code: {$httpCode}\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        echo "✓ Text generation SUCCESS!\n";
        echo "Response: " . $data['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "⚠ Unexpected response format\n";
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
} else {
    echo "✗ Text generation FAILED!\n";
    $error = json_decode($response, true);
    echo "Error: " . ($error['error']['message'] ?? 'Unknown error') . "\n";
}
echo "</pre>";

echo "<h2>Test 3: Vision Model Test (Image Analysis)</h2>";
$visionModel = env('GEMINI_VISION_MODEL', 'gemini-2.0-flash');

// Create a simple test image (1x1 pixel)
$testImage = imagecreatetruecolor(100, 100);
$white = imagecolorallocate($testImage, 255, 255, 255);
$black = imagecolorallocate($testImage, 0, 0, 0);
imagefill($testImage, 0, 0, $white);
imagerectangle($testImage, 10, 10, 90, 90, $black);
imagestring($testImage, 5, 20, 40, 'TEST', $black);

ob_start();
imagepng($testImage);
$imageData = ob_get_clean();
imagedestroy($testImage);

$base64Image = base64_encode($imageData);

$visionUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$visionModel}:generateContent?key=" . $apiKey;

$requestData = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Describe this image in one sentence.'],
                ['inline_data' => ['mime_type' => 'image/png', 'data' => $base64Image]]
            ]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 100,
        'temperature' => 0.3
    ]
];

$ch = curl_init($visionUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>";
echo "HTTP Code: {$httpCode}\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        echo "✓ Vision model SUCCESS!\n";
        echo "Response: " . $data['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "⚠ Unexpected response format\n";
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
} else {
    echo "✗ Vision model FAILED!\n";
    $error = json_decode($response, true);
    echo "Error: " . ($error['error']['message'] ?? 'Unknown error') . "\n";
}
echo "</pre>";

echo "<hr>";
echo "<p><a href='http://localhost:8000'>← Back to OJT Journal App</a></p>";
?>
