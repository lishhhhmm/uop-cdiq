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
    const track = document.querySelector('.header-logo-track');
    if (!track) return;

    const logoItems = Array.from(track.querySelectorAll('.header-logo-item'));
    if (logoItems.length === 0) return;

    const mobileQuery = window.matchMedia('(max-width: 1024px)');
    const clonedClass = 'is-clone';

    function ensureClones() {
        if (track.querySelector(`.${clonedClass}`)) return;

        logoItems.forEach(item => {
            const clone = item.cloneNode(true);
            clone.classList.add(clonedClass);
            clone.setAttribute('aria-hidden', 'true');
            track.appendChild(clone);
        });
    }

    function setLoopWidth() {
        const visibleItems = Array.from(track.querySelectorAll('.header-logo-item:not(.is-clone)'));
        const gap = parseFloat(window.getComputedStyle(track).columnGap || window.getComputedStyle(track).gap || '0');
        const loopWidth = visibleItems.reduce((total, item) => total + item.getBoundingClientRect().width, 0)
            + Math.max(visibleItems.length - 1, 0) * gap;

        track.style.setProperty('--header-logo-loop-width', `${loopWidth}px`);
        const duration = Math.max(loopWidth / 28, 6);
        track.style.animationDuration = `${duration}s`;
    }

    function sync() {
        if (!mobileQuery.matches) {
            track.classList.remove('is-animated');
            track.style.removeProperty('--header-logo-loop-width');
            track.style.removeProperty('animation-duration');
            return;
        }

        ensureClones();
        window.requestAnimationFrame(() => {
            setLoopWidth();
            track.classList.add('is-animated');
        });
    }

    sync();
    mobileQuery.addEventListener('change', sync);
    window.addEventListener('resize', sync);
}

document.addEventListener('DOMContentLoaded', () => {
    initHamburger();
    initActiveLink();
    initMobileLogoCarousel();
});
