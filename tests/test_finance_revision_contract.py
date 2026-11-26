from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
WEB = ROOT / "web"
APP = WEB / "application"
MIGRATION = ROOT / "database" / "migrations" / "20261126000100_add_finance_revision.sql"
LOCALES = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")


class FinanceRevisionContractTests(unittest.TestCase):
    def test_revision_schema_migration_and_base_schema(self):
        migration = MIGRATION.read_text(encoding="utf-8")
        schema = (ROOT / "database" / "schema.sql").read_text(encoding="utf-8")
        for marker in (
            "`request_kind`", "`replacement_id`",
            "`revision_of_id`", "`revision_reason`", "`revised_on`",
            "idx_fin_sales_invoice_revision_of",
            "idx_fin_purchase_bill_revision_of",
        ):
            with self.subTest(marker=marker):
                self.assertIn(marker, migration)
                self.assertIn(marker, schema)

    def test_store_marks_sources_revised_and_clones_replacement(self):
        store = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        self.assertIn("public function revision_available()", store)
        self.assertIn("'request_kind' => $kind", store)
        self.assertIn("create_revision_document(", store)
        self.assertIn("'status' => 'revised'", store)
        self.assertIn("'revision_of_id' => (int) $source_id", store)
        self.assertIn("'replacement_id' => $replacement_id", store)
        self.assertIn("$kind === 'revise' ? 'revision_pending' : 'cancel_pending'", store)

    def test_approval_workflow_routes_revision_requests(self):
        approval = (APP / "libraries" / "Approval_workflow_store.php").read_text(encoding="utf-8")
        self.assertIn("in_array($request_action, array('cancel', 'revise'), TRUE) ? $request_action : 'submit'", approval)
        self.assertIn("in_array($request['request_action'], array('cancel', 'revise'), TRUE)", approval)

    def test_revise_endpoint_and_route(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        routes = (APP / "config" / "routes.php").read_text(encoding="utf-8")
        self.assertIn("public function revise_request()", controller)
        self.assertIn("'revise'", controller)
        self.assertIn("admin/finance/revise_request", routes)
        self.assertIn("'fin_sales_invoice' => array('fin_sales_invoices')", controller)
        self.assertIn("'fin_purchase_bill' => array('fin_purchase_bills')", controller)

    def test_action_menu_and_dialog(self):
        view = (APP / "views" / "admin" / "finance" / "document_index.php").read_text(encoding="utf-8")
        footer = (APP / "views" / "admin" / "footer.php").read_text(encoding="utf-8")
        script = (WEB / "assets" / "js" / "finance-list.js").read_text(encoding="utf-8")
        self.assertIn("data-finance-revise-open", view)
        self.assertIn("'approved'||row.status==='posted'", view.replace(" ", ""))
        self.assertIn("finance-revise-dialog", footer)
        self.assertIn("/admin/finance/revise_request", footer)
        self.assertIn("data-finance-revise-open", script)
        self.assertIn("reviseDialogBody", script)

    def test_revision_statuses_exist_in_every_locale(self):
        for locale in LOCALES:
            text = (APP / f"language/{locale}/finance_lang.php").read_text(encoding="utf-8")
            for key in (
                "finance_revise_action",
                "finance_revise_dialog_title",
                "finance_revise_dialog_body",
                "finance_revise_reason",
                "finance_revise_submit",
                "finance_tx_state_revised",
                "finance_tx_state_revision_pending",
                "finance_revision_of",
                "finance_revision_replacement",
                "finance_status_revision_submitted",
                "finance_status_revision_auto_approved",
                "finance_status_revision_invalid",
            ):
                with self.subTest(locale=locale, key=key):
                    self.assertIn(f"$lang['{key}']", text)

    def test_revision_filters_and_view_links(self):
        panel = (APP / "libraries" / "Finance_filter_panel.php").read_text(encoding="utf-8")
        view = (APP / "views" / "admin" / "finance" / "document_view.php").read_text(encoding="utf-8")
        self.assertIn("'revised' =>", panel)
        self.assertIn("'revision_pending' =>", panel)
        self.assertIn("finance_document_revision_of", view)
        self.assertIn("finance_document_replacement", view)

    def test_revision_chain_is_followed_by_the_integration_api(self):
        store = (APP / "libraries" / "Finance_integration_store.php").read_text(encoding="utf-8")
        api = (APP / "controllers" / "Finance_api.php").read_text(encoding="utf-8")
        self.assertIn("public function latest_document($kind, $tenant_id, $document_id)", store)
        self.assertIn("->where('revision_of_id', $current)", store)
        self.assertIn("$store->latest_document($kind, $tenant_id, $draft_id)", api)
        self.assertIn("'revision_pending' => 'Document revision is awaiting approval.'", api)
        self.assertIn("'revised' => 'Document was revised; cancel the replacement draft first.'", api)

    def test_cancelling_a_revised_submission_checks_the_replacement(self):
        store = (APP / "libraries" / "Finance_integration_store.php").read_text(encoding="utf-8")
        method = store.split("public function cancel_linked_cancelled(", 1)[1].split("public function ", 1)[0]
        self.assertIn("$latest = $this->latest_document($kind, $tenant_id, $document_id);", method)
        self.assertIn("$document_id = (int) $latest['id'];", method)

    def test_revision_repoints_the_integration_inbox(self):
        store = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        self.assertIn("private function sync_revision_inbox(", store)
        self.assertIn("$this->sync_revision_inbox($tenant_id, (string) $cancellation['source_type'], $source_id, (int) $replacement_id);", store)
        self.assertIn("'fin_sales_invoice' => array('table' => 'fin_sales_invoice_inbox', 'id' => 'sales_invoice_id')", store)

    def test_replacement_draft_unlocks_amount_for_integration_documents(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        self.assertIn(
            "$integration_locked = !$is_new && !empty($record['integration_number']) && empty($record['revision_of_id']);",
            controller,
        )


if __name__ == "__main__":
    unittest.main()
