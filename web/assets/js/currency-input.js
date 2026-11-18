/*
 * Currency input formatting for admin Finance forms.
 *
 * The server stores and validates decimals in a canonical format: Latin digits,
 * a dot decimal separator, no grouping separators (e.g. 1250.500). This plugin
 * keeps the field readable for the active admin locale while always submitting
 * the canonical value.
 *
 * Usage:
 *   <input name="amount" type="text" inputmode="decimal"
 *          data-currency-input data-decimal="2" data-currency-default="0">
 *
 *   $('[data-currency-input]').currencyInput();          // initialize
 *   $input.currencyInput('set', '1250.500');             // set canonical value
 *
 * Attributes:
 *   data-decimal           Minimum fraction digits shown on blur (0-6, default 0).
 *                          Extra decimals entered by the user (up to 6) are kept.
 *   data-max-decimal       Maximum fraction digits accepted (0-6, default 6).
 *   data-currency-default  Value used when the field is empty (default "0";
 *                          use data-currency-default="" to allow an empty value).
 *   data-allow-negative    "1" allows a leading minus sign (default disallowed).
 */
(function (window, document) {
    'use strict';

    var $ = window.jQuery;
    if (!$) {
        return;
    }

    var MAX_INTEGER_DIGITS = 14;
    var MAX_FRACTION_DIGITS = 6;

    var FALLBACK_SEPARATORS = {
        en: { group: ',', decimal: '.' },
        id: { group: '.', decimal: ',' },
        de: { group: '.', decimal: ',' },
        es: { group: '.', decimal: ',' },
        fr: { group: '\u202f', decimal: ',' },
        it: { group: '.', decimal: ',' },
        pt: { group: '.', decimal: ',' },
        nl: { group: '.', decimal: ',' },
        ar: { group: '\u066c', decimal: '\u066b' },
        zh: { group: ',', decimal: '.' },
        ja: { group: ',', decimal: '.' },
        ko: { group: ',', decimal: '.' }
    };

    function localeSeparators() {
        var locale = (document.documentElement.getAttribute('lang') || 'en').trim() || 'en';
        var short = locale.toLowerCase().split('-')[0];
        var fallback = FALLBACK_SEPARATORS[short] || FALLBACK_SEPARATORS.en;

        if (!window.Intl || typeof window.Intl.NumberFormat !== 'function') {
            return fallback;
        }

        try {
            var parts = new window.Intl.NumberFormat(locale, {
                numberingSystem: 'latn',
                minimumFractionDigits: 1,
                maximumFractionDigits: 1
            }).formatToParts(1234.5);
            var resolved = { group: '', decimal: '' };
            parts.forEach(function (part) {
                if (part.type === 'group') {
                    resolved.group = part.value;
                } else if (part.type === 'decimal') {
                    resolved.decimal = part.value;
                }
            });
            if (resolved.group && resolved.decimal) {
                return resolved;
            }
        } catch (error) {
            // Fall through to the static separator map.
        }

        return fallback;
    }

    function latinDigits(value) {
        return String(value == null ? '' : value)
            .replace(/[\u0660-\u0669]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x0660);
            })
            .replace(/[\u06f0-\u06f9]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x06f0);
            });
    }

    /**
     * Convert text into the canonical server representation.
     *
     * separators === null means the value is already canonical (Latin digits,
     * dot decimal, no grouping), which is what the server renders. Otherwise
     * the active locale's separators are honored:
     *   - preferDecimal = false: the text is a localized, grouped display value
     *     such as "1.250,500", so grouping is stripped first.
     *   - preferDecimal = true: the text is being typed in the editable field
     *     ("1250,500"), so the locale decimal separator takes priority and the
     *     other punctuation is treated as a grouping character.
     */
    function parseValue(value, allowNegative, separators, preferDecimal, maxFraction) {
        var text = latinDigits(value)
            .replace(/\u066b/g, '.')
            .replace(/\u066c/g, ',')
            .replace(/[^0-9.,-]/g, '');
        var negative = text.charAt(0) === '-';
        text = text.replace(/-/g, '');

        if (!/[0-9]/.test(text)) {
            return '';
        }

        var decimalIndex = -1;

        if (separators && preferDecimal) {
            if (separators.decimal === ',') {
                decimalIndex = text.lastIndexOf(',');
                if (decimalIndex === -1) {
                    decimalIndex = text.lastIndexOf('.');
                }
            } else {
                decimalIndex = text.lastIndexOf('.');
                if (decimalIndex === -1) {
                    decimalIndex = text.lastIndexOf(',');
                }
            }
        } else if (separators) {
            if (separators.group) {
                text = text.split(separators.group).join('');
            }
            if (separators.decimal && separators.decimal !== '.') {
                text = text.split(separators.decimal).join('.');
            }
            decimalIndex = text.lastIndexOf('.');
            if (decimalIndex === -1) {
                decimalIndex = text.lastIndexOf(',');
            }
        } else {
            decimalIndex = Math.max(text.lastIndexOf('.'), text.lastIndexOf(','));
        }

        var integer;
        var fraction;

        if (decimalIndex === -1) {
            integer = text;
            fraction = '';
        } else {
            integer = text.slice(0, decimalIndex).replace(/[.,]/g, '');
            var fractionLimit = typeof maxFraction === 'number' ? maxFraction : MAX_FRACTION_DIGITS;
            fraction = text.slice(decimalIndex + 1).replace(/[.,]/g, '').slice(0, fractionLimit);
        }

        integer = integer.replace(/^0+(?=\d)/, '').slice(0, MAX_INTEGER_DIGITS);
        if (integer === '') {
            integer = '0';
        }

        var raw = fraction === '' ? integer : integer + '.' + fraction;
        if (negative && allowNegative && /[1-9]/.test(raw)) {
            raw = '-' + raw;
        }
        return raw;
    }

    function hasNonZero(raw) {
        return /[1-9]/.test(raw);
    }

    function formatValue(raw, decimals, separators) {
        if (raw === '') {
            return '';
        }

        var negative = raw.charAt(0) === '-';
        if (negative) {
            raw = raw.slice(1);
        }

        var pieces = raw.split('.');
        var integer = (pieces[0] || '0').replace(/^0+(?=\d)/, '');
        var fraction = pieces[1] || '';

        while (fraction.length < decimals) {
            fraction += '0';
        }

        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, function () {
            return separators.group;
        });

        var output = integer;
        if (fraction.length > 0) {
            output += separators.decimal + fraction;
        }
        return (negative ? '-' : '') + output;
    }

    function editableValue(raw, separators) {
        if (raw === '') {
            return '';
        }
        return raw.replace('.', separators.decimal);
    }

    function storeRaw($input, raw) {
        $input.data('currencyRaw', raw);
    }

    function storedRaw($input, options) {
        var raw = String($input.data('currencyRaw') == null ? '' : $input.data('currencyRaw'));
        if (raw === '') {
            raw = options.defaultRaw;
        }
        return raw;
    }

    function setValue($input, value, options) {
        var raw = parseValue(value, options.allowNegative, null, false, options.maxFraction);
        if (raw === '') {
            raw = options.defaultRaw;
        }
        storeRaw($input, raw);
        $input.val(raw === '' ? '' : formatValue(raw, options.decimals, options.separators));
    }

    function initInput(element) {
        var $input = $(element);
        var existing = $input.data('currencyInputOptions');
        if (existing) {
            return existing;
        }

        var decimals = parseInt($input.attr('data-decimal'), 10);
        if (isNaN(decimals) || decimals < 0) {
            decimals = 0;
        }
        decimals = Math.min(decimals, MAX_FRACTION_DIGITS);

        var maxFraction = parseInt($input.attr('data-max-decimal'), 10);
        if (isNaN(maxFraction) || maxFraction < 0) {
            maxFraction = MAX_FRACTION_DIGITS;
        }
        maxFraction = Math.min(maxFraction, MAX_FRACTION_DIGITS);

        var defaultAttr = $input.attr('data-currency-default');
        var allowNegative = $input.attr('data-allow-negative') === '1';
        var options = {
            decimals: decimals,
            maxFraction: maxFraction,
            allowNegative: allowNegative,
            defaultRaw: typeof defaultAttr === 'undefined'
                ? '0'
                : parseValue(defaultAttr, allowNegative, null, false, maxFraction),
            separators: localeSeparators()
        };
        $input.data('currencyInputOptions', options);
        setValue($input, $input.val(), options);

        $input.on('focus.currencyInput', function () {
            var raw = storedRaw($input, options);
            storeRaw($input, raw);
            $input.val(editableValue(raw, options.separators));

            if (raw === '' || !hasNonZero(raw)) {
                window.setTimeout(function () {
                    try {
                        element.select();
                    } catch (error) {
                        // Selection is a progressive enhancement only.
                    }
                }, 0);
            }
        });

        $input.on('input.currencyInput', function () {
            storeRaw($input, parseValue($input.val(), options.allowNegative, options.separators, true, options.maxFraction));
        });

        $input.on('blur.currencyInput', function () {
            var raw = storedRaw($input, options);
            storeRaw($input, raw);
            $input.val(raw === '' ? '' : formatValue(raw, options.decimals, options.separators));
        });

        var $form = $input.closest('form');
        if ($form.length && !$form.data('currencyInputSubmitBound')) {
            $form.data('currencyInputSubmitBound', true);
            $form.on('submit.currencyInput', function () {
                $form.find('[data-currency-input]').each(function () {
                    var $field = $(this);
                    var fieldOptions = $field.data('currencyInputOptions');
                    if (!fieldOptions) {
                        return;
                    }
                    var raw = storedRaw($field, fieldOptions);
                    storeRaw($field, raw);
                    $field.val(raw);
                });
            });
        }

        return options;
    }

    $.fn.currencyInput = function (command, value) {
        if (typeof command === 'string') {
            return this.each(function () {
                var $input = $(this);
                var options = initInput(this);
                if (options && command === 'set') {
                    setValue($input, value, options);
                }
            });
        }

        return this.each(function () {
            initInput(this);
        });
    };

    $(function () {
        $('[data-currency-input]').currencyInput();
    });
})(window, document);
