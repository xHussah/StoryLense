<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - StoryLense</title>
  <link rel="icon" href="images/logo.png" type="image/png" />
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="log.css">
</head>
<body>

  <!-- Header -->
  <header>
    <div class="header-content">
      <img src="images/logo.jpg" alt="StoryLense Logo" class="logo">
    </div>
  </header>

  <!-- Login Section -->
  <main class="login-main">
    <div class="login-box">
      <h2>Login</h2>

      <!-- 🔥 Display error messages -->
      <?php if (isset($_GET['error'])): ?>
          <p style="color:#ff8080; margin-bottom: 15px;">
              <?php
                  if ($_GET['error'] === 'empty') {
                      echo "Please fill in all fields.";
                  }
                  elseif ($_GET['error'] === 'invalid') {
                      echo "Incorrect email or password.";
                  }
                  elseif ($_GET['error'] === 'not_logged_in') {
                      echo "You must be logged in to access that page.";
                  }
              ?>
          </p>
      <?php endif; ?>

      <!-- Login Form -->
      <form class="login-form" action="login_process.php" method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>

        <!-- ✔ Correct submit button -->
        <button type="submit">Login</button>
      </form>

      <div class="signup-link">
        Don't have an account? <a href="sign.php">Sign up</a>
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

</body>
</html>
