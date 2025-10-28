// This file is distributed under the same license as the luxed-core package.
// Copyright (C) 2025 modulux
document.addEventListener('DOMContentLoaded', () => {
    // Tabs
    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.tab-content');

    // Helper to activate tab by href/id
    function activateTab(tabId) {
        tabs.forEach(t => t.classList.remove('nav-tab-active'));
        contents.forEach(c => c.classList.remove('active'));

        const tab = Array.from(tabs).find(t => t.getAttribute('href') === tabId);
        const content = document.querySelector(tabId);

        if (tab) tab.classList.add('nav-tab-active');
        if (content) content.classList.add('active');
    }

    // On click, update hash and activate tab
    tabs.forEach(tab => {
        tab.addEventListener('click', e => {
            e.preventDefault();
            const tabId = tab.getAttribute('href');
            activateTab(tabId);
            history.replaceState(null, '', tabId); // update URL hash without scrolling
        });
    });

    // On page load, check hash and activate tab
    const initialHash = window.location.hash;
    if (initialHash && document.querySelector(initialHash)) {
        activateTab(initialHash);
    } else if (tabs.length) {
        // Default to first tab if no hash
        const firstTabId = tabs[0].getAttribute('href');
        activateTab(firstTabId);
    }

    // Weight unit repeater
    const unitAddBtn = document.getElementById('add-weight-unit');
    const unitTable = document.querySelector('#modulux-weight-unit-table tbody');

    if (unitAddBtn && unitTable) {
        unitAddBtn.addEventListener('click', () => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>
                                Unit #${unitTable.rows.length + 1}:
                                <input type="text" name="modulux_weight_units[]" value="" />
                                <button type="button" class="button remove-unit">×</button>
                            </td>`;
            unitTable.appendChild(row);
        });

        unitTable.addEventListener('click', (e) => {
            // Check if the clicked element is a remove button            
            if (e.target.classList.contains('remove-unit')) {
                e.preventDefault();
                e.stopPropagation();
                const msg = window.modulux_i18n && window.modulux_i18n.remove_unit_confirm
                    ? window.modulux_i18n.remove_unit_confirm
                    : 'Are you sure you want to remove this unit? This action cannot be undone after you Save Settings, and removing a unit will remove it from all products that use it.';
                if (confirm(msg)) {
                    e.target.closest('tr').remove();
                }
            }           
        });
    }

    // Shipping rule repeater
    document.querySelectorAll('.add-shipping-rule').forEach(btn => {
        btn.addEventListener('click', () => {
            const unit = btn.dataset.unit;
            const table = document.querySelector(`.modulux-shipping-unit-table[data-unit="${unit}"] tbody`);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="number" step="0.01" name="modulux_shipping_rules[${unit}][max][]" value="" /></td>
                <td><input type="number" step="0.01" name="modulux_shipping_rules[${unit}][price][]" value="" /></td>
                <td><button type="button" class="button remove-shipping-rule">×</button></td>
            `;
            table.appendChild(row);
        });
    });

    document.querySelectorAll('.modulux-shipping-unit-table').forEach(table => {
        table.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-shipping-rule')) {
                e.preventDefault();
                e.stopPropagation();
                const msg = window.modulux_i18n && window.modulux_i18n.remove_unit_confirm
                    ? window.modulux_i18n.remove_unit_confirm
                    : 'Are you sure you want to remove this unit? This action cannot be undone after you Save Settings.';
                if (confirm(msg)) {
                    e.target.closest('tr').remove();
                }
            }
        });
    });
});