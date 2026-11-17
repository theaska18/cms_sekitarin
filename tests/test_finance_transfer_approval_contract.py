from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
MIGRATION = APP / "migrations/20261117000400_link_cash_transfers_to_approval.php"
STORE = APP / "libraries/Finance_transfer_store.php"
CONTROLLER = APP / "controllers/Admin_finance.php"
APPROVAL_STORE = APP / "libraries/Approval_workflow_store.php"
VIEW = APP / "views/admin/finance/transfer_index.php"


class FinanceTransferApprovalContractTests(unittest.TestCase):
    def test_migration_links_cash_transfers_to_approval_requests(self):
        migration = MIGRATION.read_text()
        self.assertIn("'approval_request_id'", migration)
        self.assertIn("fin_cash_transfer", migration)
        self.assertIn("idx_fin_cash_transfer_approval", migration)

    def test_transfer_numbers_use_transfer_code_sequence(self):
        store = STORE.read_text()
        self.assertIn("'transfer_code'", store)
        self.assertIn("next_code_for_tenant($tenant, 'transfer_code')", store)
        self.assertIn("advance_sequence($tenant, 'transfer_code'", store)
        self.assertIn("transfer_number_exists", store)

    def test_transfer_journal_uses_journal_transfer_sequence_with_fallback(self):
        store = STORE.read_text()
        self.assertIn("'journal_transfer'", store)
        self.assertIn("next_code_for_tenant($tenant, 'journal_transfer', FALSE)", store)
        self.assertIn("advance_sequence($tenant, 'journal_transfer'", store)
        self.assertIn("'TRF-' . (int) $id", store)
        self.assertIn("journal_number_exists", store)

    def test_transfer_can_only_be_posted_after_approval(self):
        store = STORE.read_text()
        self.assertIn("if($row['status']!=='approved')return 'not_postable';", store)
        self.assertIn("in_array($existing['status'],array('draft','revise')", store)
        self.assertIn("where('status','approved')", store)

    def test_controller_submits_transfer_approval_and_resubmits_revise(self):
        controller = CONTROLLER.read_text()
        self.assertIn("'cash_bank_transfer'", controller)
        self.assertIn("finance_transfer_start_approval", controller)
        self.assertIn("$approval->submit('finance_transfer'", controller)
        self.assertIn("'fin_cash_transfer'", controller)
        self.assertIn("$was_revise", controller)
        self.assertIn("finance_transfer_start_approval((int)$id)", controller)

    def test_approval_store_finalizes_cash_transfer_statuses(self):
        store = APPROVAL_STORE.read_text()
        self.assertIn("'fin_cash_transfer' => array('table' => 'fin_cash_transfer', 'id' => 'cash_transfer_id')", store)
        self.assertIn("->where_in('status', array('pending_approval','revise'))", store)

    def test_transfer_list_uses_three_dot_actions_and_revision_reason(self):
        view = VIEW.read_text()
        self.assertIn("data-transfer-actions-toggle", view)
        self.assertIn("data-transfer-actions-menu", view)
        self.assertIn("submit_approval/", view)
        self.assertIn("r.status==='approved'", view)
        self.assertIn("r.revise_note", view)
        self.assertIn("labels.reason", view)


if __name__ == "__main__":
    unittest.main()
