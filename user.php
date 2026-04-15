<?php
session_start();
require_once 'dp.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?msg=" . urlencode("You must login as a regular user"));
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* 1) Retrieve user info */
$stmt_user = $pdo->prepare("SELECT id, firstName, lastName, emailAddress, photoFileName 
                             FROM user 
                             WHERE id = ? AND userType = 'user'");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

if (!$user) {
    header("Location: login.php?msg=" . urlencode("User not found"));
    exit();
}

$full_name  = trim($user['firstName'] . " " . $user['lastName']);
$photo_file = !empty($user['photoFileName']) ? $user['photoFileName'] : "default.png";
$photo_path = "images/" . $photo_file;

/* 2) Total recipes for this user */
$stmt_total_recipes = $pdo->prepare("SELECT COUNT(*) AS total_recipes FROM recipe WHERE userID = ?");
$stmt_total_recipes->execute([$user_id]);
$total_recipes = (int) $stmt_total_recipes->fetchColumn();

/* 3) Total likes for all user's recipes */
$stmt_total_likes = $pdo->prepare("SELECT COUNT(l.recipeID) AS total_likes
                                   FROM recipe r
                                   LEFT JOIN likes l ON r.id = l.recipeID
                                   WHERE r.userID = ?");
$stmt_total_likes->execute([$user_id]);
$total_likes = (int) $stmt_total_likes->fetchColumn();

/* 4) Retrieve categories for the filter form */
$stmt_categories = $pdo->query("SELECT id, categoryName FROM recipecategory ORDER BY categoryName");
$categories = $stmt_categories->fetchAll();

/* 5) Recipes list (GET = all, POST = filtered by category) */
$selected_category_id = "";
$recipes = [];
$no_recipes_message = "";

$base_sql = "SELECT r.id, r.name, r.photoFileName,
                    u.firstName, u.lastName, u.photoFileName AS userPhoto,
                    c.categoryName, COUNT(l.recipeID) AS totalLikes
             FROM recipe r
             LEFT JOIN user u ON r.userID = u.id
             LEFT JOIN recipecategory c ON r.categoryID = c.id
             LEFT JOIN likes l ON r.id = l.recipeID";

$group_sql = " GROUP BY r.id, r.name, r.photoFileName,
                         u.firstName, u.lastName, u.photoFileName,
                         c.categoryName
               ORDER BY r.id DESC";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id']) && $_POST['category_id'] !== '') {
    $selected_category_id = (int) $_POST['category_id'];
    $stmt_recipes = $pdo->prepare($base_sql . " WHERE r.categoryID = ?" . $group_sql);
    $stmt_recipes->execute([$selected_category_id]);
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
        $selected_category_id = "";
    }
    $stmt_recipes = $pdo->prepare($base_sql . $group_sql);
    $stmt_recipes->execute();
}

$recipes = $stmt_recipes->fetchAll();

if (empty($recipes)) {
    $no_recipes_message = "لا توجد وصفات في هذه الفئة.";
}

