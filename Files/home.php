<?php
session_start();
require_once 'connection.php';

// Fetch logged-in user's profile picture
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


// Fetch categories

// Most Popular = newest release dates
$popular = $conn->query("SELECT * FROM movie ORDER BY releaseDate DESC LIMIT 10");

// Action
$action = $conn->query("SELECT * FROM movie WHERE genre = 'Action'");

// Drama
$drama = $conn->query("SELECT * FROM movie WHERE genre = 'Drama'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    
    <link rel="icon" href="images/logo.png" type="image/png" />
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="main.css">
</head>
<body>

    <!-- Header -->
    <header>
        <div class="header-content">
            <img src="images/logo.jpg" alt="StoryLense Logo" class="logo">
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Search for movies...">
            </div>
            <a href="home.php" class="home-btn">Home Page</a>
            
            <a href="user_page.php">
                <img src="images/<?= htmlspecialchars($userPic) ?>" alt="User Profile" class="user-pic">
            </a>

        </div>
    </header>

    <!-- Main Content -->
    <main>

        <!-- Hero Section -->
        <section class="hero-section">
            <h1>Welcome to StoryLense 👋</h1>
            <p>Your gateway to discovering, reviewing, and rating amazing movies.</p>
            <p>Let your movie journey begin!</p>
        </section>
        
        <!-- ========================= -->
        <!--      MOST POPULAR        -->
        <!-- ========================= -->
        <div class="category-section">
            <div class="category-header">
                <h2 class="category-title">Most Popular</h2>
            </div>
            <div class="scroll-container">
                <button class="scroll-btn scroll-btn-left" onclick="scrollMovies('trending', -1)">‹</button>

                <div class="movies-wrapper" id="trending">

                    <?php while ($row = $popular->fetch_assoc()): ?>
                        <a href="movie-details.php?id=<?= $row['movieID'] ?>" class="movie-card">
                            <div class="movie-poster">
                                <img src="<?= $row['posterURL'] ?>" alt="<?= $row['title'] ?> Poster">
                            </div>
                            <div class="movie-info">
                                <div class="movie-title"><?= $row['title'] ?></div>
                                <div class="movie-meta">
                                    <span class="movie-year"><?= date("Y", strtotime($row['releaseDate'])) ?></span>
                                    <span>•</span>
                                    <span class="movie-category"><?= $row['genre'] ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>

                </div>

                <button class="scroll-btn scroll-btn-right" onclick="scrollMovies('trending', 1)">›</button>
            </div>
        </div>

        <!-- ========================= -->
        <!--        ACTION MOVIES      -->
        <!-- ========================= -->
        <div class="category-section">
            <div class="category-header">
                <h2 class="category-title">Action Movies</h2>
            </div>

            <div class="scroll-container">
                <button class="scroll-btn scroll-btn-left" onclick="scrollMovies('action', -1)">‹</button>

                <div class="movies-wrapper" id="action">
                
                    <?php while ($row = $action->fetch_assoc()): ?>
                        <a href="movie-details.php?id=<?= $row['movieID'] ?>" class="movie-card">
                            <div class="movie-poster">
                                <img src="<?= $row['posterURL'] ?>" alt="<?= $row['title'] ?> Poster">
                            </div>
                            <div class="movie-info">
                                <div class="movie-title"><?= $row['title'] ?></div>
                                <div class="movie-meta">
                                    <span class="movie-year"><?= date("Y", strtotime($row['releaseDate'])) ?></span>
                                    <span>•</span>
                                    <span class="movie-category"><?= $row['genre'] ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>

                </div>

                <button class="scroll-btn scroll-btn-right" onclick="scrollMovies('action', 1)">›</button>
            </div>
        </div>

        <!-- ========================= -->
        <!--          DRAMA            -->
        <!-- ========================= -->
        <div class="category-section">
            <div class="category-header">
                <h2 class="category-title">Drama</h2>
            </div>

            <div class="scroll-container">
                <button class="scroll-btn scroll-btn-left" onclick="scrollMovies('drama', -1)">‹</button>

                <div class="movies-wrapper" id="drama">
                
                    <?php while ($row = $drama->fetch_assoc()): ?>
                        <a href="movie-details.php?id=<?= $row['movieID'] ?>" class="movie-card">
                            <div class="movie-poster">
                                <img src="<?= $row['posterURL'] ?>" alt="<?= $row['title'] ?> Poster">
                            </div>
                            <div class="movie-info">
                                <div class="movie-title"><?= $row['title'] ?></div>
                                <div class="movie-meta">
                                    <span class="movie-year"><?= date("Y", strtotime($row['releaseDate'])) ?></span>
                                    <span>•</span>
                                    <span class="movie-category"><?= $row['genre'] ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>

                </div>

                <button class="scroll-btn scroll-btn-right" onclick="scrollMovies('drama', 1)">›</button>
            </div>
        </div>

    </main>

    <!-- Footer -->
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

    <script>
        function scrollMovies(categoryId, direction) {
            const container = document.getElementById(categoryId);
            container.scrollBy({
                left: direction * 300,
                behavior: 'smooth'
            });
        }

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
