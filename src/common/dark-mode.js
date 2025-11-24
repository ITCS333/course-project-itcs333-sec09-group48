console.log('🍪 Dark Mode with Cookies');

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, days = 365) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value}; expires=${date.toUTCString()}; path=/`;
}

function applyDarkMode() {
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
    
    document.body.classList.toggle('dark-mode', isDarkMode);
    
    const button = document.getElementById('darkModeToggle');
    if (button) {
        button.textContent = isDarkMode ? '☀️ Light Mode' : '🌙 Dark Mode';
    }
    
    console.log('✅ Dark Mode:', isDarkMode);
}

function toggleDarkMode() {
    const isCurrentlyDark = document.body.classList.contains('dark-mode');
    const newMode = !isCurrentlyDark;
    
    setCookie('darkMode', newMode);
    localStorage.setItem('darkMode', newMode);
    
    applyDarkMode();
}

function init() {
    applyDarkMode();
    
    const button = document.getElementById('darkModeToggle');
    if (button) {
        button.addEventListener('click', toggleDarkMode);
    }
    
    setTimeout(applyDarkMode, 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

setInterval(applyDarkMode, 2000);