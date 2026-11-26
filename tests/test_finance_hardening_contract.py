from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class FinanceHardeningContractTests(unittest.TestCase):
    def test_revise_transactions_update_the_header(self):
        store = (APP / "libraries" / "Finance_transaction_store.php").read_text(encoding="utf-8")
        self.assertIn("where_in('status', array('draft', 'revise'))", store)
        self.assertNotIn("->where('status', 'draft')\n                ->update('fin_transaction'", store)

    def test_drafts_can_be_deleted_and_release_reservations(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        routes = (APP / "config" / "routes.php").read_text(encoding="utf-8")
        script = (ROOT / "web/assets/js/finance-list.js").read_text(encoding="utf-8")
        footer = (APP / "views/admin/footer.php").read_text(encoding="utf-8")
        self.assertIn("public function delete_draft(", controller)
        self.assertIn("fin_customer_payment_allocation", controller)
        self.assertIn("admin/finance/delete_draft/(:any)/(:num)", routes)
        self.assertIn("'/admin/finance/delete_draft/' + cfg.slug + '/' + sourceId", script)
        self.assertNotIn("'/' + cfg.slug + '/delete_draft/'", script)
        self.assertIn("data-finance-draft-delete", script)
        self.assertIn("finance-draft-delete-form", footer)

    def test_cancellation_reversal_uses_an_open_period(self):
        store = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        self.assertIn("function reversal_target(", store)
        self.assertIn("date('Y-m-d')", store)
        self.assertIn("'period_id' => (int) $target['period_id']", store)
        self.assertIn("'journal_date' => (string) $target['date']", store)

    def test_cancel_pending_is_visible_and_blocks_period_close(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        panel = (APP / "libraries" / "Finance_filter_panel.php").read_text(encoding="utf-8")
        self.assertIn("'cancel_pending' =>", panel)
        self.assertIn("'cancel_pending'", controller.split("$active_statuses", 1)[1][:200])
        for name in ("transaction_index.php", "document_index.php", "payment_index.php",
                     "journal_index.php", "transfer_index.php", "reconciliation_index.php"):
            source = (APP / "views/admin/finance" / name).read_text(encoding="utf-8")
            self.assertIn("cancel_pending", source, name)

    def test_payment_saves_lock_the_document_and_check_status_updates(self):
        store = (APP / "libraries" / "Finance_payment_store.php").read_text(encoding="utf-8")
        self.assertIn("LIMIT 1 FOR UPDATE", store)
        self.assertIn("affected_rows() !== 1", store)
        self.assertIn("'available_amount'", store)

    def test_department_references_are_tenant_scoped(self):
        journal = (APP / "libraries" / "Finance_journal_store.php").read_text(encoding="utf-8")
        document = (APP / "libraries" / "Finance_document_store.php").read_text(encoding="utf-8")
        for source in (journal, document):
            self.assertIn("fin_department", source)
            self.assertIn("department_id", source)

    def test_period_fiscal_and_master_data_validation(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        for marker in ("fiscal_year_overlap", "fiscal_year_has_open_periods",
                       "account_id_duplicate", "receivable_account_id", "payable_account_id"):
            self.assertIn(marker, controller)

    def test_payment_sort_and_document_report_queries_are_scoped(self):
        payment = (APP / "libraries" / "Finance_payment_store.php").read_text(encoding="utf-8")
        document = (APP / "libraries" / "Finance_document_store.php").read_text(encoding="utf-8")
        report = (APP / "libraries" / "Finance_report_store.php").read_text(encoding="utf-8")
        self.assertIn("'document_integration_number'", payment)
        self.assertIn("a.tenant_id=", document)
        self.assertNotIn("WHERE ca.tenant_id=? AND ca.active_flag=1", report)

    def test_integration_inbox_queries_qualify_joined_columns(self):
        store = (APP / "libraries" / "Finance_integration_store.php").read_text(encoding="utf-8")
        self.assertIn("apply_search($spec, $search, 't.')", store)
        self.assertIn("apply_filters($spec, $filters, 't.')", store)
        self.assertIn("order_by('t.' . $order_column", store)

    def test_integration_draft_keeps_submitted_total(self):
        controller = (APP / "controllers" / "Admin_finance_integration.php").read_text(encoding="utf-8")
        store = (APP / "libraries" / "Finance_integration_store.php").read_text(encoding="utf-8")
        self.assertIn("tax_id_required", controller)
        self.assertNotIn("total_mismatch", controller)
        self.assertIn("trans_begin()", controller)
        self.assertIn("mark_processed(", controller)
        self.assertNotIn("select('t.*,'", store)

    def test_reports_use_sql_aggregates_and_audit_cancellations(self):
        report = (APP / "libraries" / "Finance_report_store.php").read_text(encoding="utf-8")
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        manifest = (APP / "views/admin/menu_manifest/finance_report.xml").read_text(encoding="utf-8")
        self.assertIn("finance_summary", report)
        self.assertIn("cancellation_history", report)
        self.assertIn("fin_cancellation_history", controller)
        self.assertIn("fin_cancellation_history", manifest)

    def test_journal_balance_and_export_safety(self):
        journal = (APP / "libraries" / "Finance_journal_store.php").read_text(encoding="utf-8")
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        self.assertIn("return 'unbalanced';", journal)
        self.assertIn("finance_balance_guard", journal)
        self.assertIn("finance_csv_cell", controller)
        self.assertIn("preg_match('/^[=+\\-@\\t\\r]/'", controller)

    def test_api_payload_is_bounded_and_reconciliation_lines_are_unique(self):
        api = (APP / "controllers" / "Finance_api.php").read_text(encoding="utf-8")
        migration = (ROOT / "database/migrations/20261119001500_unique_reconciliation_journal_line.sql").read_text(encoding="utf-8")
        self.assertIn("1048576", api)
        self.assertIn("uk_bank_reconciliation_journal_line", migration)


if __name__ == "__main__":
    unittest.main()
