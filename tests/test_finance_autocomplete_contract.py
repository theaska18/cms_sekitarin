from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
FINANCE_VIEWS = APP / "views/admin/finance"

LOOKUP_VIEWS = (
    "transaction_form.php",
    "document_form.php",
    "payment_form.php",
    "transfer_form.php",
    "journal_form.php",
    "reconciliation_form.php",
    "resource_form.php",
)

LOOKUP_STORES = (
    "Finance_transaction_store.php",
    "Finance_document_store.php",
    "Finance_journal_store.php",
    "Finance_payment_store.php",
    "Finance_transfer_store.php",
    "Finance_reconciliation_store.php",
)


def read(path):
    return path.read_text(encoding="utf-8")


def compact(source):
    return re.sub(r"\s+", "", source)


class FinanceAutocompleteContractTests(unittest.TestCase):
    def test_lookup_views_search_on_focus_without_minimum_characters(self):
        for name in LOOKUP_VIEWS:
            with self.subTest(view=name):
                source = compact(read(FINANCE_VIEWS / name))
                self.assertIn("minLength:0", source)
                self.assertIn("on('focus'", source)
                self.assertIn("autocomplete('search','')", source)

    def test_lookup_views_show_a_trailing_search_icon(self):
        for name in LOOKUP_VIEWS:
            with self.subTest(view=name):
                source = read(FINANCE_VIEWS / name)
                self.assertIn("m16 16 4.2 4.2", source)
                self.assertIn("pointer-events-none absolute end-", source)
                self.assertIn("aria-hidden=\"true\"", source)

    def test_minimum_character_hint_text_is_gone(self):
        for name in LOOKUP_VIEWS:
            with self.subTest(view=name):
                source = read(FINANCE_VIEWS / name).lower()
                self.assertNotIn("at least 2 characters", source)
                self.assertNotIn("minimal 2 karakter", source)

    def test_lookup_stores_accept_an_empty_term(self):
        for name in LOOKUP_STORES:
            with self.subTest(store=name):
                source = compact(read(APP / "libraries" / name))
                self.assertNotIn("term_length<2", source)
                self.assertNotIn("strlen($term)<2", source)
                self.assertIn("if($term!=='')", source)
                self.assertIn("limit(20)", source)


if __name__ == "__main__":
    unittest.main()
