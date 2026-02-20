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

document.addEventListener('DOMContentLoaded', () => {
    initHamburger();
    initActiveLink();
});
