from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
CONTROLLER = APP / "controllers/Admin_finance.php"
JOURNAL = APP / "libraries/Finance_journal_store.php"
APPROVAL = APP / "libraries/Approval_workflow_store.php"
FORM = APP / "views/admin/finance/journal_form.php"
INDEX = APP / "views/admin/finance/journal_index.php"
MIGRATION = APP / "migrations/20261117000500_link_opening_balances_to_approval.php"
LOCALES = ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish")


class FinanceOpeningBalanceApprovalContractTests(unittest.TestCase):
    def test_migration_links_journal_entries_and_registers_the_sequence(self):
        migration = MIGRATION.read_text()
        self.assertIn("fin_journal_entry", migration)
        self.assertIn("approval_request_id", migration)
        self.assertIn("journal_opening_balance", migration)
        self.assertIn("OB-{Y}{M}{D}-{NNNNN}", migration)

    def test_approval_store_finalizes_opening_balance_statuses(self):
        approval = APPROVAL.read_text()
        self.assertIn("'fin_journal_entry' => array('table' => 'fin_journal_entry', 'id' => 'journal_entry_id')", approval)

    def test_journal_store_uses_opening_balance_sequence_and_requires_approval(self):
        journal = JOURNAL.read_text()
        save = journal.split("public function save_draft(", 1)[1].split("public function post(", 1)[0]
        post = journal.split("public function post(", 1)[1].split("public function lookup_accounts(", 1)[0]
        self.assertIn("journal_opening_balance", save)
        self.assertIn("sequence_unavailable", save)
        self.assertIn("array('draft', 'revise')", save)
        self.assertIn("$source_type === 'opening_balance' ? 'approved' : 'draft'", post)
        self.assertIn("where('status', $required_status)", post)

    def test_controller_submits_opening_balance_to_the_configured_flow(self):
        controller = CONTROLLER.read_text()
        submit = controller.split("public function submit_approval(", 1)[1].split("private function finance_transaction_start_approval(", 1)[0]
        method = controller.split("private function finance_opening_balance_start_approval(", 1)[1].split("public function lookup(", 1)[0]
        self.assertIn("'opening_balances'", submit)
        self.assertIn("finance_opening_balance_start_approval", submit)
        self.assertIn("finance_begining_balance", method)
        self.assertIn("effective_steps", method)
        self.assertIn("approval_steps_missing", method)
        self.assertIn("'fin_journal_entry'", method)

    def test_revise_save_resubmits_the_opening_balance(self):
        controller = CONTROLLER.read_text()
        form = controller.split("private function finance_journal_form(", 1)[1].split("private function finance_journal_values(", 1)[0]
        self.assertIn("$was_revise", form)
        self.assertIn("finance_opening_balance_start_approval", form)
        self.assertIn("array('draft', 'revise')", form)

    def test_list_shows_submit_post_and_revision_reason(self):
        index = INDEX.read_text()
        self.assertIn("submit_approval", index)
        self.assertIn("isOpening", index)
        self.assertIn("row.status==='approved'", index)
        self.assertIn("revise_note", index)
        self.assertIn("finance_tx_action_submit_approval", index)

    def test_list_actions_use_the_three_dot_menu(self):
        index = INDEX.read_text()
        self.assertIn("data-journal-actions-toggle", index)
        self.assertIn("data-journal-actions-menu", index)
        self.assertIn('aria-haspopup="menu"', index)
        self.assertIn('role="menuitem"', index)
        self.assertNotIn('class="me-3 font-semibold text-indigo-700"', index)
        revise_block = index.split("if(row.status==='draft'||row.status==='revise'){", 1)[1].split("} else if(row.status==='approved')", 1)[0]
        self.assertIn("row.status==='draft'", revise_block)
        self.assertNotIn("row.status==='revise'&&row.status==='draft'", revise_block)

    def test_form_reports_the_exact_line_and_column(self):
        form = FORM.read_text()
        controller = CONTROLLER.read_text()
        validate = controller.split("private function finance_journal_validate(", 1)[1].split("private function finance_journal_menu_code(", 1)[0]
        for key in (
            "_account_required",
            "_currency_invalid",
            "_rate_invalid",
            "_debit_invalid",
            "_credit_invalid",
            "_amount_required",
            "_base_rate_invalid",
        ):
            with self.subTest(key=key):
                self.assertIn(key, validate)
        self.assertIn("finance_journal_line_error_", form)
        self.assertIn("journal_lines_required", validate)
        self.assertIn("aria-invalid", form)

    def test_list_shows_the_user_who_created_the_journal(self):
        journal = JOURNAL.read_text()
        index = INDEX.read_text()
        self.assertIn("created_by_name", journal)
        self.assertIn("join('app_user u'", journal)
        self.assertIn("u.full_name", journal)
        self.assertIn("created_by_name", index)
        for locale in LOCALES:
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_created_by']", content)

    def test_list_formats_debit_and_names_the_pending_approver(self):
        journal = JOURNAL.read_text()
        index = INDEX.read_text()
        self.assertIn("current_approver_name", journal)
        self.assertIn("app_approval_request ar", journal)
        self.assertIn("ar.current_approver_user_id", journal)
        self.assertIn("function formatAmount", index)
        self.assertIn("toLocaleString('en-US'", index)
        self.assertIn("minimumFractionDigits: 2", index)
        self.assertIn("stateStyles", index)
        self.assertIn("finance_approver_label", index)
        self.assertIn("labels.approver", index)
        self.assertIn("current_approver_name", index)
        self.assertIn("pendingUnassigned", index)
        for locale in LOCALES:
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_approver_label']", content)
                self.assertIn("$lang['finance_pending_approver_unassigned']", content)

    def test_approval_details_use_grouped_currency_amounts(self):
        controller = CONTROLLER.read_text()
        self.assertIn("finance_approval_amount", controller)
        self.assertIn("number_format((float) $amount, 2, '.', ',')", controller)
        self.assertIn("$subject = 'Opening Balance';", controller)
        self.assertIn("'Opening Balance ' . (string) $record['journal_number'] . ' · ' . $this->finance_approval_amount($record['debit_total'])", controller)

    def test_opening_balance_labels_exist_in_all_locales(self):
        keys = (
            "finance_status_approval_submitted",
            "finance_status_approval_steps_missing",
            "finance_status_not_submittable",
            "finance_status_sequence_unavailable",
            "finance_journal_error_journal_lines_required",
            "finance_journal_line_error_account_required",
            "finance_journal_line_error_amount_required",
        )
        for locale in LOCALES:
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                for key in keys:
                    self.assertIn("$lang['" + key + "']", content)


if __name__ == "__main__":
    unittest.main()
