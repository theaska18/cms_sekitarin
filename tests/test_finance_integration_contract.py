from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
MIGRATION = ROOT / "database/migrations/20261119000200_create_finance_inbox.sql"
CANCEL_MIGRATION = ROOT / "database/migrations/20261119000400_add_finance_inbox_cancelled.sql"
DATES_MIGRATION = ROOT / "database/migrations/20261119000500_add_finance_inbox_dates.sql"
REFERENCE_MIGRATION = ROOT / "database/migrations/20261119000600_add_finance_draft_integration_reference.sql"
SYNC_MIGRATION = ROOT / "database/migrations/20261119000900_sync_finance_inbox_cancelled_status.sql"
REUSE_MIGRATION = ROOT / "database/migrations/20261119001000_allow_finance_inbox_number_reuse.sql"
SCHEMA = ROOT / "database/schema.sql"
DOCS = ROOT / "docs/FINANCE_INTEGRATION.md"
CANCELLATION_STORE = APP / "libraries/Finance_cancellation_store.php"
API = APP / "controllers/Finance_api.php"
ADMIN = APP / "controllers/Admin_finance_integration.php"
ADMIN_CONTROLLER = APP / "controllers/Admin_finance.php"
STORE = APP / "libraries/Finance_integration_store.php"
DOCUMENT_STORE = APP / "libraries/Finance_document_store.php"
LIST_VIEW = APP / "views/admin/finance/integration_index.php"
FORM_VIEW = APP / "views/admin/finance/document_form.php"
DOCUMENT_INDEX = APP / "views/admin/finance/document_index.php"
ROUTES = APP / "config/routes.php"
CONFIG = APP / "config/config.php"
SALES_MENU = APP / "views/admin/menu_manifest/finance_sales.xml"
PURCHASE_MENU = APP / "views/admin/menu_manifest/finance_purchase.xml"
LOCALES = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")


class FinanceIntegrationContractTests(unittest.TestCase):
    def test_inbox_schema_and_access_migration(self):
        migration = MIGRATION.read_text()
        for table in ("fin_sales_invoice_inbox", "fin_purchase_bill_inbox"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE IF NOT EXISTS `" + table + "`", migration)
                self.assertIn("UNIQUE KEY", migration)
                self.assertIn("ON DELETE SET NULL", migration)
        for column in (
            "sales_invoice_id", "purchase_bill_id", "amount_before_tax",
            "tax_percent", "tax_amount", "total_amount", "status",
            "error_message", "payload_json", "received_at", "processed_at",
        ):
            with self.subTest(column=column):
                self.assertIn("`" + column + "`", migration)
        self.assertFalse((ROOT / "database/migrations/20261119000300_grant_finance_inbox_access.sql").exists())
        cancel_migration = CANCEL_MIGRATION.read_text()
        self.assertIn("`cancelled_at`", cancel_migration)
        self.assertIn("fin_sales_invoice_inbox", cancel_migration)
        self.assertIn("fin_purchase_bill_inbox", cancel_migration)
        dates_migration = DATES_MIGRATION.read_text()
        for column in ("invoice_date", "bill_date", "due_date"):
            self.assertIn("`" + column + "`", dates_migration)
        reference_migration = REFERENCE_MIGRATION.read_text()
        for column in ("integration_number", "integration_party_name", "integration_party_code"):
            self.assertIn("`" + column + "`", reference_migration)

    def test_api_uses_user_secret_and_tenant_scope(self):
        api = API.read_text()
        for marker in (
            "class Finance_api extends CI_Controller",
            "function sales_invoices", "function purchase_bills",
            "Authorization", "X-API-Secret", "hash_equals",
            "access_api_flag", "block_flag", "tenant_id",
            "find_number", "find_active_number", "raw_input_stream", "review_status",
            "status' => 'accepted'", "202",
            "strlen($description) > 240",
            "function dispatch", "function read_or_cancel", "function resolve_identifier",
            "function status_response", "function cancel_response",
            "Document already posted.", "Draft already created.",
            "'status' => 'cancelled'", "'GET, DELETE'", "already_cancelled",
            "$store->cancel($kind, $tenant_id, $inbox_id, $cancelled_at)",
            "cancel_linked_cancelled", "draft_status !== 'cancelled'",
            "function valid_date", "invoice_date", "bill_date", "due_date",
        ):
            self.assertIn(marker, api)
        config = CONFIG.read_text()
        self.assertIn("$config['csrf_exclude_uris'] = array('api/finance/.*');", config)

    def test_routes_precede_finance_data_catch_all(self):
        routes = ROUTES.read_text()
        for route in (
            "$route['api/finance/sales-invoices'] = 'finance_api/sales_invoices';",
            "$route['api/finance/purchase-bills'] = 'finance_api/purchase_bills';",
            "$route['api/finance/sales-invoices/(:num)'] = 'finance_api/sales_invoices/$1';",
            "$route['api/finance/purchase-bills/(:num)'] = 'finance_api/purchase_bills/$1';",
            "$route['admin/finance/sales_invoice_inbox'] = 'admin_finance_integration/sales_invoice_index';",
            "$route['admin/finance/sales_invoice_inbox/data'] = 'admin_finance_integration/sales_invoice_data';",
            "$route['admin/finance/sales_invoice_inbox/draft/(:num)'] = 'admin_finance_integration/sales_invoice_draft/$1';",
        ):
            self.assertIn(route, routes)
        self.assertLess(
            routes.index("$route['admin/finance/sales_invoice_inbox/data']"),
            routes.index("$route['admin/finance/(:any)/data']")
        )

    def test_admin_pages_create_drafts_with_finance_selected_party(self):
        admin = ADMIN.read_text()
        for marker in (
            "class Admin_finance_integration extends Admin",
            "sales_invoice_index", "sales_invoice_data", "sales_invoice_draft",
            "purchase_bill_index", "purchase_bill_data", "purchase_bill_draft",
            "finance_integration_store", "finance_document_store",
            "save_draft", "last_saved_id", "mark_processed",
            "finance_document_amount_readonly", "finance_document_description_readonly",
            "Nama Pelanggan", "Nama Pemasok",
            "This integration submission has been cancelled.",
            "integration_number", "integration_party_name", "integration_party_code",
            "finance_document_integration_party", "function format_amount",
            "'finance_' . ($kind === 'sales' ? 'sales_invoice_inbox' : 'purchase_bill_inbox')",
        ):
            self.assertIn(marker, admin)
        self.assertIn("values['amount'] = (string) $inbox['amount_before_tax']", admin)
        self.assertIn("values['description'] = (string) $inbox['description']", admin)

    def test_integration_date_filter_has_no_default_range(self):
        panel = (APP / "libraries/Finance_filter_panel.php").read_text()
        method = panel.split("case 'integration':", 1)[1].split("case 'journal':", 1)[0]
        self.assertIn("'default' => FALSE", method)

    def test_integration_status_filter_defaults_to_received(self):
        controller = (APP / "controllers/Admin_finance_integration.php").read_text()
        view = LIST_VIEW.read_text()
        panel = (APP / "libraries/Finance_filter_panel.php").read_text()
        partial = (APP / "views/admin/finance/partials/filter_panel.php").read_text()
        self.assertIn("'status_default' => 'received'", controller)
        self.assertIn("'status_default'=>'received'", view)
        self.assertIn("$status_raw === NULL && $config['status_default']", panel)
        self.assertIn("finance_filter_status_selected", partial)
        self.assertIn("data-default-status", partial)

    def test_integration_draft_uses_the_same_default_tax_as_document_forms(self):
        controller = (APP / "controllers/Admin_finance_integration.php").read_text()
        self.assertIn("'tax_id' => $this->default_tax_id($kind)", controller)
        self.assertIn("function default_tax_id($kind)", controller)
        self.assertIn("default_output_tax_id", controller)
        self.assertIn("default_input_tax_id", controller)
        self.assertIn("t.active_flag=1", controller)

    def test_integration_draft_does_not_block_different_totals(self):
        controller = (APP / "controllers/Admin_finance_integration.php").read_text()
        self.assertNotIn("total_mismatch", controller)
        self.assertIn("tax_id_required", controller)
        self.assertIn("tax_id_invalid", controller)

    def test_integration_draft_returns_to_the_inbox_list(self):
        controller = (APP / "controllers/Admin_finance_integration.php").read_text(encoding="utf-8")
        view = (APP / "views/admin/finance/integration_index.php").read_text(encoding="utf-8")
        self.assertIn("$inbox_path = '/admin/finance/' . ($kind === 'sales' ? 'sales_invoice_inbox' : 'purchase_bill_inbox');", controller)
        self.assertIn("redirect($inbox_path . '?status=processed', 'location', 303);", controller)
        self.assertIn("'finance_status' => (string) $this->session->flashdata('finance_status')", controller)
        self.assertIn("$finance_status === 'draft_saved'", view)

    def test_integration_errors_have_specific_labels_for_every_locale(self):
        for locale in LOCALES:
            text = (APP / f"language/{locale}/finance_lang.php").read_text(encoding="utf-8")
            for code in (
                "tax_id_required", "tax_id_invalid", "total_mismatch",
                "document_save_error", "document_control_account_missing",
                "document_settings_missing",
            ):
                with self.subTest(locale=locale, code=code):
                    self.assertIn(f"finance_doc_error_{code}", text)

    def test_views_and_store(self):
        view = LIST_VIEW.read_text()
        self.assertIn("finance-integration-table", view)
        self.assertIn("Jadikan Draft", view)
        self.assertIn("Lihat Draft", view)
        self.assertIn("amount_before_tax", view)
        self.assertIn("tax_percent", view)
        self.assertIn("dateField", view)
        self.assertIn("finance_integration_document_date", view)
        self.assertIn("String(row.status || '') === 'cancelled'", view)
        self.assertIn("finance_integration_no_action", view)
        form = FORM_VIEW.read_text()
        self.assertIn("$finance_document_amount_readonly", form)
        self.assertIn("$finance_document_description_readonly", form)
        self.assertIn("$finance_document_party_label", form)
        self.assertIn("$finance_document_integration_party", form)
        self.assertIn("selectedLabel", form)
        self.assertIn("read-only:bg-slate-50", form)
        document_store = DOCUMENT_STORE.read_text()
        self.assertIn("integration_number", document_store)
        self.assertIn("integration_party_name", document_store)
        self.assertIn("integration_party_code", document_store)
        store = STORE.read_text()
        self.assertIn("function count", store)
        self.assertIn("function page", store)
        self.assertIn("function mark_processed", store)
        self.assertIn("function document_status", store)
        self.assertIn("function cancel", store)
        self.assertIn("->where('status', 'received')", store)
        self.assertIn("$spec['date'] => (string) $data['date']", store)
        self.assertIn("function cancel_linked_cancelled", store)
        self.assertIn("function find_active_number", store)
        self.assertIn("status !=', 'cancelled", store)
        self.assertIn("CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END", store)
        self.assertIn("->where('tenant_id', (int) $tenant_id)", store)

    def test_posted_and_cancelled_documents_open_read_only(self):
        routes = ROUTES.read_text(encoding="utf-8")
        self.assertIn("$route['admin/finance/(:any)/view/(:num)'] = 'admin_finance/view/$1/$2';", routes)
        self.assertLess(
            routes.index("admin/finance/(:any)/view/(:num)"),
            routes.index("admin/finance/(:any)/edit/(:num)")
        )
        controller = ADMIN_CONTROLLER.read_text(encoding="utf-8")
        self.assertIn("public function view($slug = '', $id = NULL)", controller)
        self.assertIn("finance_document_view", controller)
        self.assertIn("admin/finance/document_view", controller)
        store = STORE.read_text(encoding="utf-8")
        self.assertIn("AS draft_status", store)
        view = LIST_VIEW.read_text(encoding="utf-8")
        self.assertIn("row.draft_status", view)
        self.assertIn("'/view/'", view)
        self.assertIn("finance_view_document", view)
        detail = (APP / "views/admin/finance/document_view.php").read_text(encoding="utf-8")
        for marker in ("finance_view_document", "finance_status_cancelled", "finance_document_number", "finance_document_integration_reference"):
            self.assertIn(marker, detail)

    def test_cancelled_numbers_can_be_submitted_again(self):
        migration = REUSE_MIGRATION.read_text(encoding="utf-8")
        schema = SCHEMA.read_text(encoding="utf-8")
        for table, column, number, key in (
            ("fin_sales_invoice_inbox", "active_invoice_number", "invoice_number", "uk_sales_invoice_inbox_active_number"),
            ("fin_purchase_bill_inbox", "active_bill_number", "bill_number", "uk_purchase_bill_inbox_active_number"),
        ):
            with self.subTest(table=table):
                self.assertIn("ALTER TABLE `" + table + "`", migration)
                self.assertIn("`" + column + "`", migration)
                self.assertIn("`" + number + "`", migration)
                self.assertIn("GENERATED ALWAYS AS", migration)
                self.assertIn("`status` <> 'cancelled'", migration)
                self.assertIn("UNIQUE KEY `" + key + "`", migration)
                self.assertIn("`" + column + "`", schema)
                self.assertIn("`" + key + "`", schema)
        docs = DOCS.read_text(encoding="utf-8")
        self.assertIn("Nomor yang sudah `cancelled` boleh dikirim ulang", docs)
        self.assertIn("20261119001000_allow_finance_inbox_number_reuse.sql", docs)

    def test_cancelled_drafts_sync_their_integration_inbox_rows(self):
        store = CANCELLATION_STORE.read_text(encoding="utf-8")
        self.assertIn("sync_integration_inbox", store)
        self.assertIn("fin_sales_invoice_inbox", store)
        self.assertIn("fin_purchase_bill_inbox", store)
        self.assertIn("'sales_invoice_id'", store)
        self.assertIn("'purchase_bill_id'", store)
        self.assertIn("'status' => 'cancelled'", store)
        self.assertIn("'cancelled_at'", store)
        migration = SYNC_MIGRATION.read_text(encoding="utf-8")
        for marker in (
            "UPDATE `fin_sales_invoice_inbox`", "UPDATE `fin_purchase_bill_inbox`",
            "d.`status` = 'cancelled'", "i.`status` <> 'cancelled'", "COALESCE(i.`cancelled_at`, NOW())",
        ):
            self.assertIn(marker, migration)
        view = LIST_VIEW.read_text(encoding="utf-8")
        self.assertIn("finance_integration_cancelled", view)
        self.assertIn("statusLabels", view)

    def test_invoice_and_bill_lists_show_integration_number(self):
        index_view = DOCUMENT_INDEX.read_text()
        self.assertIn("finance_column_integration_number", index_view)
        self.assertIn("integration_number", index_view)
        self.assertIn("person_name", index_view)
        self.assertIn("finance_customer", index_view)
        self.assertIn("finance_supplier", index_view)
        controller = ADMIN_CONTROLLER.read_text()
        self.assertIn("3 => 'person_name'", controller)
        self.assertIn("$record['person_id'] = $saved[$kind === 'sales' ? 'customer_id' : 'supplier_id'];", controller)
        document_store = DOCUMENT_STORE.read_text()
        self.assertIn("d.integration_number", document_store)
        self.assertIn("AS person_name", document_store)
        self.assertIn("or_like('d.integration_number'", document_store)

    def test_menus_and_labels_exist(self):
        sales = SALES_MENU.read_text()
        purchase = PURCHASE_MENU.read_text()
        self.assertIn('code="fin_sales_invoice_inbox"', sales)
        self.assertIn('code="fin_purchase_bill_inbox"', purchase)
        self.assertIn("fin_sales_invoice_inbox_add", sales)
        self.assertIn("fin_purchase_bill_inbox_add", purchase)
        for locale in LOCALES:
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "admin_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['admin_finance_sales_invoice_inbox']", content)
                self.assertIn("$lang['admin_finance_purchase_bill_inbox']", content)


if __name__ == "__main__":
    unittest.main()
