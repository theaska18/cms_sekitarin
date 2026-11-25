from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
FINANCE = ROOT / "web" / "application" / "views" / "admin" / "finance"


class FinanceStatusColumnOrderContractTests(unittest.TestCase):
    def test_status_columns_come_first_after_the_action_column(self):
        views = (
            "transaction_index.php", "journal_index.php", "document_index.php",
            "payment_index.php", "transfer_index.php", "reconciliation_index.php",
            "integration_index.php",
        )
        for name in views:
            content = (FINANCE / name).read_text(encoding="utf-8")
            with self.subTest(view=name):
                header = content.split("<thead", 1)[1].split("</thead>", 1)[0]
                action_at = header.index("action_header(")
                action_end = header.index("?>", action_at) + 2
                status_at = header.index("finance_column_status" if "finance_column_status" in header else "finance_tx_column_status")
                self.assertLess(action_at, status_at)
                self.assertEqual(1, header[action_end:status_at].count("<th"))

                columns = content.split("columns:", 1)[1]
                data_marks = [match for match in (
                    columns.find("{data: null"), columns.find("{data:null"),
                ) if match >= 0]
                self.assertTrue(data_marks, name)
                action_at = min(data_marks)
                status_at = columns.index("{data: 'status'") if "{data: 'status'" in columns else columns.index("{data:'status'")
                self.assertLess(action_at, status_at)
                self.assertLess(columns[:status_at].count("{data:"), 2, name)

    def test_generic_resource_status_column_is_moved_to_the_front(self):
        view = (FINANCE / "resource_index.php").read_text(encoding="utf-8")
        self.assertIn("$has_status_column = FALSE", view)
        self.assertIn("array_unshift($resource_columns, $status_column)", view)
        self.assertIn("foreach ($resource_columns as $column)", view)
        self.assertIn("order: [[<?= (int) $resource_order_index ?>", view)


if __name__ == "__main__":
    unittest.main()
