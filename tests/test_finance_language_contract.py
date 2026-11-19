from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
LANGUAGES = APP / "language"
LOCALES = (
    "english", "indonesian", "french", "german", "spanish",
    "chinese", "japanese", "korean", "arabic",
)
FINANCE_CONTROLLER = APP / "controllers/Admin_finance.php"
FINANCE_INDEX = APP / "views/admin/finance/resource_index.php"
FINANCE_FORM = APP / "views/admin/finance/resource_form.php"
TRANSACTION_INDEX = APP / "views/admin/finance/transaction_index.php"
TRANSACTION_FORM = APP / "views/admin/finance/transaction_form.php"

# Keys resolved dynamically with a prefix inside transaction_form.php.
TRANSACTION_ERROR_KEYS = {
    "cash_account_id_required", "account_id_required", "transaction_on_invalid",
    "amount_invalid", "exchange_rate_invalid", "description_too_long",
    "transaction_unavailable", "cash_account_invalid", "account_invalid",
    "category_invalid", "department_invalid", "tax_invalid",
    "not_found", "not_editable", "save_error",
}
# Keys resolved dynamically with a prefix inside transaction_index.php.
TRANSACTION_STATUS_KEYS = {
    "draft_saved", "posted", "period_closed", "period_ambiguous",
    "tax_account_missing", "already_posted", "account_invalid",
    "not_postable", "unavailable", "post_error", "unknown",
}


def read(path):
    return path.read_text(encoding="utf-8")


def language_keys(path):
    return set(re.findall(r"\$lang\['([a-z0-9_]+)'\]", read(path)))


def used_localization_keys(source):
    keys = set(re.findall(r"'(?:label_key|hint_key)'\s*=>\s*'([a-z0-9_]+)'", source))
    for block in re.findall(r"'option_label_keys'\s*=>\s*array\((.*?)\)", source, re.DOTALL):
        keys.update(re.findall(r"=>\s*'([a-z0-9_]+)'", block))
    return keys


class FinanceLanguageContractTests(unittest.TestCase):
    def test_every_locale_has_a_finance_language_pack(self):
        for locale in LOCALES:
            with self.subTest(locale=locale):
                path = LANGUAGES / locale / "finance_lang.php"
                self.assertTrue(path.is_file(), f"missing {path}")
                self.assertIn("defined('BASEPATH')", read(path))

    def test_finance_language_packs_share_the_same_key_set(self):
        key_sets = {
            locale: language_keys(LANGUAGES / locale / "finance_lang.php")
            for locale in LOCALES
        }
        reference = key_sets["english"]
        self.assertTrue(reference, "English finance pack must not be empty")
        for locale, keys in key_sets.items():
            with self.subTest(locale=locale):
                self.assertEqual(reference, keys)

    def test_financial_settings_localization_keys_exist_in_every_locale(self):
        used = used_localization_keys(read(FINANCE_CONTROLLER))
        expected = {
            "finance_column_base_currency",
            "finance_field_base_currency",
            "finance_field_base_currency_hint",
            "finance_field_receivable_account_id",
            "finance_field_receivable_account_id_hint",
            "finance_field_payable_account_id",
            "finance_field_payable_account_id_hint",
            "finance_field_fx_gain_account_id",
            "finance_field_fx_gain_account_id_hint",
            "finance_field_fx_loss_account_id",
            "finance_field_fx_loss_account_id_hint",
        }
        self.assertTrue(expected.issubset(used), expected - used)
        for locale in LOCALES:
            keys = language_keys(LANGUAGES / locale / "finance_lang.php")
            with self.subTest(locale=locale):
                self.assertTrue(used.issubset(keys), used - keys)

    def test_finance_views_resolve_localized_labels_and_hints(self):
        index_view = read(FINANCE_INDEX)
        form_view = read(FINANCE_FORM)

        self.assertIn("$column['label_key']", index_view)
        self.assertIn("isset($labels[$column_label_key])", index_view)

        self.assertIn("$field['label_key']", form_view)
        self.assertIn("$field['hint_key']", form_view)
        self.assertIn("$field['option_label_keys']", form_view)

    def test_income_expense_views_use_finance_tx_keys_in_every_locale(self):
        source = read(TRANSACTION_INDEX) + read(TRANSACTION_FORM)
        used = {
            key for key in re.findall(r"finance_tx_[a-z0-9_]+", source)
            if not key.endswith("_")
        }
        used.update("finance_tx_error_" + name for name in TRANSACTION_ERROR_KEYS)
        used.update("finance_tx_status_" + name for name in TRANSACTION_STATUS_KEYS)

        self.assertIn("$labels['finance_tx_intro']", read(TRANSACTION_INDEX))
        self.assertIn("'finance_tx_error_'", read(TRANSACTION_FORM))
        self.assertIn("'finance_tx_status_'", read(TRANSACTION_INDEX))

        for locale in LOCALES:
            keys = language_keys(LANGUAGES / locale / "finance_lang.php")
            with self.subTest(locale=locale):
                self.assertTrue(used.issubset(keys), used - keys)

    def test_document_index_keys_exist_in_every_locale(self):
        source = read(APP / "views/admin/finance/document_index.php")
        used = set(re.findall(r"finance_(?:doc_[a-z0-9_]+|new_draft|action_failed|actions|column_[a-z_]+|edit|post|dt_[a-z0-9_]+)", source))
        for locale in LOCALES:
            keys = language_keys(LANGUAGES / locale / "finance_lang.php")
            with self.subTest(locale=locale):
                self.assertTrue(used.issubset(keys), used - keys)

    def test_all_literal_view_keys_exist_in_every_locale(self):
        views = APP / "views/admin/finance"
        used = set()
        for path in views.glob("*.php"):
            source = read(path)
            used.update(re.findall(r"\$labels\[['\"](finance_[a-z0-9_]+)['\"]\]", source))
            used.update(re.findall(r"\$labels\[['\"](finance_[a-z0-9_]+)['\"]\s*\?", source))
        for locale in LOCALES:
            keys = language_keys(LANGUAGES / locale / "finance_lang.php")
            with self.subTest(locale=locale):
                self.assertTrue(used.issubset(keys), used - keys)

    def test_resource_metadata_keys_exist_in_every_locale(self):
        source = read(FINANCE_CONTROLLER)
        resource_fields = set(re.findall(r"'name'\s*=>\s*'([a-z0-9_]+)'", source[source.index("private function finance_resources()"):]))
        expected = {
            "finance_resource_field_" + name for name in resource_fields
        } | {
            "finance_resource_column_" + name for name in resource_fields
        }
        expected.update(
            "finance_resource_option_" + field + "_" + option
            for field, options in {
                "active_flag": ("0", "1"),
                "account_type": ("asset", "liability", "equity", "revenue", "expense"),
                "normal_balance": ("debit", "credit"),
                "category_type": ("income", "expense"),
                "account_kind": ("cash", "bank"),
                "status": ("open", "closed"),
            }.items()
            for option in options
        )
        for locale in LOCALES:
            keys = language_keys(LANGUAGES / locale / "finance_lang.php")
            with self.subTest(locale=locale):
                self.assertTrue(expected.issubset(keys), expected - keys)


if __name__ == "__main__":
    unittest.main()
