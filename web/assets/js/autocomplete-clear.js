/*
 * Shared enhancement for jQuery UI autocomplete lookups.
 *
 * Once a lookup value is selected the visible input becomes read-only. A small
 * clear button appears before the search icon; clearing restores editability
 * and empties the linked hidden field.
 */
(function (window, document) {
    'use strict';

    if (!window.jQuery) return;
    var $ = window.jQuery;

    function hiddenTarget(input) {
        var $input = $(input);
        var selectors = [
            $input.attr('data-hidden-target'),
            $input.attr('data-target'),
            $input.attr('data-account-target'),
            $input.attr('data-fiscal-year-target')
        ];
        for (var i = 0; i < selectors.length; i++) {
            if (selectors[i]) {
                var found = document.getElementById(selectors[i]);
                if (!found) {
                    try { found = document.querySelector(selectors[i]); } catch (error) { found = null; }
                }
                if (found) return $(found);
            }
        }

        var payment = $input.attr('data-payment-lookup');
        if (payment === 'document') return $('#document-id');
        if (payment === 'cash_account') return $('#cash-account-id');

        var cashLookup = $input.attr('data-cash-lookup');
        if (cashLookup) return $('#' + cashLookup + '-id');

        if ($input.is('[data-cash-account]')) return $('#cash-account-id');

        var row = $input.closest('tr');
        if (row.length) {
            var rowHidden = row.find('input[type="hidden"]').first();
            if (rowHidden.length) return rowHidden;
        }
        var siblingHidden = $input.parent().siblings('input[type="hidden"]').first();
        if (siblingHidden.length) return siblingHidden;
        var nearbyHidden = $input.closest('div').find('input[type="hidden"]').first();
        if (nearbyHidden.length) return nearbyHidden;

        var id = $input.attr('id') || '';
        if (/^cash-search$/.test(id)) return $('#cash-account-id');
        if (/-search$/.test(id)) return $('#' + id.replace(/-search$/, '-id'));
        return $();
    }

    function clearButton(input) {
        var $input = $(input);
        var wrapper = $input.parent();
        if (!wrapper.length) return $();
        if (wrapper.css('position') === 'static') wrapper.css('position', 'relative');
        var button = wrapper.children('[data-autocomplete-clear]').first();
        if (button.length) return button;
        button = $('<button type="button" data-autocomplete-clear="1"></button>')
            .attr('aria-label', $input.attr('data-autocomplete-clear-label') || 'Clear selection')
            .attr('title', $input.attr('data-autocomplete-clear-label') || 'Clear selection')
            .css({
                position: 'absolute',
                insetInlineEnd: '2.4rem',
                top: '50%',
                transform: 'translateY(-50%)',
                display: 'inline-flex',
                width: '1.25rem',
                height: '1.25rem',
                alignItems: 'center',
                justifyContent: 'center',
                border: '0',
                borderRadius: '9999px',
                background: 'transparent',
                color: '#64748b',
                cursor: 'pointer',
                zIndex: 2
            })
            .html('<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="width:1rem;height:1rem"><path stroke-linecap="round" d="m5 5 10 10M15 5 5 15"/></svg>');
        wrapper.append(button);
        return button;
    }

    function setSelected(input, selected) {
        var $input = $(input);
        var button = clearButton(input);
        if (!$input.data('autocomplete-clear-original-padding')) {
            $input.data('autocomplete-clear-original-padding', $input[0].style.paddingInlineEnd || '');
        }
        if (selected) {
            $input.prop('readonly', true).attr('aria-readonly', 'true');
            $input.css('paddingInlineEnd', '4.5rem');
            if (button.length) button.show();
        } else {
            $input.prop('readonly', false).removeAttr('aria-readonly');
            $input.css('paddingInlineEnd', $input.data('autocomplete-clear-original-padding') || '');
            if (button.length) button.hide();
        }
    }

    function isSelected(input) {
        var $input = $(input);
        if ($input.data('autocomplete-clear-selected') === true) return true;
        var target = hiddenTarget(input);
        return String($input.val() || '').trim() !== '' && (!target.length || String(target.val() || '').trim() !== '');
    }

    function mark(input) {
        window.setTimeout(function () {
            var $input = $(input);
            if (isSelected(input)) {
                $input.data('autocomplete-clear-selected', true);
                setSelected(input, true);
            } else {
                $input.data('autocomplete-clear-selected', false);
                setSelected(input, false);
            }
        }, 0);
    }

    $(document)
        .on('autocompleteselect', 'input[autocomplete="off"]', function () {
            mark(this);
        })
        .on('autocompletechange', 'input[autocomplete="off"]', function () {
            mark(this);
        })
        .on('click', '[data-autocomplete-clear]', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var button = $(this);
            var input = button.siblings('input[autocomplete="off"]').first();
            if (!input.length) input = button.parent().find('input[autocomplete="off"]').first();
            if (!input.length) return;
            input.val('');
            hiddenTarget(input[0]).val('');
            input.data('autocomplete-clear-selected', false);
            setSelected(input[0], false);
            input.trigger('input').trigger('change').focus();
            if (input.data('ui-autocomplete')) {
                input.autocomplete('search', '');
            }
        });

    function initialize() {
        $('input[autocomplete="off"]').each(function () {
            if (!$(this).data('ui-autocomplete')) return;
            if (isSelected(this)) setSelected(this, true);
            else setSelected(this, false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initialize();
            window.setTimeout(initialize, 100);
        });
    } else {
        initialize();
        window.setTimeout(initialize, 100);
    }
})(window, document);
