<?php
// index.php
// Homepage - publicly accessible, shows Ramadan recipes and map
// If user is already logged in we show their name and a dashboard link

session_start();
require_once 'dp.php';

// Check if user is already logged in to show personalised nav
$isLoggedIn  = isset($_SESSION['user_id']);
$isAdmin     = $isLoggedIn && $_SESSION['user_type'] === 'admin';
$isUser      = $isLoggedIn && $_SESSION['user_type'] === 'user';
$loggedName  = '';

if ($isLoggedIn) {
    $stmtName = $pdo->prepare("SELECT firstName FROM user WHERE id = ?");
    $stmtName->execute([$_SESSION['user_id']]);
    $row = $stmtName->fetch();
    if ($row) {
        $loggedName = $row['firstName'];
    }
}

// Fetch latest 3 recipes for the reels section
$stmtReels = $pdo->query("
    SELECT r.id, r.name, r.photoFileName, r.description, rc.categoryName,
           COUNT(l.recipeID) AS totalLikes
    FROM recipe r
    JOIN recipecategory rc ON r.categoryID = rc.id
    LEFT JOIN likes l ON r.id = l.recipeID
    GROUP BY r.id, r.name, r.photoFileName, r.description, rc.categoryName
    ORDER BY r.id DESC
    LIMIT 3
");
$reels = $stmtReels->fetchAll();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>سُفره | الصفحة الرئيسية</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفــــرة</span>
      </div>

      <nav class="nav">
        <?php if ($isLoggedIn): ?>
          <!-- Show dashboard link if already logged in -->
          <?php if ($isAdmin): ?>
            <a class="nav-chip" href="admin.php">لوحة الإدارة</a>
          <?php else: ?>
            <a class="nav-chip" href="user.php">صفحتي</a>
          <?php endif; ?>
          <a class="nav-chip" href="signout.php">تسجيل الخروج</a>
        <?php else: ?>
          <a class="nav-chip" href="login.php">تسجيل الدخول</a>
          <a class="nav-chip" href="signup.php">إنشاء حساب</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <div class="container">

    <!-- Welcome section -->
    <section class="welcome-section">
      <div class="welcome-inner">
        <div class="welcome-logo">
          <img src="images/logo.png" alt="شعار سُفرة" class="welcome-logo-img">
        </div>

        <?php if ($isLoggedIn && $loggedName !== ''): ?>
          <h1 class="welcome-title">🌙 مرحبًا <?= htmlspecialchars($loggedName) ?>!</h1>
        <?php else: ?>
          <h1 class="welcome-title">🌙 مرحبًا بكم في سُفره</h1>
        <?php endif; ?>

        <p class="welcome-subtitle">
          منصة وصفات رمضانية تُسهّل الوصول إلى وصفات الإفطار والسحور بطريقة منظمة وبسيطة.
          اكتشف وصفات جديدة، شارك تجاربك، واستمتع بأجواء رمضان معنا.
        </p>

        <div class="welcome-actions">
          <?php if ($isAdmin): ?>
            <a class="welcome-btn primary" href="admin.php">لوحة الإدارة</a>
          <?php elseif ($isUser): ?>
            <a class="welcome-btn primary" href="user.php">صفحتي</a>
          <?php else: ?>
            <a class="welcome-btn primary" href="login.php">ابدأ الآن</a>
            <a class="welcome-btn secondary" href="#reels">استكشف الوصفات</a>
          <?php endif; ?>
        </div>

        <?php if (!$isLoggedIn): ?>
          <p class="welcome-note">سجّل الدخول للوصول إلى جميع الميزات والمحتوى الحصري</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- Ramadan countdown -->
    <section class="ramadan-countdown-section">
      <div class="ramadan-countdown-card">
        <h3 class="ramadan-title">عداد رمضان</h3>
        <div class="ramadan-boxes">
          <div class="ramadan-box" id="dayTens">0</div>
          <div class="ramadan-box" id="dayOnes">0</div>
        </div>
        <p class="ramadan-text" id="ramadanText">...</p>
      </div>
    </section>

    <!-- Reels section - loaded from database -->
    <section class="reels-section" id="reels">
      <div class="section-header">
        <h2 class="section-title">أحدث الوصفات</h2>
        <p class="section-subtitle">استكشف أحدث الوصفات المضافة على المنصة</p>
      </div>

      <div class="reels-grid">
        <?php if (count($reels) > 0): ?>
          <?php foreach ($reels as $reel): ?>
          <div class="reel-card">
            <div class="reel-header">
              <span class="reel-category"><?= htmlspecialchars($reel['categoryName']) ?></span>
            </div>
            <div class="reel-thumbnail">
              <img
                src="images/<?= htmlspecialchars($reel['photoFileName']) ?>"
                alt="<?= htmlspecialchars($reel['name']) ?>"
                class="reel-thumbnail-img"
                onerror="this.src='images/default.png'">
              <div class="play-icon">▶</div>
            </div>
            <div class="reel-content">
              <?php if ($isLoggedIn): ?>
                <a href="view-recipe.php?id=<?= (int)$reel['id'] ?>" style="text-decoration:none;">
                  <h3 class="reel-title"><?= htmlspecialchars($reel['name']) ?></h3>
                </a>
              <?php else: ?>
                <h3 class="reel-title"><?= htmlspecialchars($reel['name']) ?></h3>
              <?php endif; ?>
              <div class="reel-stats">
                <span class="reel-stat">❤️ <?= (int)$reel['totalLikes'] ?></span>
              </div>
              <p class="reel-description"><?= htmlspecialchars($reel['description']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="text-align:center; color:#666;">لا توجد وصفات متاحة حالياً.</p>
        <?php endif; ?>
      </div>

      <?php if ($isLoggedIn): ?>
        <div class="section-footer">
          <a href="<?= $isAdmin ? 'admin.php' : 'user.php' ?>" class="view-all-btn">
            عرض جميع الوصفات
            <span class="arrow">←</span>
          </a>
        </div>
      <?php endif; ?>
    </section>

    <!-- Map section -->
    <section class="map-section">
      <h2 class="section-title" style="text-align:center;">وصفات رمضانية من العالم الإسلامي</h2>
      <p class="section-description">اكتشف نكهات رمضان المتنوعة من كل ركن في العالم الإسلامي ✨</p>

      <div class="map-container">
        <img src="images/map.jpg" class="world-map" alt="خريطة العالم الإسلامي">

        <div class="map-point" data-code="MA" style="top:23%;left:15%;" onclick="showCard('المغرب','شوربة حريرة','images/harira.jpg')"></div>
        <div class="map-point" data-code="DZ" style="top:28%;left:31%;" onclick="showCard('الجزائر','كسكس','images/couscus.jpg')"></div>
        <div class="map-point" data-code="EG" style="top:30%;left:62%;" onclick="showCard('مصر','كنافة','images/kunafa.jpg')"></div>
        <div class="map-point" data-code="JO" style="top:14%;left:68%;" onclick="showCard('الاردن','منسف','images/mansaf.jpg')"></div>
        <div class="map-point" data-code="LY" style="top:28%;left:47%;" onclick="showCard('ليبيا','البازين','images/bazin.jpg')"></div>
        <div class="map-point" data-code="KSA" style="top:32%;left:79%;" onclick="showCard('المملكة العربية السعودية','كبسة','images/kabsa.jpg')"></div>
        <div class="map-point" data-code="UAE" style="top:32%;left:89%;" onclick="showCard('الإمارات','هريس','images/harees.jpg')"></div>
        <div class="map-point" data-code="IR"  style="top:15%;left:90%;" onclick="showCard('إيران','آش رشتة','images/ash.jpg')"></div>
        <div class="map-point" data-code="PK"  style="top:20%;left:98.7%;" onclick="showCard('باكستان','دال وخبز','images/dal.jpg')"></div>
        <div class="map-point" data-code="SD"  style="top:55%;left:62%;" onclick="showCard('السودان','عصيدة','images/asida.jpg')"></div>
        <div class="map-point" data-code="IRQ" style="top:12%;left:76%;" onclick="showCard('العراق','مسكوف','images/masgouf.jpg')"></div>
        <div class="map-point" data-code="SO"  style="top:74%;left:81.9%;" onclick="showCard('الصومال','سمبوسة صومالية','images/samosa.jpg')"></div>
      </div>
    </section>

  </div>

  <!-- Map popup overlay -->
  <div class="card-overlay" id="cardOverlay" onclick="closeCard()"></div>
  <div class="recipe-card" id="recipeCard">
    <span class="close" onclick="closeCard()">×</span>
    <img id="cardImg" src="" alt="">
    <div class="recipe-card-content">
      <h3 id="cardTitle"></h3>
      <p id="cardDesc"></p>
      <?php if ($isLoggedIn): ?>
        <a href="user.php" class="recipe-card-btn">استكشف الوصفات</a>
      <?php else: ?>
        <a href="login.php" class="recipe-card-btn">سجّل الدخول للمزيد</a>
      <?php endif; ?>
    </div>
  </div>

  <footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
      <div class="footer-col footer-about">
        <div class="footer-brand">
          <img src="images/logo.png" alt="شعار سُفرة" class="footer-logo">
          <h3 class="footer-title">سُفرة 🌙</h3>
        </div>
        <p class="footer-text">منصة وصفات رمضانية تساعدك توصل لوصفات الإفطار والسحور بطريقة مرتبة وبسيطة.</p>
      </div>
      <div class="footer-col">
        <h4 class="footer-heading">استكشاف</h4>
        <ul class="footer-links">
          <li><a href="index.php">الرئيسية</a></li>
          <?php if ($isLoggedIn): ?>
            <?php if ($isAdmin): ?>
              <li><a href="admin.php">لوحة الإدارة</a></li>
            <?php else: ?>
              <li><a href="user.php">صفحتي</a></li>
              <li><a href="my-recipes.php">وصفاتي</a></li>
            <?php endif; ?>
          <?php else: ?>
            <li><a href="login.php">تسجيل الدخول</a></li>
            <li><a href="signup.php">إنشاء حساب</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-heading">التصنيفات</h4>
        <ul class="footer-links">
          <li><a href="<?= $isLoggedIn ? 'user.php' : 'login.php' ?>">إفطار</a></li>
          <li><a href="<?= $isLoggedIn ? 'user.php' : 'login.php' ?>">سحور</a></li>
          <li><a href="<?= $isLoggedIn ? 'user.php' : 'login.php' ?>">حلويات</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-heading">تواصل معنا</h4>
        <div class="footer-social">
          <a class="social-btn" href="#" aria-label="انستقرام">IG</a>
          <a class="social-btn" href="#" aria-label="إكس">X</a>
          <a class="social-btn" href="#" aria-label="فيسبوك">f</a>
          <a class="social-btn" href="#" aria-label="يوتيوب">▶</a>
        </div>
        <p class="footer-mini">البريد: <a href="mailto:sufrah@example.com">sufrah@example.com</a></p>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container footer-bottom-inner">
        <small>© 2026 سُفرة . جميع الحقوق محفوظة</small>
        <small>صُنع بـ <span aria-hidden="true">♥</span> لرمضان 🌙⭐</small>
      </div>
    </div>
  </footer>

  <script>
    // Map popup functions
    function showCard(country, dish, img) {
      document.getElementById('cardTitle').innerText = country;
      document.getElementById('cardDesc').innerText  = dish;
      document.getElementById('cardImg').src         = img;
      document.getElementById('recipeCard').style.display = 'block';
      document.getElementById('cardOverlay').classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function closeCard() {
      document.getElementById('recipeCard').style.display = 'none';
      document.getElementById('cardOverlay').classList.remove('active');
      document.body.style.overflow = 'auto';
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeCard();
    });

    // Ramadan countdown
    const ramadanStart = new Date("2026-02-19");
    function updateRamadanCounter() {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const start = new Date(ramadanStart);
      start.setHours(0, 0, 0, 0);
      const daysLeft = Math.ceil((start - today) / (1000 * 60 * 60 * 24));
      const tens = document.getElementById("dayTens");
      const ones = document.getElementById("dayOnes");
      const text = document.getElementById("ramadanText");
      if (daysLeft > 0) {
        const s = String(daysLeft).padStart(2, "0");
        tens.textContent = s[0];
        ones.textContent = s[1];
        text.textContent = "تبقى " + daysLeft + " يوم على رمضان";
      } else {
        const ramadanDay = Math.abs(daysLeft) + 1;
        if (ramadanDay <= 30) {
          const s = String(ramadanDay).padStart(2, "0");
          tens.textContent = s[0];
          ones.textContent = s[1];
          text.textContent = "اليوم " + ramadanDay + " من رمضان";
        } else {
          tens.textContent = "0";
          ones.textContent = "0";
          text.textContent = "انتهى رمضان، تقبل الله منا ومنكم";
        }
      }
    }
    updateRamadanCounter();
    setInterval(updateRamadanCounter, 3600000);
  </script>
  <script src="scroll.js"></script>

</body>
</html>