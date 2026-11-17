from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
GUARD = APP / "libraries/Finance_balance_guard.php"
TRANSACTION = APP / "libraries/Finance_transaction_store.php"
TRANSFER = APP / "libraries/Finance_transfer_store.php"
PAYMENT = APP / "libraries/Finance_payment_store.php"
JOURNAL = APP / "libraries/Finance_journal_store.php"


class FinanceBalanceGuardContractTests(unittest.TestCase):
    def test_guard_computes_posted_cash_balance_and_locks_accounts(self):
        guard = GUARD.read_text()
        self.assertIn("class Finance_balance_guard", guard)
        self.assertIn("FOR UPDATE", guard)
        self.assertIn("je.status = \\'posted\\'", guard)
        self.assertIn("SUM(jl.debit_amount - jl.credit_amount)", guard)
        self.assertIn("insufficient_balance", guard)
        self.assertIn("public function validate_journal_lines", guard)

    def test_expense_posting_validates_cash_balance(self):
        store = TRANSACTION.read_text()
        post = store.split("public function post(", 1)[1].split("public function cash_accounts(", 1)[0]
        self.assertIn("finance_balance_guard", post)
        self.assertIn("$record['transaction_type'] === 'expense'", post)
        self.assertIn("can_debit(", post)
        self.assertIn("$balance_check !== 'ok'", post)
        self.assertIn("return $balance_check", post)

    def test_transfer_posting_validates_source_balance(self):
        store = TRANSFER.read_text()
        post = store.split("public function post(", 1)[1].split("private function transfer_number_exists", 1)[0]
        self.assertIn("finance_balance_guard", post)
        self.assertIn("from_cash_account_id", post)
        self.assertIn("from_amount", post)
        self.assertIn("$balance_check !== 'ok'", post)
        self.assertIn("return $balance_check", post)

    def test_supplier_payment_validates_balance_but_customer_payment_does_not(self):
        store = PAYMENT.read_text()
        post = store.split("public function post(", 1)[1].split("public function lookup(", 1)[0]
        self.assertIn("if ($kind === 'supplier')", post)
        self.assertIn("can_debit(", post)
        self.assertIn("$balance_check !== 'ok'", post)
        self.assertIn("return $balance_check", post)

    def test_manual_journal_validates_cash_lines_but_opening_balance_is_exempt(self):
        store = JOURNAL.read_text()
        post = store.split("public function post(", 1)[1].split("public function lookup_accounts(", 1)[0]
        self.assertIn("if ($source_type === 'manual')", post)
        self.assertIn("validate_journal_lines(", post)
        self.assertNotIn("if ($source_type === 'opening_balance') {\n            $CI->load->library('finance_balance_guard')", post)

    def test_balance_failure_message_is_available_in_all_locales(self):
        for locale in ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german"):
            with self.subTest(locale=locale):
                content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
                self.assertIn("$lang['finance_status_insufficient_balance']", content)

    def test_lists_displays_insufficient_balance_status(self):
        for path in (
            "views/admin/finance/transaction_index.php",
            "views/admin/finance/transfer_index.php",
            "views/admin/finance/payment_index.php",
            "views/admin/finance/journal_index.php",
        ):
            with self.subTest(view=path):
                content = (APP / path).read_text()
                self.assertIn("insufficient_balance", content)


if __name__ == "__main__":
    unittest.main()
