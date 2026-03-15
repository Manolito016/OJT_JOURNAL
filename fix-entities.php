<?php
/**
 * Fix HTML Entities in Database
 * 
 * This script decodes HTML entities in existing database entries
 * that were incorrectly encoded with htmlspecialchars().
 * 
 * Run this ONCE to fix existing data, then delete this file.
 * 
 * Usage: php fix-entities.php
 * Or access via browser: http://localhost:8000/fix-entities.php
 */

// Include configuration
require_once __DIR__ . '/src/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix HTML Entities - OJT Journal</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-danger:hover {
            background: #da190b;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix HTML Entities in Database</h1>
        
        <div class="info">
            <strong>What this does:</strong> This script decodes HTML entities (like <code>&amp;quot;</code>, <code>&amp;#039;</code>) 
            in your OJT journal entries back to normal characters (<code>"</code>, <code>'</code>).
        </div>
        
        <div class="warning">
            <strong>⚠️ Important:</strong> 
            <ul>
                <li>This script should only be run ONCE</li>
                <li>Back up your database before running (optional but recommended)</li>
                <li>Delete this file after running successfully</li>
            </ul>
        </div>

        <?php
        try {
            $pdo = getDbConnection();
            
            // Check if action is requested
            $action = $_GET['action'] ?? '';
            
            if ($action === 'fix') {
                // Process the fix
                
                // Get all entries
                $stmt = $pdo->query("SELECT id, title, user_description, ai_enhanced_description FROM ojt_entries");
                $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $fixedCount = 0;
                $updatedEntries = [];
                
                foreach ($entries as $entry) {
                    $needsUpdate = false;
                    $updates = [];
                    
                    // Check and decode title
                    $newTitle = html_entity_decode($entry['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if ($newTitle !== $entry['title']) {
                        $needsUpdate = true;
                        $updates['title'] = ['old' => $entry['title'], 'new' => $newTitle];
                    }
                    
                    // Check and decode user_description
                    if (!empty($entry['user_description'])) {
                        $newDesc = html_entity_decode($entry['user_description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if ($newDesc !== $entry['user_description']) {
                            $needsUpdate = true;
                            $updates['user_description'] = ['old' => $entry['user_description'], 'new' => $newDesc];
                        }
                    }
                    
                    // Check and decode ai_enhanced_description
                    if (!empty($entry['ai_enhanced_description'])) {
                        $newAiDesc = html_entity_decode($entry['ai_enhanced_description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if ($newAiDesc !== $entry['ai_enhanced_description']) {
                            $needsUpdate = true;
                            $updates['ai_enhanced_description'] = ['old' => $entry['ai_enhanced_description'], 'new' => $newAiDesc];
                        }
                    }
                    
                    if ($needsUpdate) {
                        // Update the entry
                        $updateStmt = $pdo->prepare("
                            UPDATE ojt_entries 
                            SET title = :title,
                                user_description = :user_desc,
                                ai_enhanced_description = :ai_desc
                            WHERE id = :id
                        ");
                        
                        $updateStmt->execute([
                            ':title' => $newTitle ?? $entry['title'],
                            ':user_desc' => $updates['user_description']['new'] ?? $entry['user_description'],
                            ':ai_desc' => $updates['ai_enhanced_description']['new'] ?? $entry['ai_enhanced_description'],
                            ':id' => $entry['id']
                        ]);
                        
                        $fixedCount++;
                        $updatedEntries[] = [
                            'id' => $entry['id'],
                            'title' => $entry['title'],
                            'updates' => $updates
                        ];
                    }
                }
                
                // Also fix entry_images table
                $stmt = $pdo->query("SELECT id, ai_description FROM entry_images");
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $fixedImages = 0;
                foreach ($images as $image) {
                    if (!empty($image['ai_description'])) {
                        $newDesc = html_entity_decode($image['ai_description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if ($newDesc !== $image['ai_description']) {
                            $updateStmt = $pdo->prepare("UPDATE entry_images SET ai_description = :desc WHERE id = :id");
                            $updateStmt->execute([
                                ':desc' => $newDesc,
                                ':id' => $image['id']
                            ]);
                            $fixedImages++;
                        }
                    }
                }
                
                echo '<div class="success">';
                echo '<h2>✅ Fix Completed!</h2>';
                echo '<p><strong>Entries fixed:</strong> ' . $fixedCount . '</p>';
                echo '<p><strong>Image descriptions fixed:</strong> ' . $fixedImages . '</p>';
                echo '</div>';
                
                if (!empty($updatedEntries)) {
                    echo '<h3>Updated Entries:</h3>';
                    echo '<table>';
                    echo '<tr><th>Entry ID</th><th>Title</th><th>Fields Updated</th></tr>';
                    foreach ($updatedEntries as $entry) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($entry['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($entry['title']) . '</td>';
                        echo '<td>';
                        foreach ($entry['updates'] as $field => $change) {
                            echo '<div><strong>' . htmlspecialchars($field) . ':</strong><br>';
                            echo '<small>Before: <code>' . htmlspecialchars(substr($change['old'], 0, 50)) . '...</code></small><br>';
                            echo '<small>After: <code>' . htmlspecialchars(substr($change['new'], 0, 50)) . '...</code></small></div>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                
                echo '<div class="warning">';
                echo '<h3>🗑️ Next Step: Delete This File</h3>';
                echo '<p>This script has completed its task. For security, you should delete this file now:</p>';
                echo '<pre>delete fix-entities.php</pre>';
                echo '<p>Or run: <code>del fix-entities.php</code> (Windows) or <code>rm fix-entities.php</code> (Linux/Mac)</p>';
                echo '</div>';
                
                echo '<a href="public/index.php" class="btn">Go to OJT Journal</a>';
                
            } else {
                // Show preview
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM ojt_entries");
                $totalEntries = $stmt->fetch()['count'];
                
                // Check how many entries have HTML entities
                $stmt = $pdo->query("SELECT id, title FROM ojt_entries WHERE title LIKE '%&quot;%' OR title LIKE '%&#039;%'");
                $entriesWithEntities = $stmt->rowCount();
                
                echo '<div class="info">';
                echo '<p><strong>Total entries in database:</strong> ' . $totalEntries . '</p>';
                echo '</div>';
                
                echo '<p>This script will fix HTML encoding issues in your OJT journal entries. After running, your entries will display with proper quotation marks and apostrophes instead of <code>&amp;quot;</code> and <code>&amp;#039;</code>.</p>';
                
                echo '<form method="get">';
                echo '<input type="hidden" name="action" value="fix">';
                echo '<button type="submit" class="btn" onclick="return confirm(\'This will update all entries in the database. Continue?\')">🔧 Fix HTML Entities Now</button>';
                echo '</form>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">';
            echo '<h2>❌ Database Error</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
