<?php
// Start session only if none exists
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session for security
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

// --- CHECK LOGIN STATUS ---
// If no userID in session → user is NOT logged in
if (!isset($_SESSION['userID'])) {
    // Redirect to login page WITH a meaningful error
    header("Location: log.php?error=not_logged_in");
    exit;
}
?>
