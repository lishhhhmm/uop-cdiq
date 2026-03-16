function initHamburger() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    if (!hamburger || !navLinks) return;

    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('open');
    });

    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navLinks.classList.remove('open');
        });
    });
}

function initActiveLink() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.php'; // Defaulting to index for root
    document.querySelectorAll('.nav-links a').forEach(link => {
        const href = link.getAttribute('href');
        // Simple check if the link ends with the current path
        if (href && (href === currentPath || href.endsWith('/' + currentPath))) {
            link.classList.add('active');
        }
    });

}

function initMobileLogoCarousel() {
    const logoItems = Array.from(document.querySelectorAll('.header-logo-item'));
    if (logoItems.length <= 2) return;

    let activeIndex = 0;
    let intervalId = null;
    const mobileQuery = window.matchMedia('(max-width: 1024px)');

    function render() {
        logoItems.forEach((item, index) => {
            const isVisible = index === activeIndex || index === (activeIndex + 1) % logoItems.length;
            item.classList.toggle('is-visible', isVisible);
        });
    }

    function stop() {
        if (intervalId) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    }

    function start() {
        stop();
        if (!mobileQuery.matches) {
            logoItems.forEach(item => item.classList.remove('is-visible'));
            return;
        }

        render();
        intervalId = window.setInterval(() => {
            activeIndex = (activeIndex + 1) % logoItems.length;
            render();
        }, 2200);
    }

    start();
    mobileQuery.addEventListener('change', start);
}

document.addEventListener('DOMContentLoaded', () => {
    initHamburger();
    initActiveLink();
    initMobileLogoCarousel();
});
