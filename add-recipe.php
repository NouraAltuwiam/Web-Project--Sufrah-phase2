<?php
// add-recipe.php
// Requirement: Add recipe page - loads categories from DB and shows the form.

session_start();
require 'dp.php';

// Requirement: Only logged-in regular users can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

// Requirement: Retrieve categories from database for the drop-down menu
$categories = $pdo->query("SELECT id, categoryName FROM recipecategory ORDER BY categoryName")->fetchAll(PDO::FETCH_ASSOC);

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سُفرة | إضافة وصفة</title>
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
    </div>
  </header>

  <div class="form-container">
    <h1>إضافة وصفة جديدة</h1>

    <?php if ($error !== ''): ?>
      <p style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Requirement: Form submits to add_recipe_action.php which inserts recipe into DB -->
    <form id="recipeForm" action="add_recipe_action.php" method="POST" enctype="multipart/form-data">

      <div class="form-group">
        <label>اسم الوصفة:</label>
        <input type="text" name="name" required>
      </div>

      <!-- Requirement: Category menu retrieved from database -->
      <div class="form-group">
        <label>التصنيف:</label>
        <select name="categoryID" required>
          <option value="">اختر</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>">
              <?= htmlspecialchars($cat['categoryName']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>الوصف:</label>
        <textarea name="description" required></textarea>
      </div>

      <!-- Recipe type selector (regular / healthy) -->
      <div class="recipe-type-section">
        <label class="section-label">نوع الوصفة</label>
        <div class="recipe-type-buttons">
          <button type="button" class="recipe-type-btn active" data-type="regular" onclick="selectRecipeType('regular')">
            <span class="type-icon">🔥</span>
            <span>عادي</span>
          </button>
          <button type="button" class="recipe-type-btn" data-type="healthy" onclick="selectRecipeType('healthy')">
            <span class="type-icon">🌿</span>
            <span>صحي</span>
          </button>
        </div>
        <input type="hidden" id="recipeType" name="recipeType" value="regular">
      </div>

      <div class="form-group">
        <label>رفع صورة الوصفة:</label>
        <input type="file" name="recipePhoto" accept="image/*" required>
      </div>

      <!-- Ingredients section -->
      <div class="section-title">المكونات:</div>
      <div id="ingredients">
        <div class="ingredient-row">
          <label>المكون 1:</label>
          <input type="text" name="ing_name[]" placeholder="الاسم" required>
          <input type="text" name="ing_qty[]"  placeholder="الكمية" required>
          <button type="button" class="delete-row-btn" onclick="removeRow(this, 'ingredient')">حذف</button>
        </div>
      </div>
      <button type="button" class="action-btn add-btn" onclick="addIngredient()">+ إضافة مكون آخر</button>

      <!-- Healthy alternatives section (optional) -->
      <div class="healthy-alternatives-section">
        <div class="healthy-header">
          <div class="healthy-title">
            <span class="healthy-icon">🌿</span>
            <span>بدائل صحية (اختياري)</span>
          </div>
          <button type="button" class="action-btn add-btn" onclick="addHealthyAlternative()">+ إضافة بديل</button>
        </div>
        <p class="healthy-description">أضف بدائل صحية للمكونات لجعل وصفتك أخف</p>
        <div id="healthyAlternatives"></div>
      </div>

      <!-- Instructions section -->
      <div class="section-title">طريقة التحضير:</div>
      <div id="steps">
        <div class="step-row">
          <label>الخطوة 1:</label>
          <input type="text" name="step[]" required>
          <button type="button" class="delete-row-btn" onclick="removeRow(this, 'step')">حذف</button>
        </div>
      </div>
      <button type="button" class="action-btn add-btn" onclick="addStep()">+ إضافة خطوة أخرى</button>

      <!-- Video section (optional) -->
      <div class="section-title">فيديو (اختياري):</div>
      <div class="form-group">
        <label>رفع فيديو:</label>
        <input type="file" name="recipeVideo" accept="video/*">
      </div>

      <button type="submit" class="submit-btn">إضافة الوصفة</button>
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

  <script>
    // Switch between regular and healthy recipe type
    function selectRecipeType(type) {
      document.querySelectorAll('.recipe-type-btn').forEach(btn => btn.classList.remove('active'));
      document.querySelector('.recipe-type-btn[data-type="' + type + '"]').classList.add('active');
      document.getElementById('recipeType').value = type;
    }

    // Re-number ingredient rows after add/remove
    function renumberIngredients() {
      document.querySelectorAll('#ingredients .ingredient-row').forEach((row, i) => {
        row.querySelector('label').textContent = 'المكون ' + (i + 1) + ':';
      });
    }

    // Re-number step rows after add/remove
    function renumberSteps() {
      document.querySelectorAll('#steps .step-row').forEach((row, i) => {
        row.querySelector('label').textContent = 'الخطوة ' + (i + 1) + ':';
      });
    }

    // Re-number healthy alternative rows after add/remove
    function renumberAlternatives() {
      document.querySelectorAll('#healthyAlternatives .alternative-row').forEach((row, i) => {
        row.querySelector('label').textContent = 'بديل ' + (i + 1) + ':';
      });
    }

    function addIngredient() {
      const div = document.createElement('div');
      div.className = 'ingredient-row';
      div.innerHTML = '<label>المكون:</label>'
        + '<input type="text" name="ing_name[]" placeholder="الاسم" required>'
        + '<input type="text" name="ing_qty[]" placeholder="الكمية" required>'
        + '<button type="button" class="delete-row-btn" onclick="removeRow(this, \'ingredient\')">حذف</button>';
      document.getElementById('ingredients').appendChild(div);
      renumberIngredients();
    }

    function addHealthyAlternative() {
      const div = document.createElement('div');
      div.className = 'alternative-row';
      div.innerHTML = '<label>بديل:</label>'
        + '<input type="text" name="alt_original[]" placeholder="المكون الأصلي" class="alternative-input">'
        + '<span class="arrow-icon">←</span>'
        + '<input type="text" name="alt_healthy[]" placeholder="البديل الصحي" class="alternative-input">'
        + '<button type="button" class="delete-row-btn" onclick="removeRow(this, \'alternative\')">حذف</button>';
      document.getElementById('healthyAlternatives').appendChild(div);
      renumberAlternatives();
    }

    function addStep() {
      const div = document.createElement('div');
      div.className = 'step-row';
      div.innerHTML = '<label>الخطوة:</label>'
        + '<input type="text" name="step[]" required>'
        + '<button type="button" class="delete-row-btn" onclick="removeRow(this, \'step\')">حذف</button>';
      document.getElementById('steps').appendChild(div);
      renumberSteps();
    }

    function removeRow(btn, type) {
      btn.parentElement.remove();
      if (type === 'ingredient')   renumberIngredients();
      else if (type === 'step')    renumberSteps();
      else if (type === 'alternative') renumberAlternatives();
    }
  </script>

</body>
</html>
