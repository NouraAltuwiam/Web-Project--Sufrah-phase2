<?php
// edit-recipe.php
// Requirement 9: Edit recipe page - loads existing recipe data into the form

session_start();
require 'dp.php';

// Requirement 5: Only logged-in regular users
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Requirement 9a: Check the recipe ID from the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my-recipes.php");
    exit();
}

$recipeId = (int) $_GET['id'];

// Retrieve the recipe and verify it belongs to this user
$stmt = $pdo->prepare("SELECT * FROM recipe WHERE id = ? AND userID = ?");
$stmt->execute([$recipeId, $userId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: my-recipes.php");
    exit();
}

// Retrieve all categories for the drop-down
$categories = $pdo->query("SELECT id, categoryName FROM recipecategory ORDER BY categoryName")->fetchAll(PDO::FETCH_ASSOC);

// Retrieve existing ingredients
$ingStmt = $pdo->prepare("SELECT ingredientName, ingredientQuantity FROM ingredients WHERE recipeID = ?");
$ingStmt->execute([$recipeId]);
$ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve existing instructions ordered by stepOrder
$instStmt = $pdo->prepare("SELECT step, stepOrder FROM instructions WHERE recipeID = ? ORDER BY stepOrder ASC");
$instStmt->execute([$recipeId]);
$instructions = $instStmt->fetchAll(PDO::FETCH_ASSOC);

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سُفرة | تعديل الوصفة</title>
  <link href="style.css" rel="stylesheet">
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
    <a href="signout.php" class="sign-out">تسجيل الخروج</a>
  </div>
</header>

<div class="container">
  <div class="form-container">
    <h1>تعديل الوصفة</h1>

    <?php if ($error !== ''): ?>
      <p style="color:red; margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Requirement 9b: Form submits to edit_recipe_action.php -->
    <form id="editForm" action="edit_recipe_action.php" method="POST" enctype="multipart/form-data">

      <!-- Requirement 9a: Recipe ID as hidden input -->
      <input type="hidden" name="recipeID" value="<?php echo $recipeId; ?>">

      <div class="form-group">
        <label>اسم الوصفة:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($recipe['name']); ?>" required>
      </div>

      <div class="form-group">
        <label>التصنيف:</label>
        <select name="categoryID" required>
          <option value="">اختر التصنيف</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>"
              <?php echo ($cat['id'] == $recipe['categoryID']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['categoryName']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>الوصف:</label>
        <textarea name="description" required><?php echo htmlspecialchars($recipe['description']); ?></textarea>
      </div>

      <!-- Requirement 9b: If user uploads new photo it replaces the old one, otherwise old remains -->
      <div class="form-group">
        <label>صورة الوصفة الحالية:</label>
        <img src="images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
             alt="current photo"
             style="max-width:150px; display:block; margin-bottom:8px;"
             onerror="this.src='images/default.png'">
        <label>رفع صورة جديدة (اختياري - اتركه فارغاً للإبقاء على الحالية):</label>
        <input type="file" name="recipePhoto" accept="image/*">
      </div>

      <!-- Requirement 9b: If user uploads new video it replaces the old one -->
      <div class="form-group">
        <label>الفيديو الحالي:
          <?php if (!empty($recipe['videoFilePath'])): ?>
            <a href="videos/<?php echo htmlspecialchars($recipe['videoFilePath']); ?>" target="_blank">مشاهدة</a>
          <?php else: ?>
            لا يوجد
          <?php endif; ?>
        </label>
        <label>رفع فيديو جديد (اختياري - اتركه فارغاً للإبقاء على الحالي):</label>
        <input type="file" name="recipeVideo" accept="video/*">
      </div>

      <!-- Ingredients section pre-filled with existing data -->
      <div class="section-title" style="margin-top:20px;">المكونات:</div>
      <div id="ingredientsContainer">
        <?php if (!empty($ingredients)): ?>
          <?php foreach ($ingredients as $ing): ?>
            <div class="ingredient-row">
              <input type="text" name="ing_name[]" value="<?php echo htmlspecialchars($ing['ingredientName']); ?>" placeholder="اسم المكون" required>
              <input type="text" name="ing_qty[]"  value="<?php echo htmlspecialchars($ing['ingredientQuantity']); ?>" placeholder="الكمية" required>
              <button type="button" class="delete-row-btn" onclick="this.parentElement.remove()">حذف</button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="ingredient-row">
            <input type="text" name="ing_name[]" placeholder="اسم المكون" required>
            <input type="text" name="ing_qty[]"  placeholder="الكمية" required>
            <button type="button" class="delete-row-btn" onclick="this.parentElement.remove()">حذف</button>
          </div>
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-outline" style="margin-top:8px;" onclick="addIngredient()">+ إضافة مكون</button>

      <!-- Instructions section pre-filled with existing data -->
      <div class="section-title" style="margin-top:20px;">خطوات التحضير:</div>
      <div id="stepsContainer">
        <?php if (!empty($instructions)): ?>
          <?php foreach ($instructions as $i => $inst): ?>
            <div class="ingredient-row">
              <label style="min-width:60px;">الخطوة <?php echo ($i + 1); ?>:</label>
              <input type="text" name="step[]" value="<?php echo htmlspecialchars($inst['step']); ?>" placeholder="وصف الخطوة" required>
              <button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="ingredient-row">
            <label style="min-width:60px;">الخطوة 1:</label>
            <input type="text" name="step[]" placeholder="وصف الخطوة" required>
            <button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>
          </div>
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-outline" style="margin-top:8px;" onclick="addStep()">+ إضافة خطوة</button>

      <div class="actions" style="margin-top:24px;">
        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
        <a href="my-recipes.php" class="btn btn-outline">إلغاء</a>
      </div>

    </form>
  </div>
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

<script>
  function addIngredient() {
    const container = document.getElementById('ingredientsContainer');
    const row = document.createElement('div');
    row.className = 'ingredient-row';
    row.innerHTML = '<input type="text" name="ing_name[]" placeholder="اسم المكون" required>'
                  + '<input type="text" name="ing_qty[]" placeholder="الكمية" required>'
                  + '<button type="button" class="delete-row-btn" onclick="this.parentElement.remove()">حذف</button>';
    container.appendChild(row);
  }

  function addStep() {
    const container = document.getElementById('stepsContainer');
    const count = container.querySelectorAll('.ingredient-row').length + 1;
    const row = document.createElement('div');
    row.className = 'ingredient-row';
    row.innerHTML = '<label style="min-width:60px;">الخطوة ' + count + ':</label>'
                  + '<input type="text" name="step[]" placeholder="وصف الخطوة" required>'
                  + '<button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>';
    container.appendChild(row);
  }

  function removeStep(btn) {
    btn.parentElement.remove();
    const rows = document.querySelectorAll('#stepsContainer .ingredient-row');
    rows.forEach((row, i) => {
      const lbl = row.querySelector('label');
      if (lbl) lbl.textContent = 'الخطوة ' + (i + 1) + ':';
    });
  }
</script>

</body>
</html>