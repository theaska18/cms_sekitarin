from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
MIGRATION = ROOT / "database/migrations/20261119000200_create_finance_inbox.sql"
GRANT = ROOT / "database/migrations/20261119000300_grant_finance_inbox_access.sql"
API = APP / "controllers/Finance_api.php"
ADMIN = APP / "controllers/Admin_finance_integration.php"
STORE = APP / "libraries/Finance_integration_store.php"
LIST_VIEW = APP / "views/admin/finance/integration_index.php"
FORM_VIEW = APP / "views/admin/finance/document_form.php"
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
        grant = GRANT.read_text()
        self.assertIn("fin_sales_invoice_inbox", grant)
        self.assertIn("fin_purchase_bill_inbox", grant)
        self.assertIn("sys_tenant_menu", grant)
        self.assertIn("app_role_menu", grant)

    def test_api_uses_user_secret_and_tenant_scope(self):
        api = API.read_text()
        for marker in (
            "class Finance_api extends CI_Controller",
            "function sales_invoices", "function purchase_bills",
            "Authorization", "X-API-Secret", "hash_equals",
            "access_api_flag", "block_flag", "tenant_id",
            "find_number", "raw_input_stream", "review_status",
            "status' => 'accepted'", "202",
        ):
            self.assertIn(marker, api)
        config = CONFIG.read_text()
        self.assertIn("$config['csrf_exclude_uris'] = array('api/finance/.*');", config)

    def test_routes_precede_finance_data_catch_all(self):
        routes = ROUTES.read_text()
        for route in (
            "$route['api/finance/sales-invoices'] = 'finance_api/sales_invoices';",
            "$route['api/finance/purchase-bills'] = 'finance_api/purchase_bills';",
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
        ):
            self.assertIn(marker, admin)
        self.assertIn("values['amount'] = (string) $inbox['amount_before_tax']", admin)
        self.assertIn("values['description'] = (string) $inbox['description']", admin)

    def test_views_and_store(self):
        view = LIST_VIEW.read_text()
        self.assertIn("finance-integration-table", view)
        self.assertIn("Jadikan Draft", view)
        self.assertIn("Lihat Draft", view)
        self.assertIn("amount_before_tax", view)
        self.assertIn("tax_percent", view)
        form = FORM_VIEW.read_text()
        self.assertIn("$finance_document_amount_readonly", form)
        self.assertIn("$finance_document_description_readonly", form)
        self.assertIn("$finance_document_party_label", form)
        self.assertIn("read-only:bg-slate-50", form)
        store = STORE.read_text()
        self.assertIn("function count", store)
        self.assertIn("function page", store)
        self.assertIn("function mark_processed", store)
        self.assertIn("->where('tenant_id', (int) $tenant_id)", store)

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
