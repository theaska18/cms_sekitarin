from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
SCHEMA = ROOT / "database/schema.sql"
FINANCE_STORE = APP / "libraries/Finance_transaction_store.php"
SEQUENCE_STORE = APP / "libraries/Application_sequence_store.php"
APPROVAL_STORE = APP / "libraries/Approval_workflow_store.php"
FINANCE_CONTROLLER = APP / "controllers/Admin_finance.php"
APPROVAL_CONTROLLER = APP / "controllers/Admin_approval.php"
FINANCE_INDEX = APP / "views/admin/finance/transaction_index.php"
APPROVAL_VIEW = APP / "views/admin/approval/index.php"
ROUTES = APP / "config/routes.php"


class FinanceApprovalContractTests(unittest.TestCase):
    def test_schema_links_finance_documents_to_approval_requests(self):
        schema = SCHEMA.read_text()
        request = schema.split("CREATE TABLE `app_approval_request`", 1)[1].split("ENGINE=", 1)[0]
        transaction = schema.split("CREATE TABLE `fin_transaction`", 1)[1].split("ENGINE=", 1)[0]
        for column in ("source_type", "source_id"):
            with self.subTest(column=column):
                self.assertIn("`" + column + "`", request)
        self.assertIn("`approval_request_id`", transaction)
        self.assertIn("idx_fin_transaction_approval", transaction)
        self.assertIn("app_approval_request", transaction)

    def test_income_and_expense_numbers_use_tenant_sequences(self):
        store = FINANCE_STORE.read_text()
        sequence = SEQUENCE_STORE.read_text()
        self.assertIn("income_code", store)
        self.assertIn("expenses_code", store)
        self.assertIn("next_code_for_tenant", store)
        self.assertIn("advance_sequence", store)
        self.assertIn("public function next_code_for_tenant($tenant_id, $sequence_code, $require_enabled = TRUE)", sequence)
        self.assertIn("sys_sequence", sequence)
        self.assertIn("app_sequence", sequence)

    def test_expense_income_journal_number_uses_configured_sequence_ignoring_flag(self):
        store = FINANCE_STORE.read_text()
        sequence = SEQUENCE_STORE.read_text()
        self.assertIn("journal_expenses_income", store)
        self.assertIn("next_code_for_tenant(", store)
        self.assertIn("'journal_expenses_income',\n            FALSE", store)
        self.assertIn("advance_sequence($tenant_id, 'journal_expenses_income'", store)
        self.assertIn("transaction_number_exists", store)
        self.assertIn("next_code_after", store)
        self.assertNotIn("'TRX-' . (int) $record['transaction_id']", store)
        self.assertIn("public function next_code_for_update($tenant_id, $sequence_code, $require_enabled = TRUE)", sequence)
        self.assertIn("public function next_code_for_tenant($tenant_id, $sequence_code, $require_enabled = TRUE)", sequence)
        self.assertIn("($require_enabled && (string) $query->row()->use_flag !== '1')", sequence)

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

    def test_finance_approval_uses_the_draft_owner_as_requester(self):
        controller = FINANCE_CONTROLLER.read_text()
        store = APPROVAL_STORE.read_text()
        self.assertIn("'fin_transaction', (int) $id, (int) $record['created_by']", controller)
        self.assertIn("'fin_cash_transfer', (int) $id, (int) $record['created_by']", controller)
        self.assertIn("'fin_journal_entry', (int) $id, (int) $record['created_by']", controller)
        submit = store.split("public function submit(", 1)[1].split("private function create_approval_token", 1)[0]
        self.assertIn("$requester_user_id = $requester_user_id === NULL", submit)
        self.assertIn("$requester_employee_id", submit)
        self.assertIn("'submitted_by'=>$submitted_by", submit)
        self.assertIn("$this->resolve_approver($step,$requester_employee_id", submit)

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

    def test_income_expense_approval_details_name_the_accounts_and_amount(self):
        controller = (APP / "controllers/Admin_finance.php").read_text()
        method = controller.split("private function finance_transaction_start_approval(", 1)[1].split("private function finance_transfer_start_approval(", 1)[0]
        self.assertIn("$subject = $type === 'income' ? 'Income' : 'Expense';", method)
        self.assertIn("'Nomor: ' . (string) $record['transaction_number']", method)
        self.assertIn("'Jenis: ' . ($type === 'income' ? 'Income' : 'Expense')", method)
        self.assertIn("'Jumlah: ' . $this->finance_approval_amount($record['total_amount'])", method)
        self.assertIn("'Akun Kas/Bank: ' . $cash_label", method)
        self.assertIn("'Akun: ' . $account_label", method)
        self.assertIn("$account_kind = $type === 'income' ? 'revenue_account' : 'expense_account';", method)
        self.assertIn("$category_kind = $type === 'income' ? 'income_category' : 'expense_category';", method)

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
