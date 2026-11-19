/*
 * Shared display helpers for Finance list tables.
 *
 * Amounts stay numeric in the data source; only the display value is grouped.
 * SQL timestamps are treated as UTC and converted to the browser's local zone.
 */
(function (window, document) {
    'use strict';

    function toUtcIso(value) {
        var text = String(value == null ? '' : value).trim();
        if (text === '') {
            return '';
        }
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(text)) {
            return text.replace(' ', 'T') + 'Z';
        }
        return text;
    }

    function formatAmount(value) {
        var amount = Number(value);
        if (!isFinite(amount)) {
            return String(value == null ? '' : value);
        }
        return amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatLocalTimes(root) {
        var scope = root || document;
        var nodes = scope.querySelectorAll('[data-local-datetime]');
        if (!nodes.length || typeof Intl === 'undefined' || typeof Intl.DateTimeFormat !== 'function') {
            return;
        }
        var locale = (document.documentElement.getAttribute('lang') || 'id-ID').trim() || 'id-ID';
        var formatter = new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZoneName: 'short'
        });
        Array.prototype.forEach.call(nodes, function (node) {
            var date = new Date(toUtcIso(node.getAttribute('datetime') || ''));
            if (isNaN(date.getTime())) {
                return;
            }
            node.textContent = formatter.format(date);
            node.setAttribute('title', toUtcIso(node.getAttribute('datetime') || ''));
        });
    }

    function formatDate(value) {
        var text = String(value == null ? '' : value).trim();
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text);
        if (!match) {
            return text;
        }
        var locale = (document.documentElement.getAttribute('lang') || 'id-ID').trim() || 'id-ID';
        var date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        if (isNaN(date.getTime()) || typeof Intl === 'undefined' || typeof Intl.DateTimeFormat !== 'function') {
            return text;
        }
        return new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).format(date);
    }

    function localTimeHtml(value) {
        var iso = toUtcIso(value);
        var fallback = String(value == null ? '' : value);
        return '<time data-local-datetime datetime="' + escapeAttribute(iso) + '">' + escapeHtml(fallback) + '</time>';
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function escapeAttribute(value) {
        return escapeHtml(value);
    }

    window.financeToUtcIso = toUtcIso;
    window.financeFormatAmount = formatAmount;
    window.financeFormatDate = formatDate;
    window.financeFormatLocalTimes = formatLocalTimes;
    window.financeLocalTimeHtml = localTimeHtml;
})(window, document);

/* Reusable three-dot action menu for Finance list tables. */
(function (window, document) {
    'use strict';

    var MENU_SELECTOR = '[data-finance-menu], [data-finance-actions-menu], [data-journal-actions-menu], [data-transfer-actions-menu]';

    function closeMenus() {
        document.querySelectorAll(MENU_SELECTOR).forEach(function (menu) {
            menu.hidden = true;
            var toggle = document.querySelector('[aria-controls="' + menu.id + '"]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            if (menu.__financePortalHome) {
                var home = menu.__financePortalHome;
                if (home.parent && document.contains(home.parent)) {
                    home.parent.insertBefore(menu, home.next);
                } else if (menu.parentNode === document.body) {
                    menu.remove();
                }
                menu.__financePortalHome = null;
            }
        });
    }

    function openMenu(toggle, menu) {
        closeMenus();
        if (!menu.__financePortalHome) {
            menu.__financePortalHome = {
                parent: menu.parentNode,
                next: menu.nextSibling
            };
        }
        if (menu.parentNode !== document.body) {
            document.body.appendChild(menu);
        }
        menu.hidden = false;
        menu.classList.add('finance-action-menu-portal');
        toggle.setAttribute('aria-expanded', 'true');
        var rect = toggle.getBoundingClientRect();
        var size = menu.getBoundingClientRect();
        var top = rect.bottom + 4;
        if (top + size.height > window.innerHeight - 8 && rect.top - size.height - 4 >= 8) {
            top = rect.top - size.height - 4;
        }
        var left = Math.max(8, Math.min(rect.right - size.width, window.innerWidth - size.width - 8));
        menu.style.top = Math.max(8, top) + 'px';
        menu.style.left = left + 'px';
        var first = menu.querySelector('[role="menuitem"]');
        if (first) {
            try { first.focus({ preventScroll: true }); } catch (error) { first.focus(); }
        }
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-finance-menu-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openMenu(toggle, menu);
            else closeMenus();
            return;
        }
        if (event.target.closest('[data-finance-menu] [role="menuitem"]')) {
            closeMenus();
            return;
        }
        if (!event.target.closest('[data-finance-menu]')) closeMenus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeMenus();
    });
    window.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);

    window.financeOpenActionMenu = openMenu;
    window.financeCloseActionMenus = closeMenus;
})(window, document);

