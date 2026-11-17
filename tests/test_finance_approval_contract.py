from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
MIGRATION = APP / "migrations/20261117000300_link_finance_transactions_to_approval.php"
FINANCE_STORE = APP / "libraries/Finance_transaction_store.php"
SEQUENCE_STORE = APP / "libraries/Application_sequence_store.php"
APPROVAL_STORE = APP / "libraries/Approval_workflow_store.php"
FINANCE_CONTROLLER = APP / "controllers/Admin_finance.php"
APPROVAL_CONTROLLER = APP / "controllers/Admin_approval.php"
FINANCE_INDEX = APP / "views/admin/finance/transaction_index.php"
APPROVAL_VIEW = APP / "views/admin/approval/index.php"
ROUTES = APP / "config/routes.php"


class FinanceApprovalContractTests(unittest.TestCase):
    def test_migration_links_finance_documents_to_approval_requests(self):
        migration = MIGRATION.read_text()
        for column in ("source_type", "source_id", "approval_request_id"):
            with self.subTest(column=column):
                self.assertIn("'" + column + "'", migration)
        self.assertIn("app_approval_request", migration)
        self.assertIn("fin_transaction", migration)
        self.assertIn("information_schema.STATISTICS", migration)

    def test_income_and_expense_numbers_use_tenant_sequences(self):
        store = FINANCE_STORE.read_text()
        sequence = SEQUENCE_STORE.read_text()
        self.assertIn("income_code", store)
        self.assertIn("expenses_code", store)
        self.assertIn("next_code_for_tenant", store)
        self.assertIn("advance_sequence", store)
        self.assertIn("public function next_code_for_tenant($tenant_id, $sequence_code)", sequence)
        self.assertIn("sys_sequence", sequence)
        self.assertIn("app_sequence", sequence)

    def test_draft_can_only_be_posted_after_approval(self):
        store = FINANCE_STORE.read_text()
        self.assertIn("in_array($existing['status'], array('draft', 'revise'), TRUE)", store)
        self.assertIn("if ($record['status'] !== 'approved' || !$record['line'])", store)
        self.assertIn("->where('status', 'approved')", store)

    def test_finance_controller_submits_approval_and_revise_resubmits_on_save(self):
        controller = FINANCE_CONTROLLER.read_text()
        routes = ROUTES.read_text()
        self.assertIn("$route['admin/finance/(:any)/submit_approval/(:num)'] = 'admin_finance/submit_approval/$1/$2';", routes)
        self.assertIn("public function submit_approval($slug = '', $id = 0)", controller)
        self.assertIn("private function finance_transaction_start_approval($slug, $id)", controller)
        self.assertIn("$type === 'income' ? 'finance_income' : 'finance_expenses'", controller)
        self.assertIn("'fin_transaction'", controller)
        self.assertIn("$record['status'] === 'revise'", controller)
        self.assertIn("finance_transaction_start_approval($slug, (int) $id)", controller)

    def test_approval_decisions_support_revise_with_a_required_reason(self):
        store = APPROVAL_STORE.read_text()
        self.assertIn("in_array($decision,array('approved','rejected','revise'),TRUE)", store)
        self.assertIn("if ($decision === 'revise' && $note === '')", store)
        self.assertIn("$decision==='revise'?'revise'", store)
        self.assertIn("private function finalize_source($request)", store)
        self.assertIn("'source_type'", store)
        self.assertIn("'source_id'", store)
        self.assertIn("'pending_approval','revise'", store)

    def test_revise_button_requires_a_note_in_the_approval_ui(self):
        view = APPROVAL_VIEW.read_text()
        self.assertIn('value="revise"', view)
        self.assertIn("Alasan revisi wajib diisi.", view)
        self.assertIn("setCustomValidity", view)
        self.assertIn("revise:'Revisi'", view)

    def test_finance_dashboard_recent_journals_show_line_level_debit_and_credit(self):
        store = (APP / "libraries/Finance_report_store.php").read_text()
        view = (APP / "views/admin/finance/dashboard.php").read_text()
        self.assertIn("recent_journal_lines", store)
        self.assertIn("jl.debit_base_amount", store)
        self.assertIn("jl.credit_base_amount", store)
        self.assertIn("recent_journal_lines", view)
        self.assertIn("$line['debit_base_amount']", view)
        self.assertIn("$line['credit_base_amount']", view)
        self.assertIn("finance_report_column_account_code", view)

    def test_finance_list_shows_submit_action_revise_reason_and_post_after_approval(self):
        view = FINANCE_INDEX.read_text()
        self.assertIn("submit_approval/", view)
        self.assertIn("row.status === 'draft'", view)
        self.assertIn("row.status === 'revise'", view)
        self.assertIn("row.status === 'approved'", view)
        self.assertIn("row.revise_note", view)
        self.assertIn("Alasan revisi", view)
        self.assertIn("data-finance-actions-toggle", view)
        self.assertIn("data-finance-actions-menu", view)
        self.assertNotIn('class="me-3 font-semibold text-indigo-700"', view)


if __name__ == "__main__":
    unittest.main()
