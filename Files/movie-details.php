<?php
// ====================== BACKEND ======================
require_once 'session.php';     // checks user is logged in
require_once 'connection.php';  // $conn (mysqli)

 $userPic = "user.png"; // fallback

if (isset($_SESSION['userID'])) {
    $uid = $_SESSION['userID'];

    $query = $conn->prepare("SELECT profilePicture FROM `user` WHERE userID = ?");
    $query->bind_param("i", $uid);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
    
    if ($result && !empty($result['profilePicture'])) {
        $userPic = $result['profilePicture'];
    }
}
$userID = $_SESSION['userID'] ?? null;

$successMsg = "";
$errorMsg   = "";

// -------- 1) Get movie ID from URL --------
$movieID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($movieID <= 0) {
    $movieExists = false;
    $movie       = null;
} else {
    $movieExists = true;
}

// -------- 2) Handle rating form (POST) --------
if ($movieExists && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate_submit'])) {

    $ratingRaw = $_POST['rating'] ?? "";
    $watchType = $_POST['watch']  ?? "";

    // Basic validation
    if (!ctype_digit($ratingRaw)) {
        $errorMsg = "Please select a rating between 1 and 5 stars.";
    } else {
        $score = (int)$ratingRaw;
        if ($score < 1 || $score > 5) {
            $errorMsg = "Please select a rating between 1 and 5 stars.";
        } elseif (!in_array($watchType, ['first', 'rewatch'], true)) {
            $errorMsg = "Please choose a valid watch type.";
        } else {
            // Check if this user already has a rating for this movie
            $stmt = $conn->prepare("
                SELECT ratingID, type
                FROM rating
                WHERE userID = ? AND movieID = ?
                ORDER BY createdAt DESC
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("ii", $userID, $movieID);
                $stmt->execute();
                $stmt->store_result();

                $existingType = null;
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($existingRatingID, $existingType);
                    $stmt->fetch();
                }
                $stmt->close();
            }
            
           

            // If already first-watch and they try first again → block
            if ($existingType === 'first' && $watchType === 'first') {
                $errorMsg = "This movie is already in your shelf as a first watch. "
                          . "You can choose Rewatch or edit your rating from My Shelf.";
            } else {
                // Insert new rating
                $stmt = $conn->prepare("
                    INSERT INTO rating (userID, movieID, score, type, createdAt)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                if ($stmt) {
                    $stmt->bind_param("iiis", $userID, $movieID, $score, $watchType);
                    if ($stmt->execute()) {
                        $stmt->close();

                        // Ensure shelf row for this user
                        $shelfID = null;
                        $stmtShelf = $conn->prepare("SELECT shelfID FROM shelf WHERE userID = ? LIMIT 1");
                        if ($stmtShelf) {
                            $stmtShelf->bind_param("i", $userID);
                            $stmtShelf->execute();
                            $stmtShelf->bind_result($shelfID);
                            if (!$stmtShelf->fetch()) {
                                $shelfID = null;
                            }
                            $stmtShelf->close();
                        }

                        // If no shelf, create one
                        if ($shelfID === null) {
                            $stmtNewShelf = $conn->prepare("INSERT INTO shelf (userID) VALUES (?)");
                            if ($stmtNewShelf) {
                                $stmtNewShelf->bind_param("i", $userID);
                                if ($stmtNewShelf->execute()) {
                                    $shelfID = $conn->insert_id;
                                }
                                $stmtNewShelf->close();
                            }
                        }

                        // Ensure movie is on shelfmovie for this shelf
                        if ($shelfID !== null) {
                            $existsOnShelf = false;
                            $stmtCheck = $conn->prepare("
                                SELECT 1 FROM shelfmovie
                                WHERE shelfID = ? AND movieID = ?
                                LIMIT 1
                            ");
                            if ($stmtCheck) {
                                $stmtCheck->bind_param("ii", $shelfID, $movieID);
                                $stmtCheck->execute();
                                $stmtCheck->store_result();
                                if ($stmtCheck->num_rows > 0) {
                                    $existsOnShelf = true;
                                }
                                $stmtCheck->close();
                            }

                            if (!$existsOnShelf) {
                                $stmtAdd = $conn->prepare("
                                    INSERT INTO shelfmovie (shelfID, movieID, addedAt)
                                    VALUES (?, ?, NOW())
                                ");
                                if ($stmtAdd) {
                                    $stmtAdd->bind_param("ii", $shelfID, $movieID);
                                    $stmtAdd->execute();
                                    $stmtAdd->close();
                                }
                            }
                        }

                        // Final success message
                        $successMsg = ($watchType === 'first')
                            ? "Rating saved and movie added to your shelf."
                            : "Rewatch rating saved successfully.";
                    } else {
                        $errorMsg = "Something went wrong while saving your rating.";
                    }
                } else {
                    $errorMsg = "Unable to prepare rating insert.";
                }
            }
        }
    }
}

// -------- 3) Load movie details --------
$movie = null;
$avgRating = null;
$ratingCount = 0;

if ($movieExists) {
    $stmtMovie = $conn->prepare("
        SELECT title, genre, duration, description, posterURL, releaseDate
        FROM movie
        WHERE movieID = ?
    ");
    if ($stmtMovie) {
        $stmtMovie->bind_param("i", $movieID);
        $stmtMovie->execute();
        $stmtMovie->bind_result($title, $genre, $duration, $description, $posterURL, $releaseDate);
        if ($stmtMovie->fetch()) {
            $movie = [
                'title'       => $title,
                'genre'       => $genre,
                'duration'    => $duration,
                'description' => $description,
                'posterURL'   => $posterURL,
                'releaseDate' => $releaseDate,
            ];
        } else {
            $movieExists = false;
        }
        $stmtMovie->close();
    }

    // Average rating
    if ($movieExists) {
        $stmtAvg = $conn->prepare("
            SELECT AVG(score) AS avgScore, COUNT(*) AS cnt
            FROM rating
            WHERE movieID = ?
        ");
        if ($stmtAvg) {
            $stmtAvg->bind_param("i", $movieID);
            $stmtAvg->execute();
            $stmtAvg->bind_result($avgScore, $cnt);
            if ($stmtAvg->fetch() && $cnt > 0) {
                $avgRating  = (float)$avgScore;
                $ratingCount = (int)$cnt;
            }
            $stmtAvg->close();
        }
    }
}

// Compute star display for average
$avgDisplay = $avgRating !== null ? number_format($avgRating, 1) : '—';
$fullStars  = $avgRating !== null ? (int)round($avgRating) : 0;
if ($fullStars < 0) $fullStars = 0;
if ($fullStars > 5) $fullStars = 5;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Movie Details</title>
  <link rel="icon" href="images/logo.png" type="image/png" />
  <link rel="stylesheet" href="main.css" />

  <style>
    /* ====== your existing styles (trimmed to the important parts) ====== */
    :root{
      --mdp-bg:#0f1115;
      --mdp-card:#161a22;
      --mdp-soft:#1e2430;
      --mdp-text:#e7eaf0;
      --mdp-dim:#aab0bd;
      --mdp-accent:#7c5bff;
      --mdp-star:#f0c419;
      --mdp-border:#2a3040;
      --mdp-success:#1db954;
    }
    *{box-sizing:border-box}
    body{margin:0;background:#0f1115;color:var(--mdp-text);}

    .mdp-wrap{max-width:1100px;margin-inline:auto;padding:24px 20px 80px;}
    .mdp-hero{display:grid;grid-template-columns:320px 1fr;gap:28px;align-items:start;}
    .mdp-poster{
      width:100%;aspect-ratio:2/3;object-fit:cover;border-radius:16px;
      box-shadow:0 10px 30px rgba(0,0,0,.35);background:#222;
      transition:transform .3s, box-shadow .3s;
    }
    .mdp-poster:hover{transform:scale(1.03);box-shadow:0 8px 25px rgba(0,183,255,.25);}
    .mdp-info{
      background:linear-gradient(180deg, rgba(124,92,255,.06), transparent 40%);
      border:1px solid var(--mdp-border);border-radius:16px;
      padding:24px 24px 20px;transition:all .3s;
    }
    .mdp-info:hover{
      transform:translateY(-4px);
      box-shadow:0 8px 20px rgba(0,183,255,.15);
      border-color:rgba(0,183,255,.25);
    }
    .mdp-title{font-size:clamp(24px,3vw,36px);margin:0 0 8px}
    .mdp-desc{
      color:var(--mdp-dim);font-size:16px;line-height:1.75;margin-bottom:18px;
      max-width:62ch;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:8;overflow:hidden;
    }
    .mdp-meta{display:flex;flex-wrap:wrap;gap:10px 18px;margin:12px 0 18px;color:#c7ccda;font-size:14px;}
    .mdp-chip{
      background:var(--mdp-soft);border:1px solid var(--mdp-border);color:#d5d9e4;
      padding:6px 10px;border-radius:999px;display:inline-flex;align-items:center;gap:8px;font-weight:500;
      transition:transform .2s, background .3s, box-shadow .3s;
    }
    .mdp-chip:hover{transform:translateY(-3px);background:rgba(255,255,255,.08);box-shadow:0 0 10px rgba(255,255,255,.15);}

    .mdp-avg{display:flex;align-items:center;gap:12px;margin-top:8px;}
    .mdp-stars{display:inline-flex;gap:4px;font-size:22px;line-height:1;}
    .mdp-stars .full{color:var(--mdp-star);}
    .mdp-stars .empty{color:#4a4f5e;}
    .mdp-avg-number{font-weight:700;letter-spacing:.3px;}

    .mdp-actions{margin-top:18px}
    .mdp-btn{
      appearance:none;border:none;cursor:pointer;
      background:#7c5bff;color:#fff;font-weight:700;
      padding:12px 18px;border-radius:10px;text-decoration:none;
      display:inline-flex;gap:10px;align-items:center;
      box-shadow:0 4px 12px rgba(124,92,255,.3);
      transition:all .3s;
    }
    .mdp-btn:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(124,92,255,.5);}
    .mdp-btn:active{transform:translateY(-1px);}

    .mdp-msg-success{color:#7CFC7C;margin-top:10px;}
    .mdp-msg-error{color:#ff8080;margin-top:10px;}

    /* ===== Modal ===== */
    .mdp-modal{
      position:fixed;inset:0;display:none;place-items:center;padding:24px;
      background:rgba(0,0,0,.6);backdrop-filter:blur(10px);z-index:50;
    }
    .mdp-modal.open{display:grid;}
    .mdp-modal-card{
      width:min(420px,94vw);background:#1e1442;color:#fff;
      padding:25px 30px;border-radius:12px;box-shadow:0 0 15px rgba(0,0,0,.5);
      animation:modalFadeIn .3s ease;
    }
    @keyframes modalFadeIn{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
    .mdp-modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
    .mdp-modal-title{margin:0 0 10px;color:#e6dcff;}

    /* ===== 5-star input (1–5) ===== */
    .mdp-rate-fieldset{border:0;margin:10px 0;padding:0;}
    .mdp-star-input{
      display:flex;flex-direction:row-reverse;justify-content:flex-start;gap:4px;
    }
    .mdp-star-input input{display:none;}
    .mdp-star-input label{
      font-size:32px;color:#4a4f5e;cursor:pointer;transition:.15s;
    }
    .mdp-star-input label:hover,
    .mdp-star-input label:hover ~ label{
      color:var(--mdp-star);transform:scale(1.1);
    }
    .mdp-star-input input:checked ~ label{color:var(--mdp-star);}

    .mdp-radio-row{display:flex;gap:14px;margin:14px 0 6px;}
    .mdp-radio{
      display:flex;align-items:center;gap:8px;
      background:transparent;border:1px solid rgba(255,255,255,.15);
      padding:8px 12px;border-radius:999px;font-size:14px;cursor:pointer;color:#e6dcff;
    }

    .mdp-modal-actions{margin-top:16px;display:flex;gap:10px;justify-content:flex-end;}
    .mdp-cancel{
      background:rgba(255,255,255,.08);color:#e6dcff;
      border:1px solid rgba(255,255,255,.15);
    }
    .mdp-cancel:hover{background:rgba(255,255,255,.15);}

    .mdp-toast{
      position:fixed;left:50%;transform:translateX(-50%);
      bottom:18px;background:var(--mdp-success);color:#0b1a0b;
      padding:12px 16px;border-radius:10px;font-weight:700;
      opacity:0;pointer-events:none;transition:opacity .18s;z-index:60;
      border:1px solid rgba(0,0,0,.2);
    }
    .mdp-toast.show{opacity:1;pointer-events:auto;}

    @media (max-width:950px){
      .mdp-hero{grid-template-columns:1fr;gap:18px;}
      .mdp-poster{width:100%;height:auto;aspect-ratio:2/3;margin:0 auto 18px;}
      .mdp-info{padding:18px;}
    }
  </style>
</head>
<body>

<!-- Header -->
<header>
  <div class="header-content">
    <img src="images/logo.jpg" alt="StoryLense Logo" class="logo" />
    <div class="search-container">
      <input type="text" class="search-bar" placeholder="Search for movies..." />
    </div>
    <a href="home.php" class="home-btn">Home Page</a>
    <a href="user_page.php">
        <img src="images/<?= htmlspecialchars($userPic) ?>" alt="User Profile" class="user-pic">
    </a>
  </div>
</header>

<main class="mdp-wrap">
  <?php if (!$movieExists || $movie === null): ?>
    <p style="color:#ccc;">Movie not found.</p>
  <?php else: ?>
    <?php
      $poster   = $movie['posterURL'] ?: 'images/logo.jpg';
      $yearText = $movie['releaseDate'] ? date('Y', strtotime($movie['releaseDate'])) : '';
      $durationText = $movie['duration'] ? $movie['duration'] . ' min' : '';
    ?>
    <section class="mdp-hero">
      <img class="mdp-poster" src="<?php echo htmlspecialchars($poster); ?>" alt="Movie poster" />

      <div class="mdp-info">
        <h1 class="mdp-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
        <p class="mdp-desc"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>

        <div class="mdp-meta">
          <?php if ($yearText): ?>
            <span class="mdp-chip"><i>📅</i> <?php echo htmlspecialchars($yearText); ?></span>
          <?php endif; ?>
          <?php if ($durationText): ?>
            <span class="mdp-chip"><i>⏰</i> <?php echo htmlspecialchars($durationText); ?></span>
          <?php endif; ?>
          <?php if ($movie['genre']): ?>
            <span class="mdp-chip"><i>🎭</i> <?php echo htmlspecialchars($movie['genre']); ?></span>
          <?php endif; ?>
        </div>

        <!-- Average rating -->
        <div class="mdp-avg" aria-label="Average user rating">
          <div class="mdp-stars" aria-hidden="true">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="<?php echo ($i <= $fullStars) ? 'full' : 'empty'; ?>">★</span>
            <?php endfor; ?>
          </div>
          <div class="mdp-avg-number">
            <?php echo htmlspecialchars($avgDisplay); ?>
            <?php if ($ratingCount > 0): ?>
              <span style="font-size:12px;color:#aab0bd;">(<?php echo $ratingCount; ?>)</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="mdp-actions">
          <button class="mdp-btn" type="button" id="openRateModal">Rate</button>
        </div>

        <?php if ($successMsg): ?>
          <p class="mdp-msg-success"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
          <p class="mdp-msg-error"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
</main>

<!-- Rating Modal -->
<section id="rate-modal" class="mdp-modal" aria-modal="true" role="dialog">
  <div class="mdp-modal-card">
    <div class="mdp-modal-head">
      <h2 class="mdp-modal-title">Rate this movie</h2>
    </div>

    <?php if ($movieExists && $movie !== null): ?>
    <form class="mdp-form" method="post" action="movie-details.php?id=<?php echo urlencode($movieID); ?>">
      <fieldset class="mdp-rate-fieldset">
        <legend style="font-weight:700;margin-bottom:8px;">Your rating</legend>
        <div class="mdp-star-input">
          <!-- 5 stars (1–5) -->
          <input type="radio" id="r5" name="rating" value="5" />
          <label for="r5">★</label>

          <input type="radio" id="r4" name="rating" value="4" />
          <label for="r4">★</label>

          <input type="radio" id="r3" name="rating" value="3" />
          <label for="r3">★</label>

          <input type="radio" id="r2" name="rating" value="2" />
          <label for="r2">★</label>

          <input type="radio" id="r1" name="rating" value="1" />
          <label for="r1">★</label>
        </div>
      </fieldset>

      <div style="margin-top:10px;font-weight:700;">Watch type</div>
      <div class="mdp-radio-row">
        <label class="mdp-radio">
          <input type="radio" name="watch" value="first" checked /> First watch
        </label>
        <label class="mdp-radio">
          <input type="radio" name="watch" value="rewatch" /> Rewatch
        </label>
      </div>

      <div class="mdp-modal-actions">
        <button class="mdp-btn" type="submit" name="rate_submit" value="1">Send</button>
        <button type="button" class="mdp-btn mdp-cancel" id="closeRateModal">Cancel</button>
      </div>
    </form>
    <?php else: ?>
      <p style="color:#ccc;">Movie not found.</p>
      <div class="mdp-modal-actions">
        <button type="button" class="mdp-btn mdp-cancel" id="closeRateModal2">Close</button>
      </div>
    <?php endif; ?>
  </div>
</section>

<div class="mdp-toast" id="rateToast"><?php echo htmlspecialchars($successMsg); ?></div>

<!-- Toast + modal JS -->
<script>
  const rateModal      = document.getElementById('rate-modal');
  const openRateBtn    = document.getElementById('openRateModal');
  const closeRateBtn   = document.getElementById('closeRateModal');
  const closeRateBtn2  = document.getElementById('closeRateModal2');
  const toast          = document.getElementById('rateToast');

  if (openRateBtn) {
    openRateBtn.addEventListener('click', () => {
      rateModal.classList.add('open');
    });
  }
  [closeRateBtn, closeRateBtn2].forEach(btn => {
    if (btn) btn.addEventListener('click', () => rateModal.classList.remove('open'));
  });

  window.addEventListener('click', (e) => {
    if (e.target === rateModal) {
      rateModal.classList.remove('open');
    }
  });

  // Show toast if we have a success message
  <?php if ($successMsg): ?>
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  <?php endif; ?>
</script>

<!-- Search bar → search.php?q=... -->
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


<!-- Footer -->
<footer>
  <div class="footer-content">
    <div class="vision-title">OUR VISION</div>
    <div class="vision-text">
      At StoryLense, we make rating movies simple, engaging, and accessible for everyone
    </div>
    <div class="copyright">&copy; StoryLense. All rights reserved.</div>
    <div class="social-icons">
      <img src="images/x-logo.png" alt="X" class="social-icon" />
      <img src="images/instagram-logo.png" alt="Instagram" class="social-icon" />
    </div>
  </div>
</footer>

</body>
</html>
