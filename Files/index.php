<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Get Started</title>
  <style>
    :root{
      --bg:#0f1115;
      --panel:#151820;
      --panel-2:#1a1f2a;
      --text:#e7e9ee;
      --muted:#aeb3c2;
      --brand:#6ea8ff;
      --stroke:#2a3342;
      --radius:1.2rem;
      --ink: var(--text);
      font-size:16px;
    }

    *{ box-sizing:border-box; }
    body{
      margin:0;
      background:var(--bg);
      color:var(--text);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
      line-height:1.55;
    }

    .pageFatema{
      min-height:100dvh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:2rem 1.25rem;
    }

    .shellFatema{
      width:min(76rem, 100%);
      background:var(--panel);
      border:0.0625rem solid var(--stroke);
      border-radius:1.6rem;
      padding:2rem;
      position:relative;
    }

    .heroFatema{
      display:grid;
      grid-template-columns: 1.1fr 1fr;
      gap:2rem;
      align-items:center;
    }

    @media (max-width: 56rem){
      .heroFatema{ grid-template-columns: 1fr; }
    }

    .copyFatema h1{
      font-size:2.2rem;
      line-height:1.2;
      margin:0 0 0.75rem;
    }
    .copyFatema p{
      margin:0 0 1.5rem;
      color:var(--muted);
      font-size:1rem;
      max-width:38rem;
    }

    .actionsFatema{
      display:flex;
      gap:0.75rem;
      flex-wrap:wrap;
    }
    .btnFatema{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:0.5rem;
      padding:0.8rem 1.1rem;
      border-radius:0.9rem;
      border:0.0625rem solid var(--stroke);
      background:var(--panel-2);
      color:var(--text);
      text-decoration:none;
      font-weight:600;
      font-size:1rem;
      transition:transform .05s ease, background .2s ease, border-color .2s ease;
    }
    .btnFatema:hover{ background:#202636; border-color:#364055; }
    .btnFatema:active{ transform:translateY(0.06rem); }

    .btnFatema.primary{
      background: linear-gradient(180deg,#7eb6ff,#5f96f2);
      color:#0c0f16;
      border-color:transparent;
    }

    .visualFatema{
      background:var(--panel-2);
      border:0.0625rem dashed var(--stroke);
      border-radius:var(--radius);
      aspect-ratio:3/4;
      width:100%;
      display:grid;
      place-items:center;
      color:#8ea3c7;
      font-size:1rem;
      position:relative; /* to hold the logo inside */
    }
    .visualFatema img{
      max-width: 60%;
      border-radius:1rem;
    }
  </style>

  <link rel="icon" href="images/logo.png" type="image/png" />
  <link rel="stylesheet" href="main.css">
</head>
<body>
  <main class="pageFatema">
    <section class="shellFatema">
      <div class="heroFatema">
        <div class="copyFatema">
          <h1><span style="color:var(--brand)">Discover and track your favorite movies. </span></h1>
          <p>
            Discover movies, rate them, and track your progress!
          </p>

          <div class="actionsFatema">
            <a class="btnFatema primary" href="sign.php">Sign up</a>
            <a class="btnFatema" href="log.php">Log in</a>
          </div>
        </div>

        <div class="visualFatema" aria-label="Illustration">
          <img src="images/logo.jpg" alt="StoryLense Logo">
        </div>
      </div>
    </section>
  </main>
</body>
</html>
