<?php
// my-recipes.php
// Requirement 7: Shows all recipes added by the logged-in user

session_start();
require 'dp.php';

// Requirement 5: Check that the user is logged in as a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Requirement 7a: Retrieve all recipes for this user from the database
$stmt = $pdo->prepare("
    SELECT r.*, rc.categoryName
    FROM recipe r
    JOIN recipecategory rc ON r.categoryID = rc.id
    WHERE r.userID = ?
    ORDER BY r.id DESC
");
$stmt->execute([$userId]);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>سُفره | وصفاتي</title>
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
      <a class="nav-chip" href="user.php">صفحة المستخدم</a>
      <a class="nav-chip" href="my-recipes.php">وصفاتي</a>
    </nav>
    <!-- Requirement 12: Sign-out link goes to signout.php -->
    <a href="signout.php" class="sign-out">تسجيل الخروج</a>
  </div>
</header>

<div class="container">
  <main class="card">
    <div class="recipes-head">
      <h2 class="section-title">وصفاتي</h2>
      <a class="btn btn-primary" href="add-recipe.php">إضافة وصفة جديدة</a>
    </div>

    <?php if (count($recipes) > 0): ?>
      <div class="table-wrap">
        <table class="recipes-table">
          <thead>
            <tr>
              <th>الوصفة</th>
              <th>المكونات</th>
              <th>الخطوات</th>
              <th>فيديو</th>
              <th>عدد الإعجابات</th>
              <th>تعديل</th>
              <th>حذف</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recipes as $recipe): ?>
              <?php
              // Retrieve ingredients for this recipe
              $ingStmt = $pdo->prepare("SELECT ingredientName, ingredientQuantity FROM ingredients WHERE recipeID = ?");
              $ingStmt->execute([$recipe['id']]);
              $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

              // Retrieve instructions ordered by stepOrder
              $instStmt = $pdo->prepare("SELECT step, stepOrder FROM instructions WHERE recipeID = ? ORDER BY stepOrder ASC");
              $instStmt->execute([$recipe['id']]);
              $instructions = $instStmt->fetchAll(PDO::FETCH_ASSOC);

              // Requirement 7a: Count likes for this recipe
              $likesStmt = $pdo->prepare("SELECT COUNT(*) AS totalLikes FROM likes WHERE recipeID = ?");
              $likesStmt->execute([$recipe['id']]);
              $likesCount = (int) $likesStmt->fetchColumn();

              // Build photo path with fallback
              $imgPath = "images/" . $recipe['photoFileName'];
              ?>
              <tr>
                <td>
                  <!-- Requirement 7a: Recipe name and photo are links to view-recipe page -->
                  <a class="recipe-link" href="view-recipe.php?id=<?php echo (int)$recipe['id']; ?>">
                    <img class="thumb-img"
                         src="<?php echo htmlspecialchars($imgPath); ?>"
                         alt="recipe photo"
                         onerror="this.src='images/default.png'">
                    <div class="recipe-meta">
                      <span class="recipe-title"><?php echo htmlspecialchars($recipe['name']); ?></span>
                      <span class="pill"><?php echo htmlspecialchars($recipe['categoryName']); ?></span>
                    </div>
                  </a>
                </td>
                <td>
                  <ul class="list">
                    <?php if (empty($ingredients)): ?>
                      <li>No ingredients added.</li>
                    <?php else: ?>
                      <?php foreach ($ingredients as $ing): ?>
                        <li><?php echo htmlspecialchars($ing['ingredientName']); ?> — <?php echo htmlspecialchars($ing['ingredientQuantity']); ?></li>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </ul>
                </td>
                <td>
                  <ol class="list">
                    <?php if (empty($instructions)): ?>
                      <li>No steps added.</li>
                    <?php else: ?>
                      <?php foreach ($instructions as $inst): ?>
                        <li><?php echo htmlspecialchars($inst['step']); ?></li>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </ol>
                </td>
                <td>
                  <?php if (!empty($recipe['videoFilePath'])): ?>
                    <a class="link"
                       href="videos/<?php echo htmlspecialchars($recipe['videoFilePath']); ?>"
                       target="_blank">Watch</a>
                  <?php else: ?>
                    <span class="pill">No video</span>
                  <?php endif; ?>
                </td>
                <!-- Requirement 7a: Count likes from database -->
                <td><?php echo $likesCount; ?></td>
                <td>
                  <!-- Requirement 7a: Edit link goes to edit-recipe page for this recipe -->
                  <a class="link" href="edit-recipe.php?id=<?php echo (int)$recipe['id']; ?>">تعديل</a>
                </td>
                <td>
                  <!-- Requirement 7a: Delete link goes to delete-recipe.php -->
                  <a class="link"
                     href="delete-recipe.php?id=<?php echo (int)$recipe['id']; ?>"
                     onclick="return confirm('Are you sure you want to delete this recipe?');">حذف</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <!-- Requirement 7a: Show message if no recipes -->
      <p class="note-text">لا توجد لديك وصفات مضافة حاليًا.</p>
    <?php endif; ?>
  </main>
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
        <li><a href="my-recipes.php">وصفاتي</a></li>
        <li><a href="add-recipe.php">إضافة وصفة</a></li>
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