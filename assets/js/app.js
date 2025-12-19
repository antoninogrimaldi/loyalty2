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

const debounce = (fn, delay = 120) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(null, args), delay);
    };
};

// Lightweight search filter for large catalogs
document.querySelectorAll('[data-product-filter]').forEach((productFilter) => {
    const productCards = Array.from(document.querySelectorAll(productFilter.getAttribute('data-product-filter')));
    const productCount = document.querySelector('[data-product-count]');
    const emptyCatalog = document.querySelector('[data-empty-catalog]');
    if (!productCards.length) return;

    const total = productCards.length;
    const updateCount = (visible) => {
        if (productCount) {
            productCount.textContent = `Prodotti trovati: ${visible}/${total}`;
        }
    };
    const applyFilter = debounce((term) => {
        let visible = 0;
        productCards.forEach((card) => {
            const haystack = card.getAttribute('data-searchable') || '';
            const match = haystack.includes(term);
            card.style.display = match ? '' : 'none';
            if (match) visible += 1;
        });
        updateCount(visible);
        if (emptyCatalog) emptyCatalog.style.display = visible ? 'none' : '';
    }, 120);
    productFilter.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();
        applyFilter(term);
    });
    updateCount(total);
});

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
    const runFilter = debounce((term) => {
        targets.forEach((row) => {
            const haystack = fieldSelector ? (row.querySelector(fieldSelector)?.getAttribute('data-search') || row.getAttribute('data-search') || row.textContent) : (row.getAttribute('data-search') || row.textContent);
            const visible = (haystack || '').toLowerCase().includes(term);
            row.style.display = visible ? '' : 'none';
        });
    }, 80);
    input.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();
        runFilter(term);
    });
});
