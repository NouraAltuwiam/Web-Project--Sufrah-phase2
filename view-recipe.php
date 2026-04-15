<?php
// view-recipe.php
// Requirement 10: Displays full recipe info with comments, like/favourite/report buttons

session_start();
require 'dp.php';

// Requirement 5: Only logged-in users can view recipes
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=" . urlencode("You must be logged in to view recipes."));
    exit();
}

$viewerId   = (int) $_SESSION['user_id'];
$viewerType = $_SESSION['user_type'] ?? '';

// Requirement 10a: Check the recipe ID from the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . ($viewerType === 'admin' ? 'admin.php' : 'user.php'));
    exit();
}

$recipeId = (int) $_GET['id'];

// Requirement 10b: Retrieve all recipe info and creator info from the database
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

// Retrieve instructions ordered by stepOrder
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

// Count totals
$stmtLikeCount  = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipeID = ?");
$stmtLikeCount->execute([$recipeId]);
$totalLikes = (int) $stmtLikeCount->fetchColumn();

$stmtFavCount = $pdo->prepare("SELECT COUNT(*) FROM favourites WHERE recipeID = ?");
$stmtFavCount->execute([$recipeId]);
$totalFavs = (int) $stmtFavCount->fetchColumn();

// Requirement 10d: Button states - only show if viewer is NOT the creator and NOT an admin
$isCreator = ($viewerId === (int) $recipe['creatorID']);
$isAdmin   = ($viewerType === 'admin');
$showButtons = (!$isCreator && !$isAdmin);

