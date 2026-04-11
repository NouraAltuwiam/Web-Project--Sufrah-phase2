<?php
session_start();
require 'dp.php';

// حماية الصفحة: لازم المستخدم يكون مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['user_id'];

// جلب وصفات المستخدم
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

    <a href="user.php" class="sign-out">رجوع</a>
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
              // المكونات
              $ingStmt = $pdo->prepare("
                  SELECT ingredientName, ingredientQuantity
                  FROM ingredients
                  WHERE recipeID = ?
              ");
              $ingStmt->execute([$recipe['id']]);
              $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

              // الخطوات
              $instStmt = $pdo->prepare("
                  SELECT step, stepOrder
                  FROM instructions
                  WHERE recipeID = ?
                  ORDER BY stepOrder ASC
              ");
              $instStmt->execute([$recipe['id']]);
              $instructions = $instStmt->fetchAll(PDO::FETCH_ASSOC);

              // عدد الإعجابات
              $likesStmt = $pdo->prepare("
                  SELECT COUNT(*) AS totalLikes
                  FROM likes
                  WHERE recipeID = ?
              ");
              $likesStmt->execute([$recipe['id']]);
              $likesCount = $likesStmt->fetch(PDO::FETCH_ASSOC)['totalLikes'];
              ?>

              <tr>
                <td>
                  <a class="recipe-link" href="view-recipe.php?id=<?php echo $recipe['id']; ?>">
                    <?php
                    $img = "images/" . $recipe['photoFileName'];
                    if (!file_exists($img)) {
                      $img = "images/default.png";
                    }
                    ?>

                    <img class="thumb-img"
                    src="<?php echo htmlspecialchars($img); ?>"
                     alt="صورة الوصفة">
                    <div class="recipe-meta">
                      <span class="recipe-title"><?php echo htmlspecialchars($recipe['name']); ?></span>
                      <span class="pill"><?php echo htmlspecialchars($recipe['categoryName']); ?></span>
                    </div>
                  </a>
                </td>

                <td>
                  <ul class="list">
                    <?php if (empty($ingredients)): ?>
                    <li>لا توجد مكونات</li>
                     <?php else: ?>
                      <?php foreach ($ingredients as $ingredient): ?>
                      <li>
                     <?php echo htmlspecialchars($ingredient['ingredientName']); ?>
        —           <?php echo htmlspecialchars($ingredient['ingredientQuantity']); ?>
                   </li>
                     <?php endforeach; ?>
                     <?php endif; ?>
                  </ul>
                </td>

                <td>
                  <ol class="list">
                  <?php if (empty($instructions)): ?>
                    <li>لا توجد خطوات</li>
                    <?php else: ?>
                     <?php foreach ($instructions as $instruction): ?>
                       <li><?php echo htmlspecialchars($instruction['step']); ?></li>
                     <?php endforeach; ?>
                    <?php endif; ?>
                  </ol>
                </td>

                <td>
                  <?php if (!empty($recipe['videoFilePath'])): ?>
                    <a class="link"
                       href="videos/<?php echo htmlspecialchars($recipe['videoFilePath']); ?>"
                       target="_blank">رابط</a>
                  <?php else: ?>
                    <span class="pill">no video for recipe</span>
                  <?php endif; ?>
                </td>

                <td><?php echo $likesCount; ?></td>

                <td>
                  <a class="link" href="edit-recipe.php?id=<?php echo $recipe['id']; ?>">تعديل</a>
                </td>

                <td>
                  <a class="link"
                     href="delete-recipe.php?id=<?php echo $recipe['id']; ?>"
                     onclick="return confirm('هل تريد حذف هذه الوصفة؟');">حذف</a>
                </td>
              </tr>

            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
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
      <p class="footer-text">
        منصة وصفات رمضانية تساعدك توصل لوصفات الإفطار والسحور بطريقة مرتبة وبسيطة.
      </p>
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

      <p class="footer-mini">
        البريد: <a href="mailto:sufrah@example.com">sufrah@example.com</a>
      </p>
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
