<?php
// my-recipes.php
// Requirement: My recipes page - shows all recipes added by the logged-in user.

session_start();
require 'dp.php';

// Requirement: Check that the user is logged in as a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Requirement: Retrieve all recipes added by this user from the database
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

  <!-- Header -->
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
      <!-- Requirement: Security - sign-out clears session and redirects to homepage -->
      <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="container">
    <main class="card">
      <div class="recipes-head">
        <div>
          <h2 class="section-title">وصفاتي</h2>
        </div>
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
                <th>فيديو / رابط</th>
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

                // Retrieve instructions ordered by step number
                $instStmt = $pdo->prepare("SELECT step, stepOrder FROM instructions WHERE recipeID = ? ORDER BY stepOrder ASC");
                $instStmt->execute([$recipe['id']]);
                $instructions = $instStmt->fetchAll(PDO::FETCH_ASSOC);

                // Requirement: Count and display likes from database for this recipe
                $likesStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipeID = ?");
                $likesStmt->execute([$recipe['id']]);
                $likesCount = (int) $likesStmt->fetchColumn();
                ?>
                <tr>
                  <td>
                    <!-- Requirement: Recipe name and photo are a generated link to view-recipe page -->
                    <a class="recipe-link" href="view-recipe.php?id=<?= (int)$recipe['id'] ?>">
                      <img class="thumb-img"
                           src="images/<?= htmlspecialchars($recipe['photoFileName']) ?>"
                           alt="صورة الوصفة"
                           onerror="this.src='images/default.png'">
                      <div class="recipe-meta">
                        <span class="recipe-title"><?= htmlspecialchars($recipe['name']) ?></span>
                        <span class="pill"><?= htmlspecialchars($recipe['categoryName']) ?></span>
                      </div>
                    </a>
                  </td>
                  <td>
                    <ul class="list">
                      <?php if (empty($ingredients)): ?>
                        <li>لا توجد مكونات.</li>
                      <?php else: ?>
                        <?php foreach ($ingredients as $ing): ?>
                          <li><?= htmlspecialchars($ing['ingredientName']) ?> — <?= htmlspecialchars($ing['ingredientQuantity']) ?></li>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </ul>
                  </td>
                  <td>
                    <ol class="list">
                      <?php if (empty($instructions)): ?>
                        <li>لا توجد خطوات.</li>
                      <?php else: ?>
                        <?php foreach ($instructions as $inst): ?>
                          <li><?= htmlspecialchars($inst['step']) ?></li>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </ol>
                  </td>
                  <td>
                    <?php if (!empty($recipe['videoFilePath'])): ?>
                      <a class="link" href="videos/<?= htmlspecialchars($recipe['videoFilePath']) ?>" target="_blank">مشاهدة</a>
                    <?php else: ?>
                      <span class="pill">لا يوجد</span>
                    <?php endif; ?>
                  </td>
                  <!-- Requirement: Count and display likes from database -->
                  <td>
                    <img src="images/heart.svg" class="likes-heart" alt="إعجاب">
                    <?= $likesCount ?>
                  </td>
                  <td>
                    <!-- Requirement: Edit link is a generated link to edit-recipe page for this recipe -->
                    <a class="link" href="edit-recipe.php?id=<?= (int)$recipe['id'] ?>">تعديل</a>
                  </td>
                  <td>
                    <!-- Requirement: Delete link is a generated link to delete-recipe.php -->
                    <button class="delete-btn link"
        data-id="<?= (int)$recipe['id'] ?>">حذف</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <!-- Requirement: Show appropriate message if user has no recipes -->
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
          <li><a href="login.php">تسجيل الدخول</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-heading">التصنيفات</h4>
        <ul class="footer-links">
          <li><a href="my-recipes.php">إفطار</a></li>
          <li><a href="my-recipes.php">سحور</a></li>
          <li><a href="my-recipes.php">حلويات</a></li>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
$(document).on('click', '.delete-btn', function () {

    if (!confirm("هل أنت متأكد من الحذف؟")) return;

    let button = $(this);
    let recipeId = button.data('id');

    $.ajax({
        url: 'ajax-delete-recipe.php',
        type: 'POST',
        data: { id: recipeId },

        success: function (response) {
            if (response === "true") {
                // حذف الصف من الجدول
                button.closest('tr').remove();
            } else {
                alert("فشل الحذف");
            }
        }
    });
});
</script>
</body>
</html>
