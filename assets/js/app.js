// Minimal client-side helper for responsive enhancements.
const menu = document.querySelector('.topbar nav');
if (menu) {
    menu.addEventListener('click', (event) => {
        if (event.target.matches('[data-confirm]')) {
            const message = event.target.getAttribute('data-confirm');
            if (!confirm(message)) {
                event.preventDefault();
            }
        }
    });
}
