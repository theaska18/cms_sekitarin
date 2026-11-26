from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
PARTIAL = ROOT / "web/application/views/admin/finance/partials/filter_panel.php"
JS = ROOT / "web/assets/js/finance-filters.js"
FOOTER = ROOT / "web/application/views/admin/footer.php"
FINANCE = ROOT / "web/application/views/admin/finance"
STORES = ROOT / "web/application/libraries"


class FinanceFilterPanelContractTests(unittest.TestCase):
    def test_finance_add_actions_live_in_the_filter_panel(self):
        for name in ("document_index.php", "payment_index.php", "transfer_index.php", "reconciliation_index.php", "resource_index.php"):
            with self.subTest(view=name):
                view = (FINANCE / name).read_text()
                self.assertIn("add_action", view)
                self.assertIn("finance_filter_panel->render", view)
        panel = PARTIAL.read_text()
        self.assertIn("max-w-md", panel)

    def test_table_list_shell_is_implemented_as_a_library(self):
        library = (ROOT / "web/application/libraries/Finance_list_table.php").read_text()
        self.assertIn("class Finance_list_table", library)
        self.assertIn("public function open(", library)
        self.assertIn("public function close(", library)
        controller = (ROOT / "web/application/controllers/Admin_finance.php").read_text()
        self.assertIn("load->library('finance_list_table')", controller)
        for name in ("transaction_index.php", "document_index.php", "integration_index.php"):
            view = (FINANCE / name).read_text()
            self.assertIn("finance_list_table->open", view)
            self.assertIn("finance_list_table->close", view)

    def test_filter_panel_is_implemented_as_a_library(self):
        library = (ROOT / "web/application/libraries/Finance_filter_panel.php").read_text()
        self.assertIn("class Finance_filter_panel", library)
        self.assertIn("public function config(", library)
        self.assertIn("public function filters(", library)
        self.assertIn("public function render(", library)
        self.assertIn("public function apply(", library)
        controller = (ROOT / "web/application/controllers/Admin_finance.php").read_text()
        integration = (ROOT / "web/application/controllers/Admin_finance_integration.php").read_text()
        self.assertIn("load->library('finance_filter_panel')", controller)
        self.assertIn("load->library('finance_filter_panel')", integration)
        for name in ("transaction_index.php", "document_index.php", "integration_index.php"):
            view = (FINANCE / name).read_text()
            self.assertIn("finance_filter_panel->render", view)
            self.assertNotIn("load->view('admin/finance/partials/filter_panel'", view)

    def test_shared_panel_has_date_range_status_and_search(self):
        panel = PARTIAL.read_text()
        self.assertIn("data-finance-filter-panel", panel)
        self.assertIn("finance_filter_date_fields", panel)
        self.assertIn("$date_field['start_name']", panel)
        self.assertIn("$date_field['end_name']", panel)
        self.assertIn("data-finance-filter=\"status\"", panel)
        self.assertIn("data-finance-filter=\"flag\"", panel)
        self.assertIn("data-finance-filter=\"search\"", panel)
        self.assertIn("data-finance-filter=\"page_size\"", panel)
        self.assertIn("strtotime('-6 days')", panel)
        self.assertIn("data-finance-filter-toggle", panel)
        self.assertIn("data-finance-filter-details", panel)
        self.assertIn("finance-filter-details-", panel)

    def test_shared_javascript_sends_filters_and_hides_default_toolbar(self):
        script = JS.read_text()
        footer = FOOTER.read_text()
        self.assertIn("finance-filters.js", footer)
        self.assertIn("table.on('preXhr.dt'", script)
        self.assertIn("Object.keys(current).forEach", script)
        self.assertIn("data[key] = current[key]", script)
        self.assertIn("detailFields", script)
        self.assertIn("date_ranges", (ROOT / "web/application/libraries/Finance_filter_panel.php").read_text())
        self.assertIn(".has('.dt-search, .dt-length').hide()", script)
        self.assertIn("api.search(search.value).draw()", script)
        self.assertIn("api.ajax.reload()", script)
        self.assertIn("data-finance-filter-details", script)
        self.assertIn("data-finance-filter-toggle", script)
        self.assertIn("data-finance-filter-indicator", script)
        self.assertIn("setDetailsOpen", script)

    def test_finance_list_pages_use_the_filter_panel(self):
        for name in (
            "transaction_index.php",
            "journal_index.php",
            "document_index.php",
            "payment_index.php",
            "transfer_index.php",
            "reconciliation_index.php",
            "resource_index.php",
            "report_index.php",
            "integration_index.php",
        ):
            with self.subTest(view=name):
                view = (FINANCE / name).read_text()
                self.assertIn("finance_filter_panel->render", view)
                self.assertNotIn("load->view('admin/finance/partials/filter_panel'", view)

    def test_column_filters_and_export_actions_exist(self):
        library = (ROOT / "web/application/libraries/Finance_filter_panel.php").read_text()
        panel = PARTIAL.read_text()
        script = JS.read_text()
        controller = (ROOT / "web/application/controllers/Admin_finance.php").read_text()
        store = (STORES / "Finance_transaction_store.php").read_text()
        self.assertIn("'field_filters'", library)
        self.assertIn("finance_filter_field_filters", panel)
        self.assertIn("data-finance-export-toggle", panel)
        self.assertIn("data-finance-export-pdf", panel)
        self.assertIn("data-finance-export-excel", panel)
        self.assertIn("exportPdf", script)
        self.assertIn("exportCsv", script)
        self.assertIn("finance_transaction_filter_config", controller)
        self.assertIn("amount_min", store)
        self.assertIn("currency_code", store)

    def test_multiple_date_columns_have_separate_ranges(self):
        library = (ROOT / "web/application/libraries/Finance_filter_panel.php").read_text()
        self.assertIn("public function date_fields(", library)
        self.assertIn("'transaction_on'", library)
        self.assertIn("'create_on'", library)
        self.assertIn("date_ranges", library)
        transaction_view = (FINANCE / "transaction_index.php").read_text()
        self.assertIn("'date_preset' => 'transaction'", (ROOT / "web/application/controllers/Admin_finance.php").read_text())
        self.assertIn("$finance_filter_config", transaction_view)
        store = (STORES / "Finance_transaction_store.php").read_text()
        self.assertIn("$filters['date_ranges']", store)

    def test_finance_lists_have_no_default_date_range(self):
        library = (ROOT / "web/application/libraries/Finance_filter_panel.php").read_text()
        for preset in ("transaction", "transfer", "journal", "payment", "reconciliation", "document", "integration"):
            with self.subTest(preset=preset):
                method = library.split(f"case '{preset}':", 1)[1].split("case '", 1)[0]
                self.assertIn("'default' => FALSE", method)
                self.assertNotIn("'default' => TRUE", method)
        report = library.split("case 'report':", 1)[1].split("case '", 1)[0]
        self.assertIn("'default' => TRUE", report)

    def test_document_lists_have_no_default_date_range(self):
        library = (ROOT / "web/application/libraries/Finance_filter_panel.php").read_text()
        method = library.split("case 'document':", 1)[1].split("case 'integration':", 1)[0]
        self.assertIn("'default' => FALSE", method)
        self.assertIn("'date_preset'=>'document'", (ROOT / "web/application/views/admin/finance/document_index.php").read_text())

    def test_stores_accept_date_status_and_flag_filters(self):
        expected = {
            "Finance_transaction_store.php": ("transaction_on", "status"),
            "Finance_journal_store.php": ("journal_date", "status"),
            "Finance_document_store.php": ("$spec['date']", "status"),
            "Finance_payment_store.php": ("payment_date", "status"),
            "Finance_transfer_store.php": ("transfer_date", "status"),
            "Finance_reconciliation_store.php": ("statement_date", "status"),
            "Finance_integration_store.php": ("$spec['date']", "status"),
        }
        for name, columns in expected.items():
            with self.subTest(store=name):
                content = (STORES / name).read_text()
                self.assertIn("$filters = array()", content)
                for column in columns:
                    self.assertIn(column, content)
        self.assertIn("active_flag", (STORES / "Finance_store.php").read_text())


if __name__ == "__main__":
    unittest.main()
