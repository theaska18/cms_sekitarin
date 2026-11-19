from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"
FIN = APP / "views" / "admin" / "finance"


class FinanceUiContractTests(unittest.TestCase):
    def test_report_pages_have_one_date_and_export_control(self):
        source = (FIN / "report_index.php").read_text(encoding="utf-8")
        self.assertIn("'export'=>FALSE", source)
        self.assertIn("data-finance-report-export", source)
        self.assertIn("finance_filter_panel->render", source)
        self.assertNotIn("finance_report_run", source)
        self.assertNotIn('<form method="get" action="<?= html_escape($current_path) ?>"', source)

    def test_cancellation_history_has_custom_copy_summary_and_badges(self):
        source = (FIN / "report_index.php").read_text(encoding="utf-8")
        for marker in (
            "finance_cancellation_history_intro", "finance_cancellation_history_empty",
            "pending_count", "approved_count", "rejected_count",
            "cancellationStatuses", "finance-cell-wrap",
            "financeLocalTimeHtml",
        ):
            self.assertIn(marker, source)
        store = (APP / "libraries" / "Finance_report_store.php").read_text(encoding="utf-8")
        self.assertIn("_count", store)
        theme = (ROOT / "web/assets/css/admin-theme.css").read_text(encoding="utf-8")
        self.assertIn("td.finance-cell-wrap", theme)
        self.assertIn(".finance-cell-truncate", theme)
        for name in ("transaction_index.php", "journal_index.php", "payment_index.php",
                     "reconciliation_index.php", "document_index.php"):
            source = (FIN / name).read_text(encoding="utf-8")
            self.assertIn("finance-cell-truncate", source, name)

    def test_action_menu_and_cancel_dialog_are_localized_and_use_shared_confirm(self):
        script = (ROOT / "web/assets/js/finance-list.js").read_text(encoding="utf-8")
        footer = (APP / "views/admin/footer.php").read_text(encoding="utf-8")
        self.assertIn("window.financeActionLabels", footer)
        self.assertIn("cancelDialogBody", script)
        self.assertIn("data-web-confirm", script)
        self.assertNotIn("window.confirm(", script)
        self.assertIn("data-finance-cancel-body", footer)

    def test_delete_and_cancel_actions_respect_update_permission(self):
        script = (ROOT / "web/assets/js/finance-list.js").read_text(encoding="utf-8")
        self.assertIn("data-finance-can-update", script)
        for name in ("transaction_index.php", "document_index.php", "payment_index.php",
                     "journal_index.php", "transfer_index.php", "reconciliation_index.php"):
            source = (FIN / name).read_text(encoding="utf-8")
            self.assertIn("data-finance-can-update", source, name)

    def test_filter_panel_export_and_placeholders_are_localized(self):
        partial = (FIN / "partials/filter_panel.php").read_text(encoding="utf-8")
        panel = (APP / "libraries/Finance_filter_panel.php").read_text(encoding="utf-8")
        self.assertIn("finance_filter_export", partial)
        self.assertIn("finance_filter_min", partial)
        self.assertIn("finance_filter_max", partial)
        self.assertIn("'export' => TRUE", panel)

    def test_integration_rows_use_badges_dates_and_lighter_actions(self):
        source = (FIN / "integration_index.php").read_text(encoding="utf-8")
        self.assertIn("financeLocalTimeHtml", source)
        self.assertIn("rounded-md border px-2 py-0.5", source)
        self.assertIn("border-indigo-300", source)

    def test_filter_panel_markup_stays_balanced(self):
        source = (FIN / "partials/filter_panel.php").read_text(encoding="utf-8")
        self.assertEqual(source.count("<div"), source.count("</div>"))

    def test_cancellation_history_fits_desktop_report_shell(self):
        source = (FIN / "report_index.php").read_text(encoding="utf-8")
        theme = (ROOT / "web/assets/css/admin-theme.css").read_text(encoding="utf-8")
        self.assertIn('data-finance-layout="cancellation"', source)
        self.assertIn('[data-finance-layout="cancellation"]', theme)
        self.assertIn("@media (min-width: 1280px)", theme)

    def test_long_forms_use_sticky_action_bars(self):
        for name in ("journal_form.php", "reconciliation_form.php"):
            self.assertIn("sticky bottom-0", (FIN / name).read_text(encoding="utf-8"), name)


if __name__ == "__main__":
    unittest.main()
