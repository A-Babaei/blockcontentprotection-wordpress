document.addEventListener('DOMContentLoaded', function () {
    initCustomMessageToggle();
    initPageSelectionModeToggle();
    initMetaBoxToggle();
    initPostSelectors();
});

function initCustomMessageToggle() {
    const messageToggle = document.getElementById('enable_custom_messages');
    if (!messageToggle) return;

    const toggleRow = messageToggle.closest('tr');
    if (!toggleRow) return;

    // The fields to toggle are the next two rows in the table
    const field1 = toggleRow.nextElementSibling;
    const field2 = field1 ? field1.nextElementSibling : null;

    const toggleVisibility = () => {
        [field1, field2].forEach(field => {
            if (field) field.classList.toggle('bcp-hidden', !messageToggle.checked);
        });
    };

    toggleVisibility();
    messageToggle.addEventListener('change', toggleVisibility);
}

function initPageSelectionModeToggle() {
    const modeSelect = document.getElementById('page_selection_mode');
    if (!modeSelect) return;

    // Rendered immediately after the mode field: excluded_pages, then included_pages.
    const modeRow = modeSelect.closest('tr');
    const excludedRow = modeRow ? modeRow.nextElementSibling : null;
    const includedRow = excludedRow ? excludedRow.nextElementSibling : null;

    const toggleRows = () => {
        const isSelected = modeSelect.value === 'selected';
        if (excludedRow) excludedRow.classList.toggle('bcp-hidden', isSelected);
        if (includedRow) includedRow.classList.toggle('bcp-hidden', !isSelected);
    };

    toggleRows();
    modeSelect.addEventListener('change', toggleRows);
}

function initMetaBoxToggle() {
    const radios = document.querySelectorAll('input[name="bcp_override"]');
    const box = document.querySelector('.bcp-meta-content-types');
    if (!radios.length || !box) return;

    const update = () => {
        const checked = document.querySelector('input[name="bcp_override"]:checked');
        box.classList.toggle('bcp-hidden', !checked || checked.value !== 'enable');
    };

    radios.forEach(radio => radio.addEventListener('change', update));
    update();
}

function initPostSelectors() {
    const containers = document.querySelectorAll('.bcp-post-selector');
    if (!containers.length || typeof bcpAdmin === 'undefined') return;

    containers.forEach(container => {
        const fieldName = container.dataset.fieldName;
        const chipsWrap = container.querySelector('.bcp-post-selector-chips');
        const input = container.querySelector('.bcp-post-selector-input');
        const resultsWrap = container.querySelector('.bcp-post-selector-results');
        let debounceTimer = null;
        let activeRequest = null;

        const existingIds = () => Array.from(chipsWrap.querySelectorAll('.bcp-chip')).map(chip => chip.dataset.id);

        const closeResults = () => {
            resultsWrap.innerHTML = '';
            resultsWrap.classList.remove('is-open');
        };

        const addChip = (id, title) => {
            id = String(id);
            if (existingIds().includes(id)) return;

            const chip = document.createElement('span');
            chip.className = 'bcp-chip';
            chip.dataset.id = id;

            const label = document.createTextNode(title + ' ');
            chip.appendChild(label);

            const remove = document.createElement('a');
            remove.href = '#';
            remove.className = 'bcp-chip-remove';
            remove.setAttribute('aria-label', 'Remove');
            remove.innerHTML = '&times;';
            chip.appendChild(remove);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = fieldName;
            hidden.value = id;
            chip.appendChild(hidden);

            chipsWrap.appendChild(chip);
        };

        chipsWrap.addEventListener('click', e => {
            if (e.target.classList.contains('bcp-chip-remove')) {
                e.preventDefault();
                e.target.closest('.bcp-chip')?.remove();
            }
        });

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const term = input.value.trim();
            if (term.length < 2) {
                closeResults();
                return;
            }
            debounceTimer = setTimeout(() => {
                activeRequest?.abort?.();
                const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
                activeRequest = controller;

                const url = bcpAdmin.ajaxUrl
                    + '?action=bcp_search_posts'
                    + '&nonce=' + encodeURIComponent(bcpAdmin.nonce)
                    + '&s=' + encodeURIComponent(term);

                fetch(url, { credentials: 'same-origin', signal: controller?.signal })
                    .then(r => r.json())
                    .then(res => {
                        resultsWrap.innerHTML = '';
                        if (!res.success || !res.data || !res.data.length) {
                            closeResults();
                            return;
                        }
                        res.data.forEach(item => {
                            const row = document.createElement('div');
                            row.className = 'bcp-post-selector-result';
                            row.textContent = item.title + ' (' + item.type + ')';
                            row.addEventListener('click', () => {
                                addChip(item.id, item.title);
                                input.value = '';
                                closeResults();
                                input.focus();
                            });
                            resultsWrap.appendChild(row);
                        });
                        resultsWrap.classList.add('is-open');
                    })
                    .catch(() => {});
            }, 300);
        });

        document.addEventListener('click', e => {
            if (!container.contains(e.target)) {
                closeResults();
            }
        });
    });
}
