// ===== أنيميشن السكرول =====
// يشغّل الأنيميشن لما العنصر يظهر بالشاشة

// دالة لفحص إذا العنصر ظاهر بالشاشة
function isElementInViewport(el) {
  const rect = el.getBoundingClientRect();
  const windowHeight = window.innerHeight || document.documentElement.clientHeight;
  
  // العنصر يعتبر ظاهر إذا 20% منه داخل الشاشة
  return (
    rect.top <= windowHeight * 0.8 &&
    rect.bottom >= 0
  );
}

// دالة تضيف كلاس visible للعناصر الظاهرة
function checkVisibility() {
  // العناصر اللي نبي نحركها
  const headers = document.querySelectorAll('.section-header');
  const cards = document.querySelectorAll('.reel-card');
  const button = document.querySelector('.view-all-btn');
  
  // أجزاء الخريطة منفصلة
  const mapTitle = document.querySelector('.map-section .section-title');
  const mapDescription = document.querySelector('.map-section .section-description');
  const mapContainer = document.querySelector('.map-container');
  const mapPoints = document.querySelectorAll('.map-point');
  
  // فحص العناوين
  headers.forEach(element => {
    if (isElementInViewport(element)) {
      element.classList.add('visible');
    }
  });
  
  // فحص الكروت
  cards.forEach(element => {
    if (isElementInViewport(element)) {
      element.classList.add('visible');
    }
  });
  
  // فحص عنوان الخريطة
  if (mapTitle && isElementInViewport(mapTitle)) {
    mapTitle.classList.add('visible');
    
    // بعد ما العنوان يظهر، نظهر الوصف
    setTimeout(() => {
      if (mapDescription) {
        mapDescription.classList.add('visible');
      }
      
      // بعد ما الوصف يظهر، نظهر الخريطة
      setTimeout(() => {
        if (mapContainer) {
          mapContainer.classList.add('visible');
          
          // بعد ما الخريطة تظهر، نظهر النقاط واحدة واحدة
          setTimeout(() => {
            mapPoints.forEach(point => {
              point.classList.add('visible');
            });
          }, 600);
        }
      }, 300);
    }, 300);
  }
  
  // فحص الزر
  if (button && isElementInViewport(button)) {
    button.classList.add('visible');
  }
}

// تشغيل الفحص عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
  checkVisibility();
});

// تشغيل الفحص عند السكرول
let scrollTimeout;
window.addEventListener('scroll', function() {
  if (scrollTimeout) {
    clearTimeout(scrollTimeout);
  }
  scrollTimeout = setTimeout(checkVisibility, 50);
});

// تشغيل الفحص عند تغيير حجم الشاشة
window.addEventListener('resize', checkVisibility);

// تشغيل مرة أولى بعد ثانية
setTimeout(checkVisibility, 100);