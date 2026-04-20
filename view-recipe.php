<?php
// view-recipe.php
// Requirement: Displays all recipe info. Shows favourite/like/report buttons
//              only for regular users. Disables each button if already used.
//              Includes a comment form that submits to add_comment.php.

session_start();
require 'dp.php';

// Requirement: Only logged-in users can view recipes
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=" . urlencode("You must be logged in to view recipes."));
    exit();
}

$viewerId   = (int) $_SESSION['user_id'];
$viewerType = $_SESSION['user_type'] ?? '';

// Requirement: Check the recipe ID from the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . ($viewerType === 'admin' ? 'admin.php' : 'user.php'));
    exit();
}

$recipeId = (int) $_GET['id'];

// Requirement: Retrieve all recipe info from the database
$stmtRecipe = $pdo->prepare("
    SELECT r.*, u.firstName, u.lastName, u.photoFileName AS creatorPhoto, u.id AS creatorID,
           rc.categoryName
    FROM recipe r
    JOIN user u            ON r.userID    = u.id
    JOIN recipecategory rc ON r.categoryID = rc.id
    WHERE r.id = ?
");
$stmtRecipe->execute([$recipeId]);
$recipe = $stmtRecipe->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: " . ($viewerType === 'admin' ? 'admin.php' : 'user.php'));
    exit();
}