/* 6) Retrieve favourite recipes of this user */
$stmt_favourites = $pdo->prepare("SELECT r.id, r.name, r.photoFileName
                                   FROM favourites f
                                   INNER JOIN recipe r ON f.recipeID = r.id
                                   WHERE f.userID = ?
                                   ORDER BY r.id DESC");
$stmt_favourites->execute([$user_id]);
$favourites = $stmt_favourites->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سفرة</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="style.css">
  <style>
    .fav-item:hover { color: #8FAE9E; text-decoration: underline; }
    .filter-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 16px 0 20px; }
    .filter-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; width: 100%; }
    .remove-link { display: inline-block; text-decoration: none; color: inherit; width: 100%; height: 100%; }
    .block-btn { cursor: pointer; }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفــــرة</span>
      </div>
      <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="container">

    <section class="welcome">
      <h1>مرحبًا <span><?= htmlspecialchars($user['firstName']) ?></span> !</h1>
    </section>

    <div class="main-grid">

      <section class="user-info-card">
        <div class="user-header">
          <div class="user-photo">
            <img src="<?= htmlspecialchars($photo_path) ?>" alt="User Avatar" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div class="user-title">
            <h2><?= htmlspecialchars($full_name) ?></h2>
          </div>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">👤</div>
            <div class="info-content">
              <div class="info-label">الاسم</div>
              <div class="info-value"><?= htmlspecialchars($full_name) ?></div>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon">📧</div>
            <div class="info-content">
              <div class="info-label">البريد الإلكتروني</div>
              <div class="info-value"><?= htmlspecialchars($user['emailAddress']) ?></div>
            </div>
          </div>
        </div>
      </section>

      <section class="my-recipes-card">
        <h3>وصفاتي</h3>
        <a href="my-recipes.php" class="recipes-link">
          عرض جميع وصفاتي
          <img src="images/arrow.svg" class="icon-sm" alt="سهم">
        </a>
        <div class="stats-boxes">
          <div class="stat-box">
            <div class="stat-number"><?= $total_recipes ?></div>
            <div class="stat-label">إجمالي الوصفات</div>
          </div>
          <div class="stat-box">
            <div class="stat-number">
              <img src="images/heart.svg" class="heart-icon" alt="قلب">
              <span><?= $total_likes ?></span>
            </div>
            <div class="stat-label">إجمالي الإعجابات</div>
          </div>
        </div>
      </section>

    </div>

    <!-- All Recipes -->
    <section class="all-recipes-section">
      <h2 class="section-title">جميع الوصفات المتاحة</h2>

      <div class="filter-bar">
        <form method="POST" action="user.php" class="filter-form">
          <img src="images/filter.svg" class="icon-sm" alt="تصفية">
          <label for="categoryFilter" class="filter-label">تصفية حسب الفئة:</label>
          <select name="category_id" id="categoryFilter" class="category-select">
            <option value="">جميع الفئات</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"
                <?= ((string)$selected_category_id === (string)$cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['categoryName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="filter-button" type="submit">تصفية</button>
        </form>
      </div>

      <?php if ($no_recipes_message !== ""): ?>
        <p><?= htmlspecialchars($no_recipes_message) ?></p>
      <?php else: ?>
        <table class="recipes-table">
          <thead>
            <tr>
              <th>اسم الوصفة</th>
              <th>صورة الوصفة</th>
              <th>صاحب الوصفة</th>
              <th>عدد الإعجابات</th>
              <th>الفئة</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recipes as $recipe): ?>
              <tr>
                <td>
                  <a href="view-recipe.php?id=<?= (int)$recipe['id'] ?>" class="recipe-name-link">
                    <?= htmlspecialchars($recipe['name']) ?>
                  </a>
                </td>
                <td>
                  <img class="thumb-img"
                       src="images/<?= htmlspecialchars($recipe['photoFileName']) ?>"
                       alt="<?= htmlspecialchars($recipe['name']) ?>"
                       onerror="this.src='images/default.png'">
                </td>
                <td>
                  <div class="creator-info">
                    <img src="images/<?= htmlspecialchars(!empty($recipe['userPhoto']) ? $recipe['userPhoto'] : 'default.png') ?>"
                         class="creator-photo"
                         alt="<?= htmlspecialchars($recipe['firstName']) ?>"
                         onerror="this.src='images/default.png'">
                    <span class="creator-name">
                      <?= htmlspecialchars(trim($recipe['firstName'] . " " . $recipe['lastName'])) ?>
                    </span>
                  </div>
                </td>
                <td>
                  <span class="likes-count">
                    <img src="images/heart.svg" class="likes-heart" alt="إعجاب">
                    <?= (int)$recipe['totalLikes'] ?>
                  </span>
                </td>
                <td>
                  <span class="reel-category"><?= htmlspecialchars($recipe['categoryName']) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <!-- Favourites -->
    <section class="favorites-section">
      <h2 class="section-title">
        <img src="images/heart.svg" class="heart-title" alt="قلب">
        وصفاتي المفضلة
      </h2>

      <?php if (!empty($favourites)): ?>
        <table class="favorites-table">
          <thead>
            <tr>
              <th>اسم الوصفة</th>
              <th>صورة الوصفة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($favourites as $fav): ?>
              <tr>
                <td>
                  <a href="view-recipe.php?id=<?= (int)$fav['id'] ?>" class="fav-name">
                    <?= htmlspecialchars($fav['name']) ?>
                  </a>
                </td>
                <td>
                  <img class="thumb-img"
                       src="images/<?= htmlspecialchars($fav['photoFileName']) ?>"
                       alt="<?= htmlspecialchars($fav['name']) ?>"
                       onerror="this.src='images/default.png'">
                </td>
                <td>
                  <a href="remove-favourite.php?recipe_id=<?= (int)$fav['id'] ?>"
                     onclick="return confirm('هل تريد حذف هذه الوصفة من المفضلة؟');">
                    <button class="block-btn" type="button">حذف</button>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>لا توجد وصفات مفضلة لديك.</p>
      <?php endif; ?>
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
          <li><a href="my-recipes.php">وصفاتي</a></li>
          <li><a href="add-recipe.php">إضافة وصفة</a></li>
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
        <small>© 2026 سُفرة . جميع الحقوق محفوظة</small>
        <small>صُنع بـ <span aria-hidden="true">♥</span> لرمضان 🌙</small>
      </div>
    </div>
  </footer>

</body>
</html>