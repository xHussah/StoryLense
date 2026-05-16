<?php
// ===================== USER PAGE BACKEND (TOP OF FILE) =====================
require_once 'session.php';   // ensures user is logged in
require_once 'connection.php';   // DB connection

$userID = $_SESSION['userID'] ?? null;

$successMsg = "";
$errorMsg   = "";

// --------- Get this user's shelfID (needed for delete movie) ----------
$shelfID = null;
$stmtShelf = $conn->prepare("SELECT shelfID FROM shelf WHERE userID = ?");
if ($stmtShelf) {
    $stmtShelf->bind_param("i", $userID);
    $stmtShelf->execute();
    $stmtShelf->bind_result($shelfID);
    $stmtShelf->fetch();
    $stmtShelf->close();
}

// ----------------- Handle POST actions first -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // DELETE ONE RATING
    if ($action === 'delete_rating') {
        $ratingID = (int)($_POST['rating_id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM rating WHERE ratingID = ? AND userID = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $ratingID, $userID);
            if ($stmt->execute()) {
                $successMsg = "Rating deleted successfully.";
            } else {
                $errorMsg = "Failed to delete rating.";
            }
            $stmt->close();
        }

    // DELETE A MOVIE (all this user's ratings for it + from shelf)
    } elseif ($action === 'delete_movie') {
        $movieID = (int)($_POST['movie_id'] ?? 0);

        // Delete ratings for this user + movie
        $stmt = $conn->prepare("DELETE FROM rating WHERE userID = ? AND movieID = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $userID, $movieID);
            $stmt->execute();
            $stmt->close();
        }

        // Delete from shelfmovie
        if ($shelfID !== null) {
            $stmt = $conn->prepare("DELETE FROM shelfmovie WHERE shelfID = ? AND movieID = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $shelfID, $movieID);
                $stmt->execute();
                $stmt->close();
            }
        }

        $successMsg = "Movie and all your ratings for it were removed.";

    // EDIT RATING (change the score only)
    } elseif ($action === 'edit_rating') {
        $ratingID = (int)($_POST['rating_id'] ?? 0);
        $newScore = $_POST['new_score'] ?? "";

        if ($newScore === "" || !is_numeric($newScore)) {
            $errorMsg = "Please enter a valid rating value.";
        } else {
            $newScore = (float)$newScore;
            if ($newScore < 0 || $newScore > 5) {
                $errorMsg = "Rating must be between 0 and 5.";
            } else {
                $stmt = $conn->prepare("UPDATE rating SET score = ? WHERE ratingID = ? AND userID = ?");
                if ($stmt) {
                    // score is stored as tinyint, but we accept decimals; can round or multiply by 2 if needed
                    $rounded = $newScore; // keep as-is for simplicity
                    $stmt->bind_param("dii", $rounded, $ratingID, $userID);
                    if ($stmt->execute()) {
                        $successMsg = "Rating updated successfully.";
                    } else {
                        $errorMsg = "Failed to update rating.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// ----------------- Load user info (name + profile picture) -----------------
$username = "User";
$pfp      = "profile.jpg";

$stmtUser = $conn->prepare("SELECT username, profilePicture FROM user WHERE userID = ?");
if ($stmtUser) {
    $stmtUser->bind_param("i", $userID);
    $stmtUser->execute();
    $stmtUser->bind_result($usernameFromDB, $pfpFromDB);
    if ($stmtUser->fetch()) {
        $username = $usernameFromDB ?: "User";
        $pfp      = $pfpFromDB ?: "profile.jpg";
    }
    $stmtUser->close();
}

// ----------------- Load all ratings + movie info for this user -----------------
$ratings = [];

$stmt = $conn->prepare("
    SELECT r.ratingID, r.score, r.type, r.createdAt,
           m.movieID, m.title, m.posterURL
    FROM rating r
    JOIN movie m ON r.movieID = m.movieID
    WHERE r.userID = ?
    ORDER BY r.createdAt DESC
");
if ($stmt) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $ratings = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Page</title>
    <link rel="icon" href="images/logo.png" type="image/png" />
    <link rel="stylesheet" href="user_page.css" />
    <link rel="stylesheet" href="main.css">
  </head>

  <body>
    <header>
      <div class="header-content">
        <img src="images/logo.jpg" alt="StoryLense Logo" class="logo">
        <div class="search-container">
          <input type="text" class="search-bar" placeholder="Search for movies...">
        </div>
        <a href="home.php" class="home-btn">Home Page</a>
      </div>
    </header>

    <!-- HEADER with fade animation -->
    <div class="fadeInUp-animation">
      <div class="top-header">
        <div class="profile-section">
          <a href="user_page.php">
            <img src="images/<?php echo htmlspecialchars($pfp); ?>" alt="Profile" />
          </a>
          <h1>welcome <?php echo htmlspecialchars($username); ?></h1>
        </div>
        <button class="hamburger" id="hamburger">☰</button>
      </div>

      <!-- Hamburger Side Menu -->
      <nav class="side-menu" id="sideMenu">
        <ul>
          <li><a href="settings.php">⚙️Profile</a></li>
          <li><a href="logout.php">🚪Logout</a></li>
        </ul>
      </nav>
    </div>

    <!-- MOVIE SHELF -->
    <section class="shelf-section">
      <h2>🎞️ My Shelf</h2>

      <!-- Feedback messages -->
      <?php if ($successMsg): ?>
        <p style="color:#7CFC7C; text-align:center;"><?php echo htmlspecialchars($successMsg); ?></p>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
        <p style="color:#ff8080; text-align:center;"><?php echo htmlspecialchars($errorMsg); ?></p>
      <?php endif; ?>

      <div class="container">
        <?php if (empty($ratings)): ?>
          <p style="color:#ccc; text-align:center; width:100%;">You have not rated any movies yet.</p>
        <?php else: ?>
          <?php foreach ($ratings as $r): ?>
            <?php
              $poster = $r['posterURL'] ?: 'images/logo.jpg';
              $dateText = date('M d, Y', strtotime($r['createdAt']));
              $watchLabel = ($r['type'] === 'first') ? 'First watch' : 'Rewatch';
            ?>
            <div class="card">
              <img src="<?php echo htmlspecialchars($poster); ?>" alt="" />
              <div class="card-content">
                <h3><?php echo htmlspecialchars($r['title']); ?></h3>
                <h3>⭐ <?php echo htmlspecialchars($r['score']); ?> / 5</h3>
                <p>Rated: <?php echo htmlspecialchars($dateText); ?></p>
                <p><?php echo htmlspecialchars($watchLabel); ?></p>
              </div>

              <!-- ⋮ MENU -->
              <div class="card-actions">
                <button class="menu-btn">⋮</button>
                <div class="menu-popup">
                  <!-- Edit: uses JS to open modal; form is in the modal -->
                  <button class="edit-option"
                          type="button"
                          data-rating-id="<?php echo (int)$r['ratingID']; ?>"
                          data-current-score="<?php echo htmlspecialchars($r['score']); ?>">
                    Edit rating
                  </button>

                  <!-- Delete rating -->
                  <form method="post" style="margin:0;">
                    <input type="hidden" name="rating_id" value="<?php echo (int)$r['ratingID']; ?>">
                    <button class="delete-option"
                            type="submit"
                            name="action"
                            value="delete_rating">
                      Delete rating
                    </button>
                  </form>

                  <!-- Delete movie from shelf + all ratings -->
                  <form method="post" style="margin:0;">
                    <input type="hidden" name="movie_id" value="<?php echo (int)$r['movieID']; ?>">
                    <button type="submit"
                            name="action"
                            value="delete_movie">
                      Delete movie
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="shelf-bar"></div>
    </section>

    <!-- EDIT MODAL (now a real form that updates DB) -->
    <div class="edit-modal" id="editModal">
      <div class="modal-content">
        <h3>Edit Rating</h3>
        <form method="post">
          <input type="hidden" name="action" value="edit_rating">
          <input type="hidden" name="rating_id" id="edit_rating_id">

          <label for="newRating">New Rating (⭐ 0 - 5):</label>
          <input type="number" id="newRating" name="new_score" min="0" max="5" step="1" required />

          <div class="modal-buttons">
            <button id="saveEdit" type="submit">Save</button>
            <button id="cancelEdit" type="button">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <footer>
      <div class="footer-content">
        <div class="vision-title">OUR VISION</div>
        <div class="vision-text">
          At StoryLense, we make rating movies simple, engaging, and accessible for everyone
        </div>
        <div class="copyright">&copy; StoryLense. All rights reserved.</div>
        <div class="social-icons">
          <img src="images/x-logo.png" alt="X" class="social-icon">
          <img src="images/instagram-logo.png" alt="Instagram" class="social-icon">
        </div>
      </div>
    </footer>

    <!-- STAGGERED CARD ANIMATION -->
    <script>
      window.addEventListener("load", () => {
        const cards = document.querySelectorAll(".card");
        cards.forEach((card, index) => {
          setTimeout(() => card.classList.add("show"), 200 * index);
        });
      });
    </script>

    <!-- MENU + MODAL BEHAVIOUR -->
    <script>
      let openMenu = null;
      const menuBtns = document.querySelectorAll(".menu-btn");
      const modal = document.getElementById("editModal");
      const cancelEditBtn = document.getElementById("cancelEdit");
      const editRatingInput = document.getElementById("newRating");
      const editRatingIdInput = document.getElementById("edit_rating_id");

      // Open/close kebab menu
      menuBtns.forEach((btn) => {
        const popup = btn.nextElementSibling;
        btn.addEventListener("click", (e) => {
          e.stopPropagation();
          if (openMenu && openMenu !== popup) openMenu.style.display = "none";
          popup.style.display = popup.style.display === "flex" ? "none" : "flex";
          popup.style.flexDirection = "column";
          openMenu = popup.style.display === "flex" ? popup : null;
        });
      });

      // Close popup when clicking outside
      document.addEventListener("click", () => {
        if (openMenu) {
          openMenu.style.display = "none";
          openMenu = null;
        }
      });

      // Open edit modal and fill values
      document.querySelectorAll(".edit-option").forEach((btn) => {
        btn.addEventListener("click", (e) => {
          e.stopPropagation();
          const ratingId = btn.dataset.ratingId;
          const currentScore = btn.dataset.currentScore;

          editRatingIdInput.value = ratingId;
          editRatingInput.value = currentScore;

          modal.style.display = "flex";
          if (openMenu) {
            openMenu.style.display = "none";
            openMenu = null;
          }
        });
      });

      // Cancel edit: just hide modal
      cancelEditBtn.addEventListener("click", () => {
        modal.style.display = "none";
      });

      // Close modal when clicking outside box
      window.addEventListener("click", (e) => {
        if (e.target === modal) {
          modal.style.display = "none";
        }
      });

      // Hamburger toggle
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

    <!-- Search bar → search.php -->
   <script>
const searchBar = document.querySelector('.search-bar');

if (searchBar) {
    searchBar.addEventListener('keypress', function (event) {
        if (event.key === 'Enter' && searchBar.value.trim() !== '') {
            const query = encodeURIComponent(searchBar.value.trim());
            window.location.href = `search.php?q=${query}`;
        }
    });
}
</script>

  </body>
</html>