// Retrieve ingredients
$ingStmt = $pdo->prepare("SELECT ingredientName, ingredientQuantity FROM ingredients WHERE recipeID = ?");
$ingStmt->execute([$recipeId]);
$ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve instructions ordered by step number
$instStmt = $pdo->prepare("SELECT step, stepOrder FROM instructions WHERE recipeID = ? ORDER BY stepOrder ASC");
$instStmt->execute([$recipeId]);
$instructions = $instStmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve comments with commenter info
$commentStmt = $pdo->prepare("
    SELECT c.comment, c.date, u.firstName, u.lastName, u.photoFileName AS commenterPhoto
    FROM comment c
    JOIN user u ON c.userID = u.id
    WHERE c.recipeID = ?
    ORDER BY c.date DESC
");
$commentStmt->execute([$recipeId]);
$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total likes and favourites
$stmtLikeCount = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipeID = ?");
$stmtLikeCount->execute([$recipeId]);
$totalLikes = (int) $stmtLikeCount->fetchColumn();

$stmtFavCount = $pdo->prepare("SELECT COUNT(*) FROM favourites WHERE recipeID = ?");
$stmtFavCount->execute([$recipeId]);
$totalFavs = (int) $stmtFavCount->fetchColumn();

// Requirement: Only show action buttons if viewer is a regular user (not creator, not admin)
$isCreator   = ($viewerId === (int) $recipe['creatorID']);
$isAdmin     = ($viewerType === 'admin');
$showButtons = (!$isCreator && !$isAdmin);

// Requirement: Check if viewer already liked, favourited, or reported - disable button if so
$alreadyLiked    = false;
$alreadyFaved    = false;
$alreadyReported = false;

if ($showButtons) {
    $stmtLiked = $pdo->prepare("SELECT 1 FROM likes WHERE userID = ? AND recipeID = ?");
    $stmtLiked->execute([$viewerId, $recipeId]);
    $alreadyLiked = (bool) $stmtLiked->fetchColumn();

    $stmtFaved = $pdo->prepare("SELECT 1 FROM favourites WHERE userID = ? AND recipeID = ?");
    $stmtFaved->execute([$viewerId, $recipeId]);
    $alreadyFaved = (bool) $stmtFaved->fetchColumn();

    $stmtReported = $pdo->prepare("SELECT 1 FROM report WHERE userID = ? AND recipeID = ?");
    $stmtReported->execute([$viewerId, $recipeId]);
    $alreadyReported = (bool) $stmtReported->fetchColumn();
}

$backLink = ($viewerType === 'admin') ? 'admin.php' : 'user.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>سفرة - عرض الوصفة</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <!-- Header -->
  <header class="site-header">
    <div class="container header-inner">
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفــــرة</span>
      </div>
      <nav class="nav">
        <a class="nav-chip" href="<?= $backLink ?>">
          <?= ($viewerType === 'admin') ? 'لوحة الإدارة' : 'صفحة المستخدم' ?>
        </a>
        <?php if ($viewerType === 'user'): ?>
          <a class="nav-chip" href="my-recipes.php">وصفاتي</a>
        <?php endif; ?>
      </nav>
      <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="container">

    <section class="welcome">
      <h1>وصفة: <span id="recipeName"><?= htmlspecialchars($recipe['name']) ?></span></h1>
    </section>

    <div class="main-grid">

      <!-- Recipe details card -->
      <section class="user-info-card">
        <div class="recipe-top">
          <div class="recipe-photo">
            <img src="images/<?= htmlspecialchars($recipe['photoFileName']) ?>"
                 alt="صورة الوصفة"
                 class="recipe-photo-img"
                 onerror="this.src='images/default.png'">
          </div>
          <div class="recipe-head-info">
            <h2 class="recipe-title" id="recipeTitle"><?= htmlspecialchars($recipe['name']) ?></h2>

            <!-- Requirement: Show action buttons only for regular users (not admin, not recipe owner) -->
            <?php if ($showButtons): ?>
              <div class="recipe-actions">

                <!-- Requirement: Disable favourite button if user already added recipe to favourites -->
                <?php if ($alreadyFaved): ?>
                  <button class="filter-button" type="button" disabled>✅ في المفضلة</button>
                <?php else: ?>
                  <!-- Requirement: Clicking favourite sends request to add_favourite.php -->
                  <a href="add_favourite.php?recipe_id=<?= $recipeId ?>" class="filter-button">إضافة للمفضلة ❤️</a>
                <?php endif; ?>

                <!-- Requirement: Disable like button if user already liked this recipe -->
                <?php if ($alreadyLiked): ?>
                  <button class="filter-button" type="button" disabled>✅ أعجبك</button>
                <?php else: ?>
                  <!-- Requirement: Clicking like sends request to add_like.php -->
                  <a href="add_like.php?recipe_id=<?= $recipeId ?>" class="filter-button">إعجاب 👍</a>
                <?php endif; ?>

                <!-- Requirement: Disable report button if user already reported this recipe -->
                <?php if ($alreadyReported): ?>
                  <button class="filter-button" type="button" disabled>⏳ تم الإبلاغ</button>
                <?php else: ?>
                  <!-- Requirement: Clicking report sends request to add_report.php -->
                  <a href="add_report.php?recipe_id=<?= $recipeId ?>"
                     class="filter-button"
                     onclick="return confirm('هل تريد الإبلاغ عن هذه الوصفة؟');">إبلاغ 🚩</a>
                <?php endif; ?>

              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Creator, category and description info -->
        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">👩‍🍳</div>
            <div class="info-content">
              <div class="info-label">صاحب الوصفة</div>
              <div class="info-value">
                <div class="creator-info">
                  <img src="images/<?= htmlspecialchars($recipe['creatorPhoto'] ?: 'default.png') ?>"
                       class="creator-photo" alt="creator"
                       onerror="this.src='images/default.png'">
                  <span class="creator-name">
                    <?= htmlspecialchars($recipe['firstName'] . ' ' . $recipe['lastName']) ?>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">🏷️</div>
            <div class="info-content">
              <div class="info-label">الفئة</div>
              <div class="info-value">
                <span class="category-badge"><?= htmlspecialchars($recipe['categoryName']) ?></span>
              </div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">📝</div>
            <div class="info-content">
              <div class="info-label">الوصف</div>
              <div class="info-value" id="recipeDesc">
                <?= htmlspecialchars($recipe['description']) ?>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick stats side card -->
      <section class="my-recipes-card">
        <h3>معلومات سريعة</h3>
        <div class="stats-boxes">
          <div class="stat-box">
            <div class="stat-number"><?= $totalLikes ?></div>
            <div class="stat-label">عدد الإعجابات</div>
          </div>
          <div class="stat-box">
            <div class="stat-number"><?= $totalFavs ?></div>
            <div class="stat-label">عدد المفضلة</div>
          </div>
          <div class="stat-box">
            <div class="stat-number"><?= count($comments) ?></div>
            <div class="stat-label">عدد التعليقات</div>
          </div>
        </div>
      </section>

    </div>

    <!-- Ingredients table -->
    <section class="all-recipes-section">
      <h2 class="section-title">المكونات</h2>
      <?php if (!empty($ingredients)): ?>
        <table class="recipes-table">
          <thead>
            <tr><th>المكوّن</th><th>الكمية</th></tr>
          </thead>
          <tbody>
            <?php foreach ($ingredients as $ing): ?>
              <tr>
                <td><?= htmlspecialchars($ing['ingredientName']) ?></td>
                <td><?= htmlspecialchars($ing['ingredientQuantity']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>لا توجد مكونات مضافة.</p>
      <?php endif; ?>

      <!-- Healthy alternatives box (shown if recipe has alternatives stored) -->
      <div class="healthy-box">
        <div class="healthy-num">🌿</div>
        <div class="healthy-content">
          <div class="healthy-title">بدائل صحية (اختياري) 🌿</div>
          <div class="healthy-text">يمكن استبدال بعض المكونات ببدائل أكثر صحية حسب الرغبة.</div>
        </div>
      </div>
    </section>

    <!-- Instructions -->
    <section class="all-recipes-section">
      <h2 class="section-title">طريقة التحضير</h2>
      <?php if (!empty($instructions)): ?>
        <div class="recipe-steps">
          <?php foreach ($instructions as $inst): ?>
            <div class="step-item">
              <div class="step-num"><?= (int)$inst['stepOrder'] ?></div>
              <div class="step-text"><?= htmlspecialchars($inst['step']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>لا توجد خطوات مضافة.</p>
      <?php endif; ?>
    </section>

    <!-- Video section -->
    <?php if (!empty($recipe['videoFilePath'])): ?>
      <section class="all-recipes-section">
        <h2 class="section-title">الفيديو</h2>
        <div class="filter-bar" style="justify-content: space-between;">
          <div style="font-weight:600; color:#B56A63;">
            رابط الفيديو:
            <a class="recipe-name-link"
               href="videos/<?= htmlspecialchars($recipe['videoFilePath']) ?>"
               target="_blank" rel="noopener">
              اضغط هنا لمشاهدة الفيديو
            </a>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- Requirement: Comment form - recipe ID included as hidden input -->
    <section class="favorites-section">
      <h2 class="section-title">التعليقات</h2>

      <!-- Requirement: Form submits to add_comment.php which adds comment to DB and redirects here -->
      <form action="add_comment.php" method="POST" class="comment-box">
        <input type="hidden" name="recipe_id" value="<?= $recipeId ?>">
        <input type="text" name="comment" class="comment-input" placeholder="اكتب تعليقك هنا..." required />
        <button class="filter-button" type="submit">إضافة تعليق</button>
      </form>

      <!-- Display existing comments -->
      <div class="comments-list">
        <?php if (!empty($comments)): ?>
          <?php foreach ($comments as $c): ?>
            <div class="comment-item">
              <div class="creator-info">
                <img src="images/<?= htmlspecialchars($c['commenterPhoto'] ?: 'default.png') ?>"
                     class="creator-photo" alt="commenter"
                     onerror="this.src='images/default.png'">
                <span class="creator-name">
                  <?= htmlspecialchars($c['firstName'] . ' ' . $c['lastName']) ?>
                </span>
                <div class="comment-date"><?= htmlspecialchars($c['date']) ?></div>
              </div>
              <p class="comment-text"><?= htmlspecialchars($c['comment']) ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:#666;">لا توجد تعليقات بعد. كن أول من يعلّق!</p>
        <?php endif; ?>
      </div>
    </section>

  </div>

  <footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">

      <div class="footer-col footer-about">
        <div class="footer-brand">
          <img src="images/logo.png" alt="شعار سُفرة" class="footer-logo">
          <h3 class="footer-title">سُفرة</h3>
        </div>
        <p class="footer-text">منصة وصفات رمضانية تساعدك توصل لوصفات الإفطار والسحور بطريقة مرتبة وبسيطة.</p>
      </div>

      <div class="footer-col">
        <h4 class="footer-heading">استكشاف</h4>
        <ul class="footer-links">
          <li><a href="index.php">الرئيسية</a></li>
          <?php if ($viewerType === 'user'): ?>
            <li><a href="user.php">صفحتي</a></li>
            <li><a href="my-recipes.php">وصفاتي</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-heading">التصنيفات</h4>
        <ul class="footer-links">
          <li><a href="user.php">إفطار</a></li>
          <li><a href="user.php">سحور</a></li>
          <li><a href="user.php">حلويات</a></li>
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
        <small>© 2026 سُفرة .</small>
        <small>صُنع بـ <span aria-hidden="true">♥</span> لرمضان</small>
      </div>
    </div>
  </footer>

</body>
</html>