/* Approval-backed cancellation actions for Finance list tables. */
(function (window, document) {
    'use strict';
    if (!window.jQuery) return;
    var $ = window.jQuery;

    function configFor(tableId) {
        var path = String(window.location.pathname || '').replace(/\/+$/, '');
        if (tableId === 'finance-transaction-table') {
            if (path.indexOf('/admin/finance/income') === 0) return {source: 'fin_transaction', id: 'transaction_id', menu: 'fin_income'};
            if (path.indexOf('/admin/finance/expenses') === 0) return {source: 'fin_transaction', id: 'transaction_id', menu: 'fin_expenses'};
            return null;
        }
        if (tableId === 'finance-document-table') {
            if (path.indexOf('/admin/finance/sales_invoices') === 0) return {source: 'fin_sales_invoice', id: 'sales_invoice_id', menu: 'fin_sales_invoices'};
            if (path.indexOf('/admin/finance/purchase_bills') === 0) return {source: 'fin_purchase_bill', id: 'purchase_bill_id', menu: 'fin_purchase_bills'};
            return null;
        }
        if (tableId === 'finance-payment-table') {
            if (path.indexOf('/admin/finance/customer_payments') === 0) return {source: 'fin_customer_payment', id: 'customer_payment_id', menu: 'fin_customer_payments'};
            if (path.indexOf('/admin/finance/supplier_payments') === 0) return {source: 'fin_supplier_payment', id: 'supplier_payment_id', menu: 'fin_supplier_payments'};
            return null;
        }
        if (tableId === 'finance-transfer-table') return {source: 'fin_cash_transfer', id: 'cash_transfer_id', menu: 'fin_cash_bank_transfer'};
        if (tableId === 'finance-reconciliation-table') return {source: 'fin_bank_reconciliation', id: 'reconciliation_id', menu: 'fin_bank_reconciliation'};
        if (tableId === 'finance-journal-table') {
            if (path.indexOf('/admin/finance/opening_balances') === 0) return {source: 'fin_journal_entry', id: 'journal_entry_id', menu: 'fin_opening_balances'};
            return {source: 'fin_journal_entry', id: 'journal_entry_id', menu: 'fin_journal_entries'};
        }
        return null;
    }

    function inject(table) {
        var id = table.attr('id');
        var cfg = configFor(id);
        if (!cfg || !$.fn.DataTable || !$.fn.DataTable.isDataTable(table[0])) return;
        var api = table.DataTable();
        api.rows().every(function () {
            var row = this.data();
            var node = this.node();
            if (!row || !node || ['approved', 'posted', 'completed'].indexOf(String(row.status || '')) === -1) return;
            var sourceId = parseInt(row[cfg.id], 10) || 0;
            if (!sourceId) return;
            var actionCell = node.querySelector('td');
            if (!actionCell) return;
            var menu = actionCell.querySelector('[role="menu"]');
            if (menu && menu.querySelector('[data-finance-cancel-open]')) return;
            if (!menu) {
                actionCell.textContent = '';
                var wrap = document.createElement('div');
                wrap.className = 'relative inline-flex';
                var toggleId = 'finance-cancel-menu-' + cfg.source + '-' + sourceId;
                var toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.setAttribute('data-finance-cancel-toggle', '1');
                toggle.setAttribute('aria-haspopup', 'menu');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-controls', toggleId);
                toggle.setAttribute('aria-label', 'Tindakan');
                toggle.className = 'inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2';
                toggle.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg>';
                menu = document.createElement('div');
                menu.id = toggleId;
                menu.setAttribute('data-finance-menu', '1');
                menu.setAttribute('role', 'menu');
                menu.hidden = true;
                menu.className = 'fixed z-[60] mt-1 w-52 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg';
                wrap.appendChild(toggle);
                wrap.appendChild(menu);
                actionCell.appendChild(wrap);
            }
            var button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('role', 'menuitem');
            button.setAttribute('data-finance-cancel-open', '1');
            button.setAttribute('data-source-type', cfg.source);
            button.setAttribute('data-source-id', String(sourceId));
            button.setAttribute('data-menu-code', cfg.menu);
            button.setAttribute('data-cancel-label', String(row.transaction_number || row.invoice_number || row.bill_number || row.payment_number || row.transfer_number || row.reconciliation_number || row.journal_number || ('#' + sourceId)));
            button.className = 'flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-amber-700 hover:bg-amber-50 focus:bg-amber-50 focus:outline-none';
            button.innerHTML = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M6.5 6.5l7 7m0-7-7 7"/></svg>Batalkan';
            menu.appendChild(button);
        });
    }

    function init() {
        $('table[id^="finance-"]').each(function () {
            var table = $(this);
            if (!$.fn.DataTable || !$.fn.DataTable.isDataTable(this)) return;
            table.off('draw.financeCancel').on('draw.financeCancel', function () { inject(table); });
            inject(table);
        });
    }

    $(document).on('init.dt draw.dt', init);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-finance-cancel-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu) {
                if (menu.hidden && window.financeOpenActionMenu) window.financeOpenActionMenu(toggle, menu);
                else if (window.financeCloseActionMenus) window.financeCloseActionMenus();
            }
            return;
        }
        var opener = event.target.closest('[data-finance-cancel-open]');
        if (!opener) return;
        var dialog = document.getElementById('finance-cancel-dialog');
        if (!dialog) return;
        event.preventDefault();
        document.querySelectorAll('[data-finance-menu], [data-finance-actions-menu], [data-transfer-actions-menu], [data-journal-actions-menu]').forEach(function (menu) { menu.hidden = true; });
        dialog.querySelector('[name="source_type"]').value = opener.getAttribute('data-source-type') || '';
        dialog.querySelector('[name="source_id"]').value = opener.getAttribute('data-source-id') || '';
        dialog.querySelector('[name="menu_code"]').value = opener.getAttribute('data-menu-code') || '';
        dialog.querySelector('[name="return_url"]').value = window.location.pathname;
        dialog.querySelector('[data-finance-cancel-label]').textContent = opener.getAttribute('data-cancel-label') || '';
        dialog.showModal();
        var reason = dialog.querySelector('[name="reason"]');
        if (reason) setTimeout(function () { reason.focus(); }, 30);
    });
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-finance-cancel-close]')) {
            var dialog = document.getElementById('finance-cancel-dialog');
            if (dialog && dialog.open) dialog.close();
        }
    });
})(window, document);
