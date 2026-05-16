<?php
session_start();


ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php'; // $conn (mysqli)

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

$isLoggedIn = isset($_SESSION['user_id']);

/*
  From your SQL:

  movie(
    movieID, title, genre, duration, description, posterURL, releaseDate
  )
*/

// -------- read filters from GET ----------
$q          = isset($_GET['q']) ? trim($_GET['q']) : '';
$category   = isset($_GET['category']) ? trim($_GET['category']) : '';
//$year       = isset($_GET['year']) ? trim($_GET['year']) : '';
//$minRating  = isset($_GET['rating']) ? trim($_GET['rating']) : '';   // not used yet
$maxMinutes = isset($_GET['duration']) ? trim($_GET['duration']) : '';

// escape strings for safety
$qEsc        = mysqli_real_escape_string($conn, $q);
$categoryEsc = mysqli_real_escape_string($conn, $category);



// -------- build base SQL (filters only, no title condition yet) ----------
$sql = "
  SELECT
    movieID,
    title,
    genre,
    duration,
    description,
    posterURL,
    releaseDate
  FROM movie
  WHERE 1 = 1
";

// category → genre
if ($category !== '') {
    $sql .= " AND genre = '{$categoryEsc}'";
}

/*// year → from releaseDate
if ($year !== '') {
    $yearInt = (int)$year;
    $sql .= " AND YEAR(releaseDate) = {$yearInt}";
}*/

// duration filter (minutes)
if ($maxMinutes !== '') {
    $durInt = (int)$maxMinutes;
    $sql .= " AND duration <= {$durInt}";
}

// -------- ranking logic ----------


if ($q !== '') {
    $exactEsc    = mysqli_real_escape_string($conn, $q);
    $startsEsc   = mysqli_real_escape_string($conn, $q . '%');
    $containsEsc = mysqli_real_escape_string($conn, '%' . $q . '%');

    // subquery to get the "main" movie's genre (best title match)
    $genreSub = "
      (
        SELECT genre
        FROM movie
        WHERE title LIKE '{$containsEsc}'
        ORDER BY
          CASE
            WHEN title = '{$exactEsc}' THEN 0
            WHEN title LIKE '{$startsEsc}' THEN 1
            ELSE 2
          END
        LIMIT 1
      )
    ";

  
       // 🔹 Keep movies whose title matches OR whose genre matches the search text
    $sql .= "
      AND (
        title LIKE '{$containsEsc}'
        OR genre LIKE '{$containsEsc}'   -- allow searching by genre name (Action, Drama, etc.)
        OR genre = {$genreSub}           -- keep your old 'similar genre' logic
      )
      ORDER BY
        CASE
          -- 1) exact title
          WHEN title = '{$exactEsc}' THEN 0
          -- 2) titles that start with the search text
          WHEN title LIKE '{$startsEsc}' THEN 1
          -- 3) titles that contain the search text
          WHEN title LIKE '{$containsEsc}' THEN 2
          -- 4) exact genre name (e.g. 'Action')
          WHEN genre = '{$exactEsc}' THEN 3
          -- 5) genre contains the search text
          WHEN genre LIKE '{$containsEsc}' THEN 4
          -- 6) same-genre movies from the subquery
          WHEN genre = {$genreSub} THEN 5
          ELSE 6
        END,
        releaseDate DESC,
        title ASC
    ";
 
    
    
    
    
    
    
} else {
    // no search text → normal ordering, all movies
    $sql .= " ORDER BY releaseDate DESC, title ASC";
}



$sql .= " LIMIT 100";





// -------- run query ----------
$result = $conn->query($sql);
if (!$result) {
    die('Query error: ' . $conn->error);
}

$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Search Results</title>

<style>
:root{
  --bg:#0f1115;
  --panel:#151820;
  --panel-2:#1a1f2a;
  --text:#e7e9ee;
  --muted:#aeb3c2;
  --brand:#6ea8ff;
  --stroke:#2a3342;
  --radius:1.25rem;
  --shadow:0 0.125rem 0.375rem rgba(0,0,0,.25);
  font-size:16px;
}

body{background:var(--bg); color:var(--text); margin:0}

.containerFatema{
  max-width:87.5rem;
  margin:0 auto;
  padding:1rem 1rem 3rem;
}

.toolbarFatema{
  display:flex;
  align-items:center;
  gap:.75rem;
  background:var(--panel);
  border:0.0625rem solid var(--stroke);
  border-radius:var(--radius);
  padding:.75rem;
  box-shadow:var(--shadow);
  position:sticky;
  top:4.5rem;
  z-index:20;
}

.searchFatema{
  display:flex;
  align-items:center;
  gap:.75rem;
  flex:1;
  background:var(--panel-2);
  padding:.75rem 1rem;
  border-radius:.875rem;
  border:0.0625rem solid var(--stroke);
  min-width:0;
  width:100%;
}
.searchFatema svg{flex:0 0 auto;}
.searchFatema input{
  flex:1;
  min-width:0;
  background:transparent;
  border:none;
  outline:none;
  color:var(--text);
  font-size:1rem;
}
.btnFatema{
  border:0.0625rem solid var(--stroke);
  background:var(--panel-2);
  color:var(--text);
  padding:.6rem .9rem;
  border-radius:.8rem;
  cursor:pointer;
  font-size:.95rem;
  transition:transform .05s, background .2s, border-color .2s;
  white-space:nowrap;
  flex:0 0 auto;
}
.btnFatema:hover{background:#202636; border-color:#364055}
.btnFatema:active{transform:translateY(.0625rem)}
.btnFatema.brand{
  background:linear-gradient(180deg,#7eb6ff,#5f96f2);
  color:#0c0f16;
  border-color:transparent;
}

.layoutFatema{display:grid; grid-template-columns:1fr; gap:1.25rem; margin-top:1rem}


.resultsFatema{
  display:grid;
  gap:1rem;
  /* desktop: 4 cards per row */
  grid-template-columns: repeat(4, minmax(0, 1fr));
  align-content:start;
}

/* laptop */
@media (max-width: 1200px){
  .resultsFatema{
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

/* tablet */
@media (max-width: 900px){
  .resultsFatema{
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* phone */
@media (max-width: 600px){
  .resultsFatema{
    grid-template-columns: 1fr;
  }
}









.cardFatema{
  flex:0 0 17.5rem;
  background:linear-gradient(180deg, rgba(50,80,150,0.4) 0%, rgba(30,40,70,0.6) 100%);
  border-radius:.9375rem;
  overflow:hidden;
  transition:all 0.3s ease;
  cursor:pointer;
  border:0.0625rem solid rgba(255,255,255,0.1);
  position:relative;
}
.cardFatema:hover{
  transform:translateY(-0.625rem) scale(1.03);
  box-shadow:0 0.9375rem 2.5rem rgba(0,0,0,0.5);
  border-color:rgba(255,255,255,0.3);
}
.cardBodyFatema{
  padding:1.25rem;
  background:rgba(20,30,50,0.8);
}
.posterFatema{
  width:100%;
  height:20rem;
  background:linear-gradient(135deg,#2d4a8e 0%,#1e3a5f 100%);
  display:flex;
  align-items:center;
  justify-content:center;
  color:rgba(255,255,255,0.6);
  font-size:1.2rem;
  font-weight:500;
  position:relative;
}
.posterFatema img{
  width:100%;
  height:100%;
  object-fit:cover;
}
.titleFatema{
  font-size:1.1rem;
  font-weight:600;
  color:#fff;
  margin:0 0 .5rem;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.metaFatema{
  display:flex;
  align-items:center;
  gap:.5rem;
  color:rgba(150,170,220,0.9);
  font-size:.9rem;
  margin:0;
}
.metaYearFatema{font-weight:500}
.metaCategoryFatema{color:rgba(150,170,220,0.9)}

.filterRailFatema{display:none}


.drawerFatema{
  position:fixed;
  inset:0;
  /* dark overlay behind the panel */
  background:rgba(0,0,0,0.55);        /* 55% opacity */
  display:none;
  z-index:9999;                        /* <<< higher than header */
  padding:0;                           /* no extra offset */
}

.drawerFatema.open{display:block}

.drawerPanelFatema{
  margin-left:auto;
  width:min(24rem,90vw);
  height:100%;
  /* panel itself slightly transparent */
  background:rgba(21,24,32,0.9);       /* ~90% opaque – change 0.9 to 0.8 if you want */
  backdrop-filter:blur(6px);           /* optional, but looks nice */
  border-left:0.0625rem solid var(--stroke);
  border-radius:1rem 0 0 1rem;
  padding:1.25rem;
  display:flex;
  flex-direction:column;
  gap:1rem;
  box-shadow:var(--shadow);
  overflow:auto;
}










.drawerFatema h3{margin:.25rem 0 .25rem; font-size:1.2rem}
.fieldFatema{display:flex; flex-direction:column; gap:.4rem}
.fieldFatema label{font-size:.9rem; color:var(--muted)}
.fieldFatema input,
.fieldFatema select{
  background:var(--panel-2);
  border:0.0625rem solid var(--stroke);
  border-radius:.6rem;
  padding:.6rem .75rem;
  color:var(--text);
  font-size:.95rem;
  outline:none;
}
.twoColFatema{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:.6rem;
}

.drawerActionsFatema {
    margin-top: 1.5rem;      
    display: flex;
    gap: 1rem;               
    justify-content: flex-end;
}


.srOnlyFatema{
  position:absolute;
  width:.0625rem;
  height:.0625rem;
  padding:0;
  margin:-.0625rem;
  overflow:hidden;
  clip:rect(0,0,0,0);
  white-space:nowrap;
  border:0;
}



/* ===== Netflix-style tighter grid overrides ===== */

/* use more cards per row on big screens */
.resultsFatema{
  display:grid;
  gap:0.75rem;                          /* smaller gaps */
  grid-template-columns: repeat(6, minmax(0, 1fr));  /* 6 per row on desktop */
  align-content:start;
  margin-top:0.75rem;                   /* less space below header */
}

/* laptop */
@media (max-width: 1200px){
  .resultsFatema{
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}

/* tablet */
@media (max-width: 900px){
  .resultsFatema{
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

/* phone */
@media (max-width: 600px){
  .resultsFatema{
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}


.containerFatema{
  max-width:none;
  width:100%;
  padding-left:1rem;
  padding-right:1rem;
  padding-top:0.5rem;
  padding-bottom:2rem;
}


/* Remove link underline + default blue color inside movie cards */
.resultsFatema a {
    text-decoration: none !important;  /* remove underline */
    color: inherit !important;         /* keep same color as parent */
}

.resultsFatema a:hover {
    text-decoration: none !important;  /* no underline on hover */
}




.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;  /* pushes items to opposite sides */
    width: 100%;
}

.user-pic {
    margin-left: auto;   /* pushes icon fully to the right */
    display: block;
}



.header-content {
    display: flex;
    align-items: center;
    /* no justify-content here → layout stays as before */
}



.search-container {
    flex: 1;
    display: flex;
    justify-content: flex-start; /* put the search beside the logo */
    margin-right: 45rem;           /* small space between logo and search bar */
}




/* push the right-side links to the far right */
.home-btn {
    margin-left: auto;           /* moves Dashboard / Home Page to the right */
}

.user-pic {
    margin-left: 0.75rem;        /* small space between button and icon */
    display: block;
}


</style>

<link rel="icon" href="images/logo.png" type="image/png" />
<link rel="stylesheet" href="main.css">
</head>
<body class="indexFatema">

<header>
  <div class="header-content">
    <img src="images/logo.jpg" alt="StoryLense Logo" class="logo">

    <div class="search-container">
      <!-- form so Search submits with GET -->
      <form class="searchFatema headerSearchFatema"
            role="search"
            aria-label="Search"
            method="get"
            action="search.php">
        <svg aria-hidden="true" width="1.25rem" height="1.25rem" viewBox="0 0 24 24" fill="none">
          <path d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z" stroke="#8ea3c7" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <input id="q" name="q" type="search" placeholder="Search…" aria-label="Search input"
               value="<?php echo htmlspecialchars($q); ?>" />
        <button id="headerSearchBtn" class="btnFatema brand" type="submit">Search</button>
        <button id="filterBtn" class="btnFatema" type="button">Filter</button>
      </form>
    </div>

    <?php if($isLoggedIn): ?>
  
        <a href="user_page.php"><img src="images/user.png" alt="User" class="user-pic"></a>
    <?php else: ?>
        <a href="home.php" class="home-btn">Home Page</a>
<a href="user_page.php">
        <img src="images/<?= htmlspecialchars($userPic) ?>" alt="User Profile" class="user-pic">
    </a>    <?php endif; ?>

  </div>
</header>

<div class="containerFatema">
  <div class="layoutFatema">
    <main class="resultsFatema" aria-label="Results">
      <?php if (empty($movies)): ?>
        <p style="color:var(--muted);">No movies found.</p>
      <?php else: ?>
        <?php foreach ($movies as $m): ?>
          <?php
            $poster   = $m['posterURL'] ?: 'images/logo.jpg';
            $yearText = $m['releaseDate'] ? date('Y', strtotime($m['releaseDate'])) : '';
          ?>
          <!-- dynamic card: clicking goes to movie-details.php with the movieID -->
          <a href="movie-details.php?id=<?php echo urlencode($m['movieID']); ?>" class="card-link">
            <article class="cardFatema">
              <div class="posterFatema">
                <img src="<?php echo htmlspecialchars($poster); ?>"
                     alt="<?php echo htmlspecialchars($m['title']); ?>">
              </div>
              <div class="cardBodyFatema">
                <h4 class="titleFatema"><?php echo htmlspecialchars($m['title']); ?></h4>
                <p class="metaFatema">
                  <span class="metaYearFatema"><?php echo htmlspecialchars($yearText); ?></span>
                  •
                  <span class="metaCategoryFatema"><?php echo htmlspecialchars($m['genre']); ?></span>
                </p>
              </div>
            </article>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
</div>

<!-- Filter Drawer -->
<div class="drawerFatema" id="filterDrawer">
  <div class="drawerPanelFatema">
    <div style="display:flex; align-items:center; justify-content:space-between;">
      <h3>Filters</h3>
      <button class="btnFatema" id="closeDrawer" type="button">Close</button>
    </div>

    <!-- Filters submit with GET to same page -->
    <form id="filterForm" method="get" action="search.php">
      <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>" />

      <div class="fieldFatema">
        <label for="category">Category</label>
        <select id="category" name="category">
          <option value="">Any</option>
          <option value="Action"   <?php if($category==='Action')   echo 'selected'; ?>>Action</option>
          <option value="Drama"    <?php if($category==='Drama')    echo 'selected'; ?>>Drama</option>
          <option value="Comedy"   <?php if($category==='Comedy')   echo 'selected'; ?>>Comedy</option>
          <option value="Sci-Fi"   <?php if($category==='Sci-Fi')   echo 'selected'; ?>>Sci-Fi</option>
          <option value="Thriller" <?php if($category==='Thriller') echo 'selected'; ?>>Thriller</option>
          <option value="Romance"  <?php if($category==='Romance')  echo 'selected'; ?>>Romance</option>
          <option value="Fantasy"  <?php if($category==='Fantasy')  echo 'selected'; ?>>Fantasy</option>
          <option value="Crime"    <?php if($category==='Crime')    echo 'selected'; ?>>Crime</option>
          <option value="Musical"  <?php if($category==='Musical')  echo 'selected'; ?>>Musical</option>
        </select>
      </div>

  <!--    <div class="fieldFatema">
        <label for="year">Year</label>
        <input id="year" name="year" type="number" placeholder="e.g., 2024"
               value="<?php echo htmlspecialchars($year); ?>" />
      </div> -->

      <div class="fieldFatema">
          
          <!-- kept for UI [rating], not used in SQL yet --> 
     <!--   <label for="rating">Minimum rating</label>
        
        <input id="rating" name="rating" type="number" min="0" max="10" step="0.1"
               placeholder="e.g., 7.5"
               value="<?php echo htmlspecialchars($minRating); ?>" /> -->
      </div>

      <div class="fieldFatema">
        <label for="duration">Duration</label>
        <select id="duration" name="duration">
          <option value="">Any</option>
          <option value="60"  <?php if($maxMinutes==='60')  echo 'selected'; ?>>&lt; 1 hour</option>
          <option value="90"  <?php if($maxMinutes==='90')  echo 'selected'; ?>>&lt; 1 hour 30 min</option>
          <option value="120" <?php if($maxMinutes==='120') echo 'selected'; ?>>&lt; 2 hours</option>
          <option value="150" <?php if($maxMinutes==='150') echo 'selected'; ?>>&lt; 2 hours 30 min</option>
          <option value="180" <?php if($maxMinutes==='180') echo 'selected'; ?>>&lt; 3 hours</option>
        </select>
      </div>

      <div class="drawerActionsFatema">
        <button class="btnFatema" type="button" id="clearFilters">Clear</button>
        <button class="btnFatema brand" type="submit" id="applyFilters">Apply</button>
      </div>
    </form>
  </div>
</div>

<script>
const filterBtn   = document.getElementById('filterBtn');
const drawer      = document.getElementById('filterDrawer');
const closeDrawer = document.getElementById('closeDrawer');
const clearBtn    = document.getElementById('clearFilters');

if (filterBtn)   filterBtn.addEventListener('click', () => drawer.classList.add('open'));
if (closeDrawer) closeDrawer.addEventListener('click', () => drawer.classList.remove('open'));
if (drawer) {
  drawer.addEventListener('click', (e) => {
    if (e.target === drawer) drawer.classList.remove('open');
  });
}

if (clearBtn) {
  clearBtn.addEventListener('click', () => {
    document.getElementById('category').value = '';
   // document.getElementById('year').value = '';
  //  document.getElementById('rating').value = '';
    document.getElementById('duration').value = '';
  });
}
</script>

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
