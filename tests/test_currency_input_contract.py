from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
LIBRARY = ROOT / "web/assets/js/currency-input.js"
FINANCE = APP / "views/admin/finance"

MONEY_FORMS = {
    "transaction_form.php": ["amount"],
    "document_form.php": ["amount"],
    "payment_form.php": ["amount"],
    "transfer_form.php": ["from_amount", "to_amount"],
    "journal_form.php": ["lines[<?= (int) $index ?>][debit_amount]", "lines[<?= (int) $index ?>][credit_amount]"],
    "reconciliation_form.php": ["opening_balance", "closing_balance", "lines[<?= (int)$i ?>][statement_amount]"],
}


def read(path):
    return path.read_text(encoding="utf-8")


class CurrencyInputContractTests(unittest.TestCase):
    def test_library_is_jquery_based_and_locale_aware(self):
        source = read(LIBRARY)
        self.assertIn("window.jQuery", source)
        self.assertIn("Intl.NumberFormat", source)
        self.assertIn("formatToParts", source)
        self.assertIn("numberingSystem: 'latn'", source)
        self.assertIn("data-currency-input", source)
        self.assertIn("data-decimal", source)
        self.assertIn("data-currency-default", source)
        self.assertIn("data-allow-negative", source)
        self.assertIn("data-max-decimal", source)

    def test_library_keeps_canonical_values_for_the_server(self):
        source = read(LIBRARY)
        self.assertIn("parseValue", source)
        self.assertIn("formatValue", source)
        self.assertIn("submit.currencyInput", source)
        self.assertIn("$field.val(raw)", source)
        self.assertIn("MAX_FRACTION_DIGITS = 2", source)
        self.assertIn("MAX_INTEGER_DIGITS = 14", source)

    def test_library_round_trips_locale_separators(self):
        source = read(LIBRARY)
        self.assertIn("localeSeparators", source)
        self.assertIn("preferDecimal", source)
        self.assertIn("editableValue", source)
        self.assertIn("storedRaw", source)

    def test_library_exposes_idempotent_plugin_api(self):
        source = read(LIBRARY)
        self.assertIn("$.fn.currencyInput", source)
        self.assertIn("command === 'set'", source)
        self.assertIn("setValue($input, value, options)", source)

    def test_every_money_form_loads_the_library_and_uses_it(self):
        for filename, fields in MONEY_FORMS.items():
            source = read(FINANCE / filename)
            self.assertIn("currency-input.js", source, filename)
            for field in fields:
                self.assertIn(field, source, filename)
            self.assertIn("data-currency-input", source, filename)

    def test_money_inputs_use_two_decimals_and_default_zero(self):
        for filename in MONEY_FORMS:
            source = read(FINANCE / filename)
            self.assertIn('data-decimal="2"', source, filename)
            self.assertIn('data-currency-default="0"', source, filename)

    def test_exchange_rates_are_not_currency_formatted(self):
        rate_fields = (
            "name=\"exchange_rate\"",
            "name=\"from_exchange_rate\"",
            "name=\"to_exchange_rate\"",
            "name=\"lines[<?= (int) $index ?>][exchange_rate]\"",
        )
        for filename in MONEY_FORMS:
            source = read(FINANCE / filename)
            for line in source.splitlines():
                if any(field in line for field in rate_fields):
                    if filename == "document_form.php" and 'name="exchange_rate"' in line:
                        self.assertIn('data-currency-input', line, filename)
                        self.assertIn('data-max-decimal="2"', line, filename)
                        continue
                    self.assertNotIn("data-currency-input", line, filename)

    def test_negative_balances_allow_a_minus_sign(self):
        source = read(FINANCE / "reconciliation_form.php")
        for field in ("opening_balance", "closing_balance", "statement_amount"):
            self.assertIn(field, source)
        self.assertGreaterEqual(source.count('data-allow-negative="1"'), 4)

    def test_dynamic_rows_initialize_currency_inputs(self):
        journal = read(FINANCE / "journal_form.php")
        self.assertIn("$(row).find('[data-currency-input]').currencyInput();", journal)
        self.assertIn("data-debit data-currency-input", journal)
        self.assertIn("data-credit data-currency-input", journal)

        reconciliation = read(FINANCE / "reconciliation_form.php")
        self.assertIn("$(row).find('[data-currency-input]').currencyInput();", reconciliation)
        self.assertIn("amount.currencyInput('set',item.item.amount);", reconciliation)

    def test_server_still_validates_canonical_decimal_format(self):
        controller = read(APP / "controllers/Admin_finance.php")
        self.assertIn(r"/^\d{1,14}(?:\.\d{1,2})?$/", controller)
        self.assertIn(r"/^\d{1,10}(?:\.\d{1,2})?$/", controller)
        self.assertNotIn(r"\d{1,6}", controller)
        api = read(APP / "controllers/Finance_api.php")
        self.assertIn(r"/^\d{1,14}(?:\.\d{1,2})?$/", api)
        self.assertIn(r"/^\d{1,3}(?:\.\d{1,2})?$/", api)
        filters = read(APP / "libraries/Finance_filter_panel.php")
        self.assertIn(r"/^-?\d{1,20}(?:\.\d{1,2})?$/", filters)

    def test_non_currency_numeric_inputs_limit_two_decimals(self):
        for filename, fields in {
            "transaction_form.php": ["exchange_rate"],
            "payment_form.php": ["exchange_rate"],
            "transfer_form.php": ["from_exchange_rate", "to_exchange_rate"],
            "journal_form.php": ["exchange_rate"],
        }.items():
            source = read(FINANCE / filename)
            self.assertIn('data-decimal-limit="2"', source, filename)
            for field in fields:
                self.assertIn(field, source, filename)
        resource_form = read(FINANCE / "resource_form.php")
        self.assertIn("data-decimal-limit=\"2\"", resource_form)
        self.assertIn("currency-input.js", resource_form)
        filter_panel = read(FINANCE / "partials/filter_panel.php")
        self.assertIn('data-decimal-limit="2"', filter_panel)
        self.assertIn('step="0.01"', filter_panel)
        filter_script = read(ROOT / "web" / "assets" / "js" / "finance-filters.js")
        self.assertIn("function limitDecimal", filter_script)
        self.assertIn("[data-decimal-limit]", filter_script)
        integration_list = read(FINANCE / "integration_index.php")
        self.assertIn("maximumFractionDigits: 2", integration_list)
        resource_list = read(FINANCE / "resource_index.php")
        self.assertIn("maximumFractionDigits: 2", resource_list)
        controller = read(APP / "controllers/Admin_finance.php")
        self.assertIn("maksimal 2 desimal", controller)
        library = read(LIBRARY)
        self.assertIn("data-decimal-limit", library)
        self.assertIn("limitDecimalField", library)


if __name__ == "__main__":
    unittest.main()
