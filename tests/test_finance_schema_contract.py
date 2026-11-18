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
        self.assertEqual(26, len(tables))
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

    def test_finance_export_and_import_access_codes_are_explicit(self):
        report_manifest = (ROOT / "web/application/views/admin/menu_manifest/finance_report.xml").read_text()
        accounting_manifest = (ROOT / "web/application/views/admin/menu_manifest/finance_accounting.xml").read_text()
        master_manifest = (ROOT / "web/application/views/admin/menu_manifest/finance_master.xml").read_text()
        controller = FINANCE_CONTROLLER.read_text()
        for code in ("fin_profit_loss", "fin_balance_sheet", "fin_cash_flow", "fin_trial_balance", "fin_ar_report", "fin_ap_report"):
            with self.subTest(report=code):
                self.assertIn(f'access code="{code}_export" name="Export"', report_manifest)
                self.assertNotIn(f'access code="{code}_add"', report_manifest)
                self.assertNotIn(f'access code="{code}_update"', report_manifest)
        self.assertIn('access code="fin_general_ledger_export" name="Export"', accounting_manifest)
        self.assertNotIn('access code="fin_general_ledger_update"', accounting_manifest)
        for code in ("fin_chart_of_accounts", "fin_categories", "fin_tax_configuration"):
            with self.subTest(master=code):
                self.assertIn(f'access code="{code}_export" name="Export"', accounting_manifest + master_manifest)
                self.assertIn(f'access code="{code}_import" name="Import"', accounting_manifest + master_manifest)
                self.assertIn(f"has_admin_access_code('{code}_export')", controller)
                self.assertIn(f"has_admin_access_code('{code}_import')", controller)
        self.assertIn("$menu_code . '_export'", controller)
        self.assertIn("finance_report_can_export", (APP / "views/admin/finance/report_index.php").read_text())
        self.assertIn("finance_chart_of_accounts_can_export", (APP / "views/admin/finance/chart_of_accounts_index.php").read_text())
        self.assertIn("finance_can_import", (APP / "views/admin/finance/resource_index.php").read_text())

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
            "fin_financial_settings",
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

    def test_finance_document_payment_and_reconciliation_approval_support(self):
        migration = (APP / "migrations/20261117000700_link_finance_documents_to_approval.php").read_text()
        approval = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = FINANCE_CONTROLLER.read_text()
        document = (APP / "libraries/Finance_document_store.php").read_text()
        payment = (APP / "libraries/Finance_payment_store.php").read_text()
        journal = (APP / "libraries/Finance_journal_store.php").read_text()
        reconciliation = (APP / "libraries/Finance_reconciliation_store.php").read_text()
        for table in ("fin_sales_invoice", "fin_purchase_bill", "fin_customer_payment", "fin_supplier_payment", "fin_bank_reconciliation"):
            with self.subTest(table=table):
                self.assertIn("'" + table + "'", migration)
        for source in ("fin_sales_invoice", "fin_purchase_bill", "fin_customer_payment", "fin_supplier_payment", "fin_bank_reconciliation"):
            with self.subTest(source=source):
                self.assertIn("'" + source + "' => array('table'", approval)
        for method in ("finance_manual_journal_start_approval", "finance_document_start_approval", "finance_payment_start_approval", "finance_reconciliation_start_approval"):
            with self.subTest(method=method):
                self.assertIn("private function " + method, controller)
        self.assertIn("sales_invoice_code", document)
        self.assertIn("purchase_bill_code", document)
        self.assertIn("customer_payment_code", payment)
        self.assertIn("supplier_payment_code", payment)
        self.assertIn("journal_manual", journal)
        self.assertIn("bank_reconciliation_code", reconciliation)
        self.assertIn("$required_status = 'approved';", journal)
        self.assertIn("if ($payment['status'] !== 'approved')", payment)
        self.assertIn("$doc['status'] !== 'approved'", document)
        self.assertIn("$row['status']!=='approved'", reconciliation)

    def test_finance_currency_lists_use_grouped_amount_formatting(self):
        views = (
            "views/admin/finance/document_index.php",
            "views/admin/finance/payment_index.php",
            "views/admin/finance/reconciliation_index.php",
            "views/admin/finance/report_index.php",
            "views/admin/finance/transaction_index.php",
            "views/admin/finance/transfer_index.php",
            "views/admin/finance/journal_index.php",
        )
        for path in views:
            with self.subTest(view=path):
                content = (APP / path).read_text()
                self.assertNotIn("toFixed(2)", content)
        for path in (
            "views/admin/finance/document_index.php",
            "views/admin/finance/payment_index.php",
            "views/admin/finance/reconciliation_index.php",
            "views/admin/finance/report_index.php",
        ):
            with self.subTest(shared=path):
                self.assertIn("finance-list.js", (APP / path).read_text())

    def test_all_finance_list_tables_are_no_wrap(self):
        views = (
            "views/admin/finance/transaction_index.php",
            "views/admin/finance/transfer_index.php",
            "views/admin/finance/journal_index.php",
            "views/admin/finance/document_index.php",
            "views/admin/finance/payment_index.php",
            "views/admin/finance/reconciliation_index.php",
            "views/admin/finance/resource_index.php",
            "views/admin/finance/report_index.php",
            "views/admin/finance/dashboard.php",
        )
        for path in views:
            with self.subTest(view=path):
                content = (APP / path).read_text()
                self.assertIn("finance-list-table", content)
                self.assertIn("whitespace-nowrap", content)
        css = (ROOT / "web/assets/css/admin-theme.css").read_text()
        self.assertIn(".finance-list-table th", css)
        self.assertIn("white-space: nowrap", css)

    def test_document_payment_and_reconciliation_lists_use_three_dot_actions(self):
        for path in (
            "views/admin/finance/document_index.php",
            "views/admin/finance/payment_index.php",
            "views/admin/finance/reconciliation_index.php",
            "views/admin/finance/resource_index.php",
        ):
            with self.subTest(view=path):
                content = (APP / path).read_text()
                self.assertIn("data-finance-menu-toggle", content)
                self.assertIn("data-finance-menu", content)
                self.assertIn('role="menuitem"', content)
                self.assertNotIn('class="me-3 font-semibold text-indigo-700"', content)

    def test_new_finance_forms_default_currency_to_the_base_currency(self):
        controller = FINANCE_CONTROLLER.read_text()
        document_form = controller.split("private function finance_document_form(", 1)[1].split("private function finance_document_values(", 1)[0]
        resource_form = controller.split("private function finance_form(", 1)[1].split("private function finance_form_values(", 1)[0]
        self.assertIn("$record['currency_code'] = strtoupper((string) $base_settings->row()->base_currency);", document_form)
        self.assertIn("$resource['table'] !== 'fin_settings'", resource_form)
        self.assertIn("$record['currency_code'] = strtoupper((string) $base_settings->row()->base_currency);", resource_form)
        label = (APP / "language/indonesian/finance_lang.php").read_text(encoding="utf-8")
        self.assertIn("$lang['finance_tx_field_amount'] = 'Jumlah (sebelum pajak)';", label)
        self.assertIn("$lang['finance_doc_line_amount'] = 'Jumlah sebelum pajak';", label)

    def test_account_id_fields_use_account_autocomplete_lookups(self):
        controller = FINANCE_CONTROLLER.read_text()
        self.assertIn("'label' => 'Tax account', 'type' => 'account_lookup'", controller)
        self.assertIn("'label' => 'Chart of accounts', 'type' => 'account_lookup'", controller)
        self.assertIn("'label' => 'Parent account', 'type' => 'account_lookup'", controller)
        self.assertIn("'tax_configuration', 'cash_accounts', 'bank_accounts', 'chart_of_accounts'", controller)
        form = (APP / "views/admin/finance/resource_form.php").read_text()
        self.assertIn("exclude_id", form)
        self.assertIn("data-account-exclude", form)

    def test_chart_of_accounts_uses_a_tree_table(self):
        controller = FINANCE_CONTROLLER.read_text()
        view = (APP / "views/admin/finance/chart_of_accounts_index.php").read_text()
        self.assertIn("private function finance_chart_of_accounts_index", controller)
        self.assertIn("private function finance_chart_of_accounts_data", controller)
        self.assertIn("parent_account_id", controller)
        self.assertIn("has_children", controller)
        self.assertIn("data-coa-toggle", view)
        self.assertIn("finance-coa-table", view)
        self.assertIn("finance-list-table", view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_chart_of_accounts_tree_intro']", content)
                self.assertIn("$lang['finance_coa_tree_empty']", content)

    def test_group_accounts_are_excluded_from_posting_lookups(self):
        migration = (APP / "migrations/20261117000800_add_account_posting_flag.php").read_text()
        journal = (APP / "libraries/Finance_journal_store.php").read_text()
        transaction = (APP / "libraries/Finance_transaction_store.php").read_text()
        document = (APP / "libraries/Finance_document_store.php").read_text()
        controller = FINANCE_CONTROLLER.read_text()
        view = (APP / "views/admin/finance/chart_of_accounts_index.php").read_text()
        self.assertIn("posting_flag", migration)
        self.assertIn("'posting_flag' => 0", migration)
        self.assertIn("->where('posting_flag', 1)", journal)
        self.assertIn("$this->db->where('posting_flag', 1);", transaction)
        self.assertIn("$this->db->where('posting_flag', 1);", document)
        self.assertIn("(int) $account['posting_flag'] !== 1", journal)
        self.assertIn("(int) $account['posting_flag'] !== 1", transaction)
        self.assertIn("(int) $account['posting_flag'] !== 1", document)
        self.assertIn("$slug === 'chart_of_accounts'", controller)
        self.assertIn("posting_flag", view)

    def test_chart_of_accounts_csv_export_import_validates_duplicates(self):
        controller = FINANCE_CONTROLLER.read_text()
        routes = (APP / "config/routes.php").read_text()
        view = (APP / "views/admin/finance/chart_of_accounts_index.php").read_text()
        self.assertIn("chart_of_accounts/export", routes)
        self.assertIn("chart_of_accounts/import", routes)
        self.assertIn("public function chart_of_accounts_export", controller)
        self.assertIn("public function chart_of_accounts_import", controller)
        self.assertIn("fin_chart_of_accounts_export", controller)
        self.assertIn("fin_chart_of_accounts_import", controller)
        self.assertIn("finance_chart_of_accounts_can_export", view)
        self.assertIn("finance_chart_of_accounts_can_import", view)
        self.assertIn("$conflicts = array_values(array_intersect(array_keys($file_codes), array_keys($existing)))", controller)
        self.assertIn("Import dibatalkan. Kode akun sudah ada di tenant", controller)
        self.assertIn("Siklus parent terdeteksi", controller)
        self.assertIn("Parent ' . $row['parent_code']", controller)
        self.assertIn("fputcsv", controller)
        self.assertIn("finance_coa_import_errors", view)
        self.assertIn("enctype=\"multipart/form-data\"", view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_coa_export']", content)
                self.assertIn("$lang['finance_coa_import']", content)

    def test_category_csv_export_import_validates_composite_duplicates(self):
        controller = FINANCE_CONTROLLER.read_text()
        routes = (APP / "config/routes.php").read_text()
        view = (APP / "views/admin/finance/resource_index.php").read_text()
        self.assertIn("categories/export", routes)
        self.assertIn("categories/import", routes)
        self.assertIn("public function category_export", controller)
        self.assertIn("public function category_import", controller)
        self.assertIn("fin_categories_export", controller)
        self.assertIn("fin_categories_import", controller)
        self.assertIn("finance_can_export", view)
        self.assertIn("finance_can_import", view)
        self.assertIn("$key = $type . '|' . $code", controller)
        self.assertIn("Import dibatalkan. Kategori sudah ada di tenant", controller)
        self.assertIn("category_file", view)
        self.assertIn("finance_category_import_errors", view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_category_export']", content)
                self.assertIn("$lang['finance_category_import']", content)

    def test_tax_csv_export_import_validates_account_existence(self):
        controller = FINANCE_CONTROLLER.read_text()
        routes = (APP / "config/routes.php").read_text()
        view = (APP / "views/admin/finance/resource_index.php").read_text()
        self.assertIn("tax_configuration/export", routes)
        self.assertIn("tax_configuration/import", routes)
        self.assertIn("public function tax_export", controller)
        self.assertIn("public function tax_import", controller)
        self.assertIn("fin_tax_configuration_export", controller)
        self.assertIn("fin_tax_configuration_import", controller)
        self.assertIn("Akun pajak ' . $row['account_code'] . ' tidak ditemukan.", controller)
        self.assertIn("Akun pajak ' . $row['account_code'] . ' harus bertipe aset atau liabilitas.", controller)
        self.assertIn("akun pajak wajib untuk tarif lebih dari 0", controller)
        self.assertIn("tax_file", view)
        self.assertIn("finance_tax_import_errors", view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_tax_export']", content)
                self.assertIn("$lang['finance_tax_import']", content)

    def test_accounting_period_fiscal_year_uses_autocomplete_lookup(self):
        controller = FINANCE_CONTROLLER.read_text()
        view = (APP / "views/admin/finance/resource_form.php").read_text()
        self.assertIn("'fiscal_year_id', 'label' => 'Fiscal year', 'type' => 'fiscal_year_lookup'", controller)
        self.assertIn("fiscal_year_lookup", view)
        self.assertIn("data-fiscal-year-lookup", view)
        self.assertIn("accounting_periods/lookup/fiscal_year", view)
        self.assertIn("$slug === 'accounting_periods' && $kind === 'fiscal_year'", controller)
        self.assertIn("finance_fiscal_year_label", controller)

    def test_finance_reports_export_all_filtered_rows_to_csv(self):
        controller = FINANCE_CONTROLLER.read_text()
        store = (APP / "libraries/Finance_report_store.php").read_text()
        routes = (APP / "config/routes.php").read_text()
        view = (APP / "views/admin/finance/report_index.php").read_text()
        self.assertIn("admin/finance/(:any)/export", routes)
        self.assertIn("public function report_export", controller)
        self.assertIn("public function rows($report", store)
        self.assertIn("fputcsv", controller)
        self.assertIn("'Report Name'", controller)
        self.assertIn("'Prepared By'", controller)
        self.assertIn("$report_code = preg_replace", controller)
        self.assertIn("$as_of_report", controller)
        self.assertIn("$menu_code . '_export'", controller)
        self.assertIn("finance_report_can_export", view)
        self.assertIn("array_merge(array('No'), $column_labels)", controller)
        self.assertIn("finance_report_export", view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_report_export']", content)

    def test_finance_cash_flow_supports_direct_and_indirect_methods(self):
        controller = FINANCE_CONTROLLER.read_text()
        store = (APP / "libraries/Finance_report_store.php").read_text()
        view = (APP / "views/admin/finance/report_cash_flow.php").read_text()
        self.assertIn("private function finance_cash_flow_statement(", controller)
        self.assertIn("public function cash_flow_statement($tenant_id, $start, $end, $method = 'direct')", store)
        self.assertIn("$method === 'indirect'", store)
        self.assertIn("cash_flow_classify", store)
        self.assertIn("working_capital", store)
        self.assertIn("finance_cf_direct", view)
        self.assertIn("finance_cf_indirect", view)
        self.assertIn("finance_cf_operating", view)
        self.assertIn("finance_cf_beginning_cash", view)
        self.assertIn("finance_cf_ending_cash", view)
        self.assertIn("cash_flow_detail", controller)
        self.assertIn("method=direct", view)
        self.assertIn("method=indirect", view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_cf_direct']", content)
                self.assertIn("$lang['finance_cf_indirect']", content)
                self.assertIn("$lang['finance_cf_ending_cash']", content)

    def test_finance_detail_reports_keep_sidebar_menu_active(self):
        header = (APP / "views/admin/header.php").read_text()
        self.assertIn("$admin_detail_aliases", header)
        self.assertIn("'profit_loss_detail' => 'profit_loss'", header)
        self.assertIn("'balance_sheet_skontro' => 'balance_sheet'", header)
        self.assertIn("'cash_flow_detail' => 'cash_flow'", header)
        self.assertIn("$current_admin_path = $active_current_path;", header)

    def test_finance_profit_loss_uses_formatted_statement_with_subtotals(self):
        controller = FINANCE_CONTROLLER.read_text()
        store = (APP / "libraries/Finance_report_store.php").read_text()
        view = (APP / "views/admin/finance/report_profit_loss.php").read_text()
        self.assertIn("private function finance_profit_loss_statement(", controller)
        self.assertIn("public function profit_loss_statement($tenant_id, $start, $end)", store)
        self.assertIn("finance_report_text", controller)
        self.assertIn("gross_profit", store)
        self.assertIn("net_profit", store)
        self.assertIn("finance_pl_total_revenue", view)
        self.assertIn("finance_pl_gross_profit", view)
        self.assertIn("finance_pl_total_expenses", view)
        self.assertIn("finance_pl_net_profit", view)
        self.assertIn("finance_pl_detail", view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_pl_revenue']", content)
                self.assertIn("$lang['finance_pl_net_profit']", content)
                self.assertIn("$lang['finance_profit_loss_detail']", content)

    def test_finance_balance_sheet_skontro_groups_assets_and_passiva(self):
        controller = FINANCE_CONTROLLER.read_text()
        store = (APP / "libraries/Finance_report_store.php").read_text()
        view = (APP / "views/admin/finance/report_balance_sheet_skontro.php").read_text()
        standard_view = (APP / "views/admin/finance/report_index.php").read_text()
        self.assertIn("'balance_sheet_skontro' => 'fin_balance_sheet'", controller)
        self.assertIn("private function finance_balance_sheet_skontro(", controller)
        self.assertIn("public function balance_sheet_skontro($tenant_id, $end)", store)
        self.assertIn("balance_sheet_group", store)
        self.assertIn("current_earnings", store)
        self.assertIn("je.status", store)
        self.assertIn("finance_skontro_aktiva", view)
        self.assertIn("finance_skontro_pasiva", view)
        self.assertIn("finance_skontro_total_liabilities_equity", view)
        self.assertIn("finance_skontro_balanced", view)
        self.assertIn("finance_skontro_open", standard_view)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_balance_sheet_skontro']", content)
                self.assertIn("$lang['finance_skontro_total_assets']", content)

    def test_finance_report_pages_show_report_title_and_summary(self):
        controller = FINANCE_CONTROLLER.read_text()
        store = (APP / "libraries/Finance_report_store.php").read_text()
        view = (APP / "views/admin/finance/report_index.php").read_text()
        self.assertIn("public function summary($report", store)
        self.assertIn("finance_report_summary", controller)
        self.assertIn("finance_report_base_currency", controller)
        self.assertIn("finance_report_balance_totals", controller)
        self.assertIn("finance_skontro_total_assets", view)
        self.assertIn("finance_skontro_total_equity", view)
        self.assertIn("admin_finance_' . $finance_report_slug", view)
        self.assertIn("finance_report_info_title", view)
        self.assertIn("finance_report_rows", view)

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