// Check if viewer already liked, favourited, or reported this recipe
$alreadyLiked = false;
$alreadyFaved = false;
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
  <title>سفرة | <?php echo htmlspecialchars($recipe['name']); ?></title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفــــرة</span>
      </div>
      <nav class="nav">
        <a class="nav-chip" href="<?php echo $backLink; ?>">
          <?php echo ($viewerType === 'admin') ? 'لوحة الإدارة' : 'صفحة المستخدم'; ?>
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
      <h1>وصفة: <span><?php echo htmlspecialchars($recipe['name']); ?></span></h1>
    </section>

    <div class="main-grid">

      <!-- Requirement 10b: Recipe details card -->
      <section class="user-info-card">
        <div class="recipe-top">
          <div class="recipe-photo">
            <img src="images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
                 alt="recipe photo"
                 class="recipe-photo-img"
                 onerror="this.src='images/default.png'">
          </div>
          <div class="recipe-head-info">
            <h2 class="recipe-title"><?php echo htmlspecialchars($recipe['name']); ?></h2>

            <!-- Requirement 10d: Show action buttons only if not creator and not admin -->
            <?php if ($showButtons): ?>
              <div class="recipe-actions">

                <!-- Favourite button: disabled if already added -->
                <?php if ($alreadyFaved): ?>
                  <button class="filter-button" type="button" disabled>✅ في المفضلة</button>
                <?php else: ?>
                  <a href="add_favourite.php?recipe_id=<?php echo $recipeId; ?>" class="filter-button">إضافة للمفضلة ❤️</a>
                <?php endif; ?>

                <!-- Like button: disabled if already liked -->
                <?php if ($alreadyLiked): ?>
                  <button class="filter-button" type="button" disabled>✅ أعجبك</button>
                <?php else: ?>
                  <a href="add_like.php?recipe_id=<?php echo $recipeId; ?>" class="filter-button">إعجاب 👍</a>
                <?php endif; ?>

                <!-- Report button: disabled if already reported and still pending -->
                <?php if ($alreadyReported): ?>
                  <button class="filter-button" type="button" disabled>⏳ تم الإبلاغ</button>
                <?php else: ?>
                  <a href="add_report.php?recipe_id=<?php echo $recipeId; ?>"
                     class="filter-button"
                     onclick="return confirm('هل تريد الإبلاغ عن هذه الوصفة؟');">إبلاغ 🚩</a>
                <?php endif; ?>

              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">👩‍🍳</div>
            <div class="info-content">
              <div class="info-label">صاحب الوصفة</div>
              <div class="info-value">
                <div class="creator-info">
                  <img src="images/<?php echo htmlspecialchars($recipe['creatorPhoto'] ?: 'default.png'); ?>"
                       class="creator-photo" alt="creator"
                       onerror="this.src='images/default.png'">
                  <span class="creator-name">
                    <?php echo htmlspecialchars($recipe['firstName'] . ' ' . $recipe['lastName']); ?>
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
                <span class="category-badge"><?php echo htmlspecialchars($recipe['categoryName']); ?></span>
              </div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">📝</div>
            <div class="info-content">
              <div class="info-label">الوصف</div>
              <div class="info-value"><?php echo htmlspecialchars($recipe['description']); ?></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick stats side card -->
      <section class="my-recipes-card">
        <h3>معلومات سريعة</h3>
        <div class="stats-boxes">
          <div class="stat-box">
            <div class="stat-number"><?php echo $totalLikes; ?></div>
            <div class="stat-label">عدد الإعجابات</div>
          </div>
          <div class="stat-box">
            <div class="stat-number"><?php echo $totalFavs; ?></div>
            <div class="stat-label">عدد المفضلة</div>
          </div>
          <div class="stat-box">
            <div class="stat-number"><?php echo count($comments); ?></div>
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
                <td><?php echo htmlspecialchars($ing['ingredientName']); ?></td>
                <td><?php echo htmlspecialchars($ing['ingredientQuantity']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No ingredients listed.</p>
      <?php endif; ?>
    </section>

    <!-- Instructions -->
    <section class="all-recipes-section">
      <h2 class="section-title">طريقة التحضير</h2>
      <?php if (!empty($instructions)): ?>
        <div class="recipe-steps">
          <?php foreach ($instructions as $inst): ?>
            <div class="step-item">
              <div class="step-num"><?php echo (int)$inst['stepOrder']; ?></div>
              <div class="step-text"><?php echo htmlspecialchars($inst['step']); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>No steps listed.</p>
      <?php endif; ?>
    </section>

    <!-- Video -->
    <?php if (!empty($recipe['videoFilePath'])): ?>
      <section class="all-recipes-section">
        <h2 class="section-title">الفيديو</h2>
        <div class="filter-bar">
          <a class="recipe-name-link"
             href="videos/<?php echo htmlspecialchars($recipe['videoFilePath']); ?>"
             target="_blank">
            اضغط هنا لمشاهدة الفيديو
          </a>
        </div>
      </section>
    <?php endif; ?>

    <!-- Requirement 10c: Comments section with add-comment form -->
    <section class="favorites-section">
      <h2 class="section-title">التعليقات</h2>

      <!-- Comment form - hidden recipe ID (Req 10c) -->
      <form action="add_comment.php" method="POST" class="comment-box">
        <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
        <input type="text" name="comment" class="comment-input" placeholder="اكتب تعليقك هنا..." required />
        <button class="filter-button" type="submit">إضافة تعليق</button>
      </form>

      <!-- Display existing comments -->
      <div class="comments-list">
        <?php if (!empty($comments)): ?>
          <?php foreach ($comments as $c): ?>
            <div class="comment-item">
              <div class="creator-info">
                <img src="images/<?php echo htmlspecialchars($c['commenterPhoto'] ?: 'default.png'); ?>"
                     class="creator-photo" alt="commenter"
                     onerror="this.src='images/default.png'">
                <span class="creator-name">
                  <?php echo htmlspecialchars($c['firstName'] . ' ' . $c['lastName']); ?>
                </span>
                <div class="comment-date"><?php echo htmlspecialchars($c['date']); ?></div>
              </div>
              <p class="comment-text"><?php echo htmlspecialchars($c['comment']); ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:#666;">No comments yet. Be the first to comment!</p>
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