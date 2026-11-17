from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
FOUNDATION = APP / "migrations/20261116000200_create_finance_foundation.php"
UNIQUES = APP / "migrations/20261116000300_add_finance_unique_constraints.php"
CLEANUP = APP / "migrations/20261116000400_remove_duplicate_finance_indexes.php"
MIGRATION_CONFIG = APP / "config/migration.php"
FINANCE_CONTROLLER = APP / "controllers/Admin_finance.php"
FINANCE_STORE = APP / "libraries/Finance_store.php"
FINANCE_ROUTES = APP / "config/routes.php"


class FinanceSchemaContractTests(unittest.TestCase):
    def test_finance_foundation_is_tenant_scoped_and_idempotent(self):
        source = FOUNDATION.read_text()
        tables = re.findall(r"^\s*'(fin_[a-z_]+)' => array\(", source, re.MULTILINE)
        self.assertEqual(27, len(tables))
        self.assertEqual(len(tables), len(set(tables)))
        self.assertTrue(all(name.startswith("fin_") for name in tables))
        self.assertIn("create_if_missing", source)
        self.assertIn("$this->db->table_exists($table)", source)

        definitions = re.split(r"^\s*'fin_[a-z_]+' => array\(", source, flags=re.MULTILINE)[1:]
        for definition in definitions:
            with self.subTest(definition=definition[:80]):
                self.assertIn("'tenant_id' =>", definition.split("'indexes' =>", 1)[0])

    def test_finance_business_keys_are_unique_and_migrations_preserve_data_on_rollback(self):
        unique_source = UNIQUES.read_text()
        cleanup_source = CLEANUP.read_text()
        self.assertIn("add_unique_if_missing", unique_source)
        self.assertIn("information_schema.statistics", unique_source)
        self.assertIn("DROP INDEX", cleanup_source)
        self.assertIn("down()", FOUNDATION.read_text())
        self.assertIn("Finance data", FOUNDATION.read_text())
        self.assertNotIn("drop_table", FOUNDATION.read_text().lower())
        self.assertIn("$config['migration_enabled'] = FALSE;", MIGRATION_CONFIG.read_text())

    def test_finance_setup_pages_use_tenant_scoped_server_side_data_access(self):
        controller = FINANCE_CONTROLLER.read_text()
        store = FINANCE_STORE.read_text()
        routes = FINANCE_ROUTES.read_text()
        index_view = (APP / "views/admin/finance/resource_index.php").read_text()
        form_view = (APP / "views/admin/finance/resource_form.php").read_text()

        for menu_code in (
            "fin_chart_of_accounts", "fin_customers", "fin_suppliers", "fin_categories",
            "fin_departments", "fin_tax_configuration", "fin_fiscal_year",
            "fin_accounting_periods", "fin_cash_accounts", "fin_bank_accounts",
            "fin_financial_settings", "fin_numbering_config",
        ):
            with self.subTest(menu_code=menu_code):
                self.assertIn(menu_code, controller)

        self.assertIn("$this->has_admin_menu_code($resource['menu_code'])", controller)
        self.assertIn("$this->has_admin_access_code($resource['menu_code'] . '_add')", controller)
        self.assertIn("$this->has_admin_access_code($resource['menu_code'] . '_update')", controller)
        self.assertIn("->where('tenant_id', (int) $tenant_id)", store)
        self.assertIn("serverSide: true", index_view)
        self.assertIn("admin_finance/data/$1", routes)
        self.assertIn("data-normalize=\"code\"", form_view)
        self.assertIn("data-normalize=\"name\"", form_view)

    def test_income_expense_workflow_uses_drafts_balanced_posting_and_tenant_scoped_lookups(self):
        controller = FINANCE_CONTROLLER.read_text()
        transaction_store = (APP / "libraries/Finance_transaction_store.php").read_text()
        form_view = (APP / "views/admin/finance/transaction_form.php").read_text()
        index_view = (APP / "views/admin/finance/transaction_index.php").read_text()
        routes = FINANCE_ROUTES.read_text()

        self.assertIn("$this->db->trans_begin()", transaction_store)
        self.assertIn("'status' => 'posted'", transaction_store)
        self.assertIn("'source_type' => 'transaction'", transaction_store)
        self.assertIn("->where('tenant_id', (int) $tenant_id)", transaction_store)
        self.assertIn("$this->finance_transaction_store->post(", controller)
        self.assertIn("'admin_finance/post/$1/$2'", routes)
        self.assertIn("minLength: 0", form_view)
        self.assertIn("jquery-ui.min.js", form_view)
        self.assertIn("serverSide: true", index_view)

    def test_finance_reports_are_derived_from_posted_journals_and_server_paged(self):
        controller = FINANCE_CONTROLLER.read_text()
        report_store = (APP / "libraries/Finance_report_store.php").read_text()
        report_view = (APP / "views/admin/finance/report_index.php").read_text()
        for code in ("profit_loss", "balance_sheet", "cash_flow", "trial_balance", "ar_report", "ap_report", "general_ledger"):
            with self.subTest(report=code):
                self.assertIn("'" + code + "' =>", controller)
        self.assertIn('je.status="posted"', report_store)
        self.assertIn("serverSide: true", report_view)
        self.assertIn("start_on", report_view)
        self.assertIn("end_on", report_view)

    def test_finance_lists_show_creator_local_time_and_shared_status_design(self):
        transaction_store = (APP / "libraries/Finance_transaction_store.php").read_text()
        transfer_store = (APP / "libraries/Finance_transfer_store.php").read_text()
        transaction_view = (APP / "views/admin/finance/transaction_index.php").read_text()
        transfer_view = (APP / "views/admin/finance/transfer_index.php").read_text()
        shared = (ROOT / "web/assets/js/finance-list.js").read_text()
        self.assertIn("created_by_name", transaction_store)
        self.assertIn("current_approver_name", transaction_store)
        self.assertIn("t.create_on", transaction_store)
        self.assertIn("from_account_name", transfer_store)
        self.assertIn("to_account_name", transfer_store)
        self.assertIn("t.create_on", transfer_store)
        self.assertIn("finance-list.js", transaction_view)
        self.assertIn("finance-list.js", transfer_view)
        self.assertIn("financeLocalTimeHtml", transaction_view)
        self.assertIn("financeFormatAmount", transaction_view)
        self.assertIn("financeFormatDate", transaction_view)
        self.assertIn("transaction_on", transaction_view)
        self.assertIn("finance_transaction_date", transaction_view)
        self.assertIn("finance_created_at", transaction_view)
        self.assertIn("whitespace-nowrap", transaction_view)
        self.assertIn("created_by_name", transaction_view)
        self.assertIn("financeLocalTimeHtml", transfer_view)
        self.assertIn("financeFormatAmount", transfer_view)
        self.assertIn("financeFormatDate", transfer_view)
        self.assertIn("transfer_date", transfer_view)
        self.assertIn("finance_transaction_date", transfer_view)
        self.assertIn("finance_created_at", transfer_view)
        self.assertIn("whitespace-nowrap", transfer_view)
        self.assertIn("from_account_name", transfer_view)
        self.assertIn("to_account_name", transfer_view)
        self.assertIn("current_approver_name", transfer_view)
        self.assertIn("financeFormatLocalTimes", shared)
        self.assertIn("timeZoneName: 'short'", shared)
        self.assertIn("toLocaleString('en-US'", shared)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_transfer_amount']", content)
                self.assertIn("$lang['finance_transfer_from_account']", content)
                self.assertIn("$lang['finance_transfer_to_account']", content)
                self.assertIn("$lang['finance_transaction_date']", content)
                self.assertIn("$lang['finance_created_at']", content)

    def test_zero_rate_tax_configuration_does_not_require_a_tax_account(self):
        transaction = (APP / "libraries/Finance_transaction_store.php").read_text()
        document = (APP / "libraries/Finance_document_store.php").read_text()
        self.assertIn("!empty($line['tax_id']) && (float) $line['tax_amount'] > 0", transaction)
        self.assertIn("!empty($line['tax_id']) && (float) $line['tax_amount'] > 0", document)

    def test_manual_journal_and_opening_balance_drafts_require_balanced_explicit_posting(self):
        controller = FINANCE_CONTROLLER.read_text()
        journal_store = (APP / "libraries/Finance_journal_store.php").read_text()
        journal_form = (APP / "views/admin/finance/journal_form.php").read_text()
        self.assertIn("'manual', 'opening_balance'", journal_store)
        self.assertIn("abs($debits - $credits) > 0.000001", journal_store)
        self.assertIn("'status' => 'posted'", journal_store)
        self.assertIn("$this->finance_journal_form($slug", controller)
        self.assertIn("minLength: 0", journal_form)
        self.assertIn("serverSide: true", (APP / "views/admin/finance/journal_index.php").read_text())

    def test_sales_and_purchase_documents_post_through_configured_control_accounts(self):
        controller = FINANCE_CONTROLLER.read_text()
        document_store = (APP / "libraries/Finance_document_store.php").read_text()
        self.assertIn("'receivable_account_id'", document_store)
        self.assertIn("'payable_account_id'", document_store)
        self.assertIn("'source_type' => $spec['source_type']", document_store)
        self.assertIn("'status' => 'posted'", document_store)
        self.assertIn("$this->finance_document_form($slug", controller)
        self.assertIn("$this->finance_document_post($slug, $id)", controller)

    def test_payments_allocate_to_posted_documents_and_post_fx_differences(self):
        controller = FINANCE_CONTROLLER.read_text()
        payment_store = (APP / "libraries/Finance_payment_store.php").read_text()
        payment_form = (APP / "views/admin/finance/payment_form.php").read_text()
        self.assertIn("'fin_customer_payment_allocation'", payment_store)
        self.assertIn("'fin_supplier_payment_allocation'", payment_store)
        self.assertIn("->where('p.status', 'posted')", payment_store)
        self.assertIn("fx_gain_account_id", payment_store)
        self.assertIn("fx_loss_account_id", payment_store)
        self.assertIn("$this->finance_payment_form($slug", controller)
        self.assertIn("minLength:0", payment_form)

    def test_transfer_requires_base_value_parity_and_all_transactions_uses_multiple_documents(self):
        controller = FINANCE_CONTROLLER.read_text()
        transfer_store = (APP / "libraries/Finance_transfer_store.php").read_text()
        transaction_store = (APP / "libraries/Finance_transaction_store.php").read_text()
        self.assertIn("abs($base_from-$base_to)>0.000001", transfer_store)
        self.assertIn("where('source_type','cash_transfer')", transfer_store)
        self.assertIn("UNION ALL SELECT sales_invoice_id", transaction_store)
        self.assertIn("UNION ALL SELECT customer_payment_id", transaction_store)
        self.assertIn("$this->finance_transfer_form($id)", controller)
        self.assertIn("$this->finance_payment_post($slug, $id)", controller)

    def test_bank_reconciliation_matches_tenant_cash_lines_and_checks_statement_balance(self):
        controller = FINANCE_CONTROLLER.read_text()
        reconciliation_store = (APP / "libraries/Finance_reconciliation_store.php").read_text()
        reconciliation_form = (APP / "views/admin/finance/reconciliation_form.php").read_text()
        self.assertIn("matched_flag", reconciliation_store)
        self.assertIn("closing_balance_mismatch", reconciliation_store)
        self.assertIn("je.status','posted'", reconciliation_store)
        self.assertIn("minLength:0", reconciliation_form)
        self.assertIn("$this->finance_reconciliation_form($id)", controller)


if __name__ == "__main__":
    unittest.main()
