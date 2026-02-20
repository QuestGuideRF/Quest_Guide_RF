document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initUserMenu();
    initLazyLoading();
});
function initMobileMenu() {
    const toggler = document.getElementById('navbar-toggler');
    const menu = document.getElementById('navbar-menu');
    if (!toggler || !menu) {
        return;
    }
    console.log('Mobile menu initialized');
    toggler.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('Toggler clicked');
        menu.classList.toggle('active');
        const spans = toggler.querySelectorAll('span');
        if (menu.classList.contains('active')) {
            console.log('Menu opened');
            spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
        } else {
            console.log('Menu closed');
            spans[0].style.transform = '';
            spans[1].style.opacity = '';
            spans[2].style.transform = '';
        }
    });
    document.addEventListener('click', (e) => {
        if (!toggler.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('active');
            const spans = toggler.querySelectorAll('span');
            spans[0].style.transform = '';
            spans[1].style.opacity = '';
            spans[2].style.transform = '';
        }
    });
}
function initUserMenu() {
    const toggle = document.getElementById('user-menu-toggle');
    const dropdown = document.getElementById('user-menu-dropdown');
    if (!toggle || !dropdown) return;
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('active');
    });
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
}
function initLazyLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        images.forEach(img => imageObserver.observe(img));
    }
}
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
function formatDate(dateString, options = {}) {
    const date = new Date(dateString);
    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        ...options
    };
    return date.toLocaleDateString('ru-RU', defaultOptions);
}
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Скопировано в буфер обмена', 'success');
    } catch (err) {
        console.error('Failed to copy:', err);
        showNotification('Ошибка копирования', 'error');
    }
}
function isMobile() {
    return window.matchMedia('(max-width: 767px)').matches;
}
function scrollToElement(selector, offset = 0) {
    const element = document.querySelector(selector);
    if (!element) return;
    const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
    window.scrollTo({
        top: top,
        behavior: 'smooth'
    });
}