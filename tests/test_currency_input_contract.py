from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
LIBRARY = ROOT / "web/assets/js/currency-input.js"
TRANSACTION_FORM = APP / "views/admin/finance/transaction_form.php"


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

    def test_library_keeps_canonical_values_for_the_server(self):
        source = read(LIBRARY)
        self.assertIn("parseValue", source)
        self.assertIn("formatValue", source)
        self.assertIn("submit.currencyInput", source)
        self.assertIn("$field.val(raw)", source)
        self.assertIn("MAX_FRACTION_DIGITS = 6", source)
        self.assertIn("MAX_INTEGER_DIGITS = 14", source)

    def test_library_round_trips_locale_separators(self):
        source = read(LIBRARY)
        self.assertIn("localeSeparators", source)
        self.assertIn("preferDecimal", source)
        self.assertIn("editableValue", source)
        self.assertIn("storedRaw", source)

    def test_amount_input_uses_currency_input(self):
        source = read(TRANSACTION_FORM)
        self.assertIn('name="amount"', source)
        self.assertIn("data-currency-input", source)
        self.assertIn('data-decimal="2"', source)
        self.assertIn('data-currency-default="0"', source)
        self.assertIn("currency-input.js", source)
        self.assertNotIn('name="amount" type="number"', source)

    def test_server_still_validates_canonical_decimal_format(self):
        controller = read(APP / "controllers/Admin_finance.php")
        self.assertIn(r"/^\d{1,14}(?:\.\d{1,6})?$/", controller)


if __name__ == "__main__":
    unittest.main()
