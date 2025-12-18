// Client-side helpers for confirmations and catalog search UX.
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

// Lightweight search filter for large catalogs
const productFilter = document.querySelector('[data-product-filter]');
const productCards = document.querySelectorAll('[data-product-card]');
if (productFilter && productCards.length) {
    productFilter.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();
        productCards.forEach((card) => {
            const haystack = card.getAttribute('data-searchable') || '';
            card.style.display = haystack.includes(term) ? '' : 'none';
        });
    });
}

// Sync datalist pickers to inputs for offer selection
function attachProductPicker(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('change', () => {
        const option = document.querySelector(`#products-list option[value="${CSS.escape(input.value)}"]`);
        if (option && option.dataset.ean) {
            input.value = option.dataset.ean;
            input.setAttribute('data-label', option.label || option.textContent || '');
        }
    });
}
attachProductPicker('product1');
attachProductPicker('product2');

// Generic table/list filter
document.querySelectorAll('[data-filter-target]').forEach((input) => {
    const selector = input.getAttribute('data-filter-target');
    const fieldSelector = input.getAttribute('data-filter-field');
    const targets = document.querySelectorAll(selector);
    input.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();
        targets.forEach((row) => {
            const haystack = fieldSelector ? (row.querySelector(fieldSelector)?.getAttribute('data-search') || row.getAttribute('data-search') || row.textContent) : (row.getAttribute('data-search') || row.textContent);
            const visible = (haystack || '').toLowerCase().includes(term);
            row.style.display = visible ? '' : 'none';
        });
    });
});
