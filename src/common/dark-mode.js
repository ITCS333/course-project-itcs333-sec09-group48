// src/common/dark-mode.js

// نظام Dark Mode باستخدام Cookies
console.log('🍪 Dark Mode with Cookies');

// دالة لقراءة Cookies
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// دالة لحفظ Cookies
function setCookie(name, value, days = 365) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value}; expires=${date.toUTCString()}; path=/`;
}

// تطبيق Dark Mode
function applyDarkMode() {
    // قراءة من Cookie أولاً، ثم من localStorage
    const cookieDark = getCookie('darkMode');
    const localDark = localStorage.getItem('darkMode');
    
    let isDarkMode;
    
    if (cookieDark !== null) {
        isDarkMode = cookieDark === 'true';
    } else if (localDark !== null) {
        isDarkMode = localDark === 'true';
    } else {
        isDarkMode = false;
    }
    
    // التطبيق
    document.body.classList.toggle('dark-mode', isDarkMode);
    
    const button = document.getElementById('darkModeToggle');
    if (button) {
        button.textContent = isDarkMode ? '☀️ Light Mode' : '🌙 Dark Mode';
    }
    
    console.log('✅ Dark Mode:', isDarkMode);
}

// تغيير Dark Mode
function toggleDarkMode() {
    const isCurrentlyDark = document.body.classList.contains('dark-mode');
    const newMode = !isCurrentlyDark;
    
    // حفظ في كلا المكانين
    setCookie('darkMode', newMode);
    localStorage.setItem('darkMode', newMode);
    
    // تطبيق فوري
    applyDarkMode();
}

// التهيئة
function init() {
    applyDarkMode();
    
    const button = document.getElementById('darkModeToggle');
    if (button) {
        button.addEventListener('click', toggleDarkMode);
    }
    
    // تطبيق إضافي بعد ثانية
    setTimeout(applyDarkMode, 1000);
}

// تشغيل فوري
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// تطبيق مستمر
setInterval(applyDarkMode, 2000);