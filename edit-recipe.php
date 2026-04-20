<?php
// edit-recipe.php
// Requirement: Edit recipe page - checks recipe ID, retrieves existing recipe data,
//              displays it in the form with recipe ID as a hidden input.

session_start();
require 'dp.php';

// Requirement: Only logged-in regular users can edit recipes
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Requirement: Check the recipe ID sent in the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my-recipes.php");
    exit();
}

$recipeId = (int) $_GET['id'];

// Retrieve the recipe and confirm it belongs to this user
$stmt = $pdo->prepare("SELECT * FROM recipe WHERE id = ? AND userID = ?");
$stmt->execute([$recipeId, $userId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: my-recipes.php");
    exit();
}

// Retrieve categories for the drop-down
$categories = $pdo->query("SELECT id, categoryName FROM recipecategory ORDER BY categoryName")->fetchAll(PDO::FETCH_ASSOC);

// Retrieve existing ingredients for this recipe
$ingStmt = $pdo->prepare("SELECT ingredientName, ingredientQuantity FROM ingredients WHERE recipeID = ?");
$ingStmt->execute([$recipeId]);
$ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve existing instructions ordered by step number
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
      <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="form-container">
      <h1>تعديل الوصفة</h1>

      <?php if ($error !== ''): ?>
        <p style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <!-- Requirement: Form submits to edit_recipe_action.php which updates the DB -->
      <form id="editForm" action="edit_recipe_action.php" method="POST" enctype="multipart/form-data">

        <!-- Requirement: Recipe ID included as hidden input -->
        <input type="hidden" name="recipeID" value="<?= $recipeId ?>">

        <div class="form-group">
          <label>اسم الوصفة:</label>
          <input type="text" name="name" value="<?= htmlspecialchars($recipe['name']) ?>" required>
        </div>

        <div class="form-group">
          <label>التصنيف:</label>
          <select name="categoryID" required>
            <option value="">اختر التصنيف</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"
                <?= ($cat['id'] == $recipe['categoryID']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['categoryName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>الوصف:</label>
          <textarea name="description" required><?= htmlspecialchars($recipe['description']) ?></textarea>
        </div>

        <!-- Requirement: Replace old photo with new one if uploaded, otherwise keep old -->
        <div class="form-group">
          <label>صورة الوصفة الحالية:</label>
          <img src="images/<?= htmlspecialchars($recipe['photoFileName']) ?>"
               alt="current photo"
               style="max-width:150px; display:block; margin-bottom:8px;"
               onerror="this.src='images/default.png'">
          <label>رفع صورة جديدة (اختياري - اتركه فارغاً للإبقاء على الحالية):</label>
          <input type="file" name="recipePhoto" accept="image/*">
        </div>

        <!-- Requirement: Replace old video with new one if uploaded, otherwise keep old -->
        <div class="form-group">
          <label>الفيديو الحالي:
            <?php if (!empty($recipe['videoFilePath'])): ?>
              <a href="videos/<?= htmlspecialchars($recipe['videoFilePath']) ?>" target="_blank">مشاهدة</a>
            <?php else: ?>
              لا يوجد
            <?php endif; ?>
          </label>
          <label>رفع فيديو جديد (اختياري - اتركه فارغاً للإبقاء على الحالي):</label>
          <input type="file" name="recipeVideo" accept="video/*">
        </div>

        <!-- Ingredients section pre-filled from database -->
        <div class="section-title" style="margin-top:20px;">المكونات:</div>
        <div id="ingredientsContainer">
          <?php if (!empty($ingredients)): ?>
            <?php foreach ($ingredients as $i => $ing): ?>
              <div class="ingredient-row">
                <label>المكون <?= ($i + 1) ?>:</label>
                <input type="text" name="ing_name[]" value="<?= htmlspecialchars($ing['ingredientName']) ?>" placeholder="اسم المكون" required>
                <input type="text" name="ing_qty[]"  value="<?= htmlspecialchars($ing['ingredientQuantity']) ?>" placeholder="الكمية" required>
                <button type="button" class="delete-row-btn" onclick="removeIngredient(this)">حذف</button>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="ingredient-row">
              <label>المكون 1:</label>
              <input type="text" name="ing_name[]" placeholder="اسم المكون" required>
              <input type="text" name="ing_qty[]"  placeholder="الكمية" required>
              <button type="button" class="delete-row-btn" onclick="removeIngredient(this)">حذف</button>
            </div>
          <?php endif; ?>
        </div>
        <button type="button" class="action-btn add-btn" onclick="addIngredientRow()">+ إضافة مكون آخر</button>

        <!-- Instructions section pre-filled from database -->
        <div class="section-title" style="margin-top:20px;">طريقة التحضير:</div>
        <div id="stepsContainer">
          <?php if (!empty($instructions)): ?>
            <?php foreach ($instructions as $i => $inst): ?>
              <div class="step-row">
                <label>الخطوة <?= ($i + 1) ?>:</label>
                <input type="text" name="step[]" value="<?= htmlspecialchars($inst['step']) ?>" placeholder="وصف الخطوة" required>
                <button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="step-row">
              <label>الخطوة 1:</label>
              <input type="text" name="step[]" placeholder="وصف الخطوة" required>
              <button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>
            </div>
          <?php endif; ?>
        </div>
        <button type="button" class="action-btn add-btn" onclick="addStepRow()">+ إضافة خطوة أخرى</button>

        <div class="actions" style="margin-top:24px;">
          <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
          <a href="my-recipes.php" class="btn btn-outline">إلغاء</a>
        </div>

      </form>
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

  <script>
    function renumberIngredients() {
      document.querySelectorAll('#ingredientsContainer .ingredient-row').forEach((row, i) => {
        const lbl = row.querySelector('label');
        if (lbl) lbl.textContent = 'المكون ' + (i + 1) + ':';
      });
    }

    function renumberSteps() {
      document.querySelectorAll('#stepsContainer .step-row').forEach((row, i) => {
        const lbl = row.querySelector('label');
        if (lbl) lbl.textContent = 'الخطوة ' + (i + 1) + ':';
      });
    }

    function addIngredientRow() {
      const div = document.createElement('div');
      div.className = 'ingredient-row';
      div.innerHTML = '<label>المكون:</label>'
        + '<input type="text" name="ing_name[]" placeholder="اسم المكون" required>'
        + '<input type="text" name="ing_qty[]" placeholder="الكمية" required>'
        + '<button type="button" class="delete-row-btn" onclick="removeIngredient(this)">حذف</button>';
      document.getElementById('ingredientsContainer').appendChild(div);
      renumberIngredients();
    }

    function removeIngredient(btn) {
      btn.parentElement.remove();
      renumberIngredients();
    }

    function addStepRow() {
      const div = document.createElement('div');
      div.className = 'step-row';
      div.innerHTML = '<label>الخطوة:</label>'
        + '<input type="text" name="step[]" placeholder="وصف الخطوة" required>'
        + '<button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>';
      document.getElementById('stepsContainer').appendChild(div);
      renumberSteps();
    }

    function removeStep(btn) {
      btn.parentElement.remove();
      renumberSteps();
    }

    // Preview new photo before upload
    document.getElementById('photoUpload').addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('photoPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  </script>

</body>
</html>
