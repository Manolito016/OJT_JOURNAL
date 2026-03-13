<?php
/**
 * ⚠️ DATABASE CLEAR UTILITY - FOR TESTING/DEVELOPMENT ONLY ⚠️
 * 
 * This script will PERMANENTLY DELETE all data from the database.
 * 
 * INSTRUCTIONS:
 * 1. Access this file in your browser: http://localhost:8000/clear-database.php
 * 2. Click "Clear All Data" to delete everything
 * 3. DELETE THIS FILE after use for security!
 * 
 * SECURITY: Change the SECRET_KEY below before using!
 */

// ============= SECURITY CONFIGURATION =============
$SECRET_KEY = 'change-this-to-something-random-12345'; // CHANGE THIS!
// ==================================================

// Check if secret key matches
$action = $_GET['action'] ?? '';
$providedKey = $_GET['key'] ?? '';

if ($action === 'clear' && $providedKey === $SECRET_KEY) {
    // Proceed with clearing database
    require_once __DIR__ . '/src/config.php';
    
    try {
        $pdo = getDbConnection();
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete all images first (in case CASCADE doesn't work)
        $pdo->exec("DELETE FROM entry_images");
        
        // Delete all entries
        $pdo->exec("DELETE FROM ojt_entries");
        
        // Reset auto-increment
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='ojt_entries'");
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='entry_images'");
        
        // Commit transaction
        $pdo->commit();
        
        $success = true;
        $message = '✅ Database cleared successfully! All entries and images have been deleted.';
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $success = false;
        $message = '❌ Error clearing database: ' . $e->getMessage();
    }
} elseif ($action === 'clear' && $providedKey !== $SECRET_KEY) {
    $success = false;
    $message = '❌ Invalid security key! Access denied.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚠️ Clear Database Utility</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #ef4444;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        .warning {
            background: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #991b1b;
        }
        .warning strong { display: block; margin-bottom: 8px; }
        .success {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #065f46;
        }
        .error {
            background: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #991b1b;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .stats {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { color: #64748b; }
        .stat-value { font-weight: 600; color: #1e293b; }
        .instructions {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #1e40af;
        }
        .instructions ol {
            margin-left: 20px;
            margin-top: 10px;
        }
        .instructions li { margin: 5px 0; }
        code {
            background: #1e293b;
            color: #10b981;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Database Clear Utility</h1>
        
        <?php if (isset($success) && $success): ?>
            <div class="success">
                <strong>✅ Success!</strong>
                <?= htmlspecialchars($message) ?>
            </div>
            <div class="instructions">
                <strong>Next Steps:</strong>
                <ol>
                    <li>Return to <a href="index.php">main application</a></li>
                    <li><strong>DELETE THIS FILE</strong> for security: <code>clear-database.php</code></li>
                </ol>
            </div>
            <div style="text-align: center;">
                <a href="index.php" class="btn btn-secondary">← Back to App</a>
            </div>
            
        <?php elseif (isset($success) && !$success): ?>
            <div class="error">
                <strong>❌ Error</strong>
                <?= htmlspecialchars($message) ?>
            </div>
            <div style="text-align: center;">
                <a href="clear-database.php?key=<?= urlencode($SECRET_KEY) ?>" class="btn btn-secondary">Try Again</a>
            </div>
            
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ WARNING: This action is PERMANENT!</strong>
                This will delete <strong>ALL</strong> of the following:
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>All OJT entries</li>
                    <li>All uploaded images</li>
                    <li>All AI-generated descriptions</li>
                </ul>
                <p style="margin-top: 10px;"><strong>This CANNOT be undone!</strong></p>
            </div>
            
            <div class="stats">
                <div class="stat-row">
                    <span class="stat-label">Action:</span>
                    <span class="stat-value">Delete ALL data</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Tables affected:</span>
                    <span class="stat-value">ojt_entries, entry_images</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Auto-increment:</span>
                    <span class="stat-value">Will be reset</span>
                </div>
            </div>
            
            <div class="instructions">
                <strong>After clearing:</strong>
                <ol>
                    <li>Return to main application</li>
                    <li><strong>Delete this file</strong> for security</li>
                </ol>
            </div>
            
            <div style="text-align: center;">
                <a href="clear-database.php?action=clear&key=<?= urlencode($SECRET_KEY) ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('⚠️ Are you ABSOLUTELY SURE? This will PERMANENTLY delete ALL data and CANNOT be undone!')">
                    🗑️ Clear All Data
                </a>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
