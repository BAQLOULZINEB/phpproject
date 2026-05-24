<?php
/**
 * Test script to verify recommender widget functionality
 * Usage: http://localhost/Glow-E.web%20.1.0.1/test_recommender.php?user_id=2&debug_recommender=1
 */

session_start();

// Get user_id from query parameter (for testing)
if (isset($_GET['user_id'])) {
    $_SESSION['user_id'] = intval($_GET['user_id']);
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; margin: 10px 0; border-radius: 4px;'>";
    echo "<strong>✓ Test Mode:</strong> User ID {$_SESSION['user_id']} loaded in session<br>";
    echo "<small>Add <code>&debug_recommender=1</code> to see debug logs in error_log</small>";
    echo "</div>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommender Widget Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body style="background: #f8f9fa; padding: 20px;">

<div class="container" style="margin-top: 20px;">
    <h1>Glow-E Recommender Widget Test</h1>
    <hr>
    
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2>Test Information:</h2>
        <ul>
            <li><strong>Session Status:</strong> <?php echo isset($_SESSION['user_id']) ? "User #" . $_SESSION['user_id'] . " logged in" : "No user logged in"; ?></li>
            <li><strong>API Base:</strong> http://localhost:8000</li>
            <li><strong>Debug Mode:</strong> <?php echo isset($_GET['debug_recommender']) && $_GET['debug_recommender'] === '1' ? "ENABLED" : "DISABLED"; ?></li>
        </ul>
        
        <h3 style="margin-top: 30px; margin-bottom: 20px;">Test Links:</h3>
        <ul>
            <li><a href="?user_id=2&debug_recommender=1" class="btn btn-primary">Test User #2 (with debug)</a></li>
            <li><a href="?user_id=3" class="btn btn-primary">Test User #3</a></li>
            <li><a href="?user_id=11" class="btn btn-primary">Test User #11</a></li>
            <li><a href="?" class="btn btn-secondary">No User (Test Fallback)</a></li>
        </ul>
    </div>

    <hr style="margin: 40px 0;">
    
    <h2 style="margin-bottom: 20px;">Widget Output:</h2>
    
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <?php include 'recommender_widget.php'; ?>
    </div>
    
    <hr style="margin: 40px 0;">
    
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 12px; margin: 10px 0; border-radius: 4px;">
        <strong>📋 Debug Instructions:</strong>
        <ol>
            <li>Check PHP error_log for detailed messages: <code>tail -f <?php echo ini_get('error_log'); ?></code></li>
            <li>Click a test link above with debug mode enabled</li>
            <li>Watch the error_log to see all debug messages</li>
        </ol>
    </div>

</div>

</body>
</html>
