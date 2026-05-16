<?php
// ===================== SETTINGS BACKEND (TOP OF FILE) =====================
require_once 'session.php';   // starts session + checks userID + security
require_once 'connection.php';   // $conn (mysqli)

// Logged-in user ID from session
$userID = $_SESSION['userID'] ?? null;

$successMsg = "";
$errorMsg   = "";

// ----------------- 1) Handle form submission (POST) -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {

    // Get submitted values (may be empty)
    $newUsername  = trim($_POST['username'] ?? "");
    $newPassword  = $_POST['password'] ?? "";
    $newProfile   = trim($_POST['profile_picture'] ?? "");

    $didUpdate = false;

    // Update username if not empty
    if ($newUsername !== "") {
        $stmt = $conn->prepare("UPDATE user SET username = ? WHERE userID = ?");
        if ($stmt) {
            $stmt->bind_param("si", $newUsername, $userID);
            if ($stmt->execute()) {
                $didUpdate = true;
            }
            $stmt->close();
        }
    }

    // Update password if not empty (hash before saving)
    if ($newPassword !== "") {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE userID = ?");
        if ($stmt) {
            $stmt->bind_param("si", $hashed, $userID);
            if ($stmt->execute()) {
                $didUpdate = true;
            }
            $stmt->close();
        }
    }

    // Update profile picture if not empty
    if ($newProfile !== "") {
        $stmt = $conn->prepare("UPDATE user SET profilePicture = ? WHERE userID = ?");
        if ($stmt) {
            $stmt->bind_param("si", $newProfile, $userID);
            if ($stmt->execute()) {
                $didUpdate = true;
            }
            $stmt->close();
        }
    }

    if ($didUpdate) {
        $successMsg = "Changes saved successfully!";
    } else {
        $errorMsg = "No changes were made. Please fill at least one field.";
    }
}

// ----------------- 2) Load user info for display -----------------
$username = "";
$email    = "";
$pfp      = "profile.jpg";  // fallback

$stmt = $conn->prepare("SELECT username, email, profilePicture FROM user WHERE userID = ?");
if ($stmt) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($username, $email, $profilePictureFromDB);
    if ($stmt->fetch()) {
        $pfp = $profilePictureFromDB ?: "profile.jpg";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>settings</title>
    <link rel="icon" href="images/logo.png" type="image/png" />
    <link rel="stylesheet" href="user_page.css" />
    <link rel="stylesheet" href="main.css" />
  </head>
  <body>
    <div class="fadeInUp-animation">
      <div class="top-header">
        <div class="profile-section">
          <!-- Show current profile picture from DB -->
          <a href="user_page.php">
            <img src="images/<?php echo htmlspecialchars($pfp); ?>" alt="Profile" />
          </a>
          <h1>Settings</h1>
        </div>
        <button class="hamburger" id="hamburger">☰</button>
      </div>

      <!-- Side menu -->
      <nav class="side-menu" id="sideMenu">
        <ul>
          <li><a href="user_page.php">🏠 My Shelf</a></li>
          <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
      </nav>

      <section class="settings-page">
        <!-- Simple feedback messages -->
        <?php if ($successMsg): ?>
          <p style="color:#7CFC7C; margin-bottom:10px;"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
          <p style="color:#ff8080; margin-bottom:10px;"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>

        <!-- SETTINGS FORM -->
        <form method="post" action="settings.php">
          <h2>Profile</h2>

          <label>Change Profile Picture:</label>
          <div class="pic-options">
            <!-- each option has data-file = image filename stored in DB -->
            <img src="images/rabbit.jpg"  class="option<?php echo ($pfp === 'rabbit.jpg' ? ' selected' : ''); ?>"  data-file="rabbit.jpg" />
            <img src="images/cat.jpg"     class="option<?php echo ($pfp === 'cat.jpg' ? ' selected' : ''); ?>"     data-file="cat.jpg" />
            <img src="images/penguin.jpg" class="option<?php echo ($pfp === 'penguin.jpg' ? ' selected' : ''); ?>" data-file="penguin.jpg" />
            <img src="images/bear.jpg"    class="option<?php echo ($pfp === 'bear.jpg' ? ' selected' : ''); ?>"    data-file="bear.jpg" />
          </div>

          <!-- hidden input where JS will store selected picture filename -->
          <input type="hidden" name="profile_picture" id="profile_picture"
                 value="<?php echo htmlspecialchars($pfp); ?>" />

          <label>Username:</label>
          <input class="username"
                 type="text"
                 name="username"
                 placeholder="Enter new username"
                 value="<?php echo htmlspecialchars($username); ?>" />

        <h2>Account</h2>
        <label>Change Password:</label>
        <input class="password"
               type="password"
               name="password"
               placeholder="New password" />

        <button class="save-btn" type="submit" name="save_settings">Save Changes</button>
        </form>
      </section>
    </div>

    <!-- Menu toggle JS (unchanged) -->
    <script>
      const hamburger = document.getElementById("hamburger");
      const sideMenu = document.getElementById("sideMenu");

      hamburger.addEventListener("click", () => {
        sideMenu.classList.toggle("open");
      });

      window.addEventListener("click", (e) => {
        if (!sideMenu.contains(e.target) && e.target !== hamburger) {
          sideMenu.classList.remove("open");
        }
      });
    </script>

    <!-- Profile picture selection JS -->
    <script>
      const options = document.querySelectorAll(".option");
      const hiddenInput = document.getElementById("profile_picture");

      options.forEach((option) => {
        option.addEventListener("click", () => {
          options.forEach((opt) => opt.classList.remove("selected"));
          option.classList.add("selected");
          // store filename (from data-file) into hidden input
          hiddenInput.value = option.dataset.file;
        });
      });
    </script>
  </body>
</html>
