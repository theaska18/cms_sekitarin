from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
STORE = ROOT / "web/application/libraries/Approval_workflow_store.php"


class ApprovalSelfApprovalContractTests(unittest.TestCase):
    def test_requester_steps_are_auto_approved(self):
        source = STORE.read_text(encoding="utf-8")
        for marker in (
            "auto_approve_requester_steps",
            "Auto-approved: requester is the configured approver.",
            "$current_resolution['user_id'] === (int) $requester_user_id",
            "'decision' => 'approved'",
            "app_approval_action",
        ):
            self.assertIn(marker, source)
        self.assertGreaterEqual(source.count("auto_approve_requester_steps("), 3)

    def test_submit_and_decide_share_the_auto_approval_rule(self):
        source = STORE.read_text(encoding="utf-8")
        submit = source.split("public function submit(", 1)[1].split("private function auto_approve_requester_steps(", 1)[0]
        decide = source.split("public function decide(", 1)[1].split("private function finalize_source(", 1)[0]
        self.assertIn("auto_approve_requester_steps(", submit)
        self.assertIn("auto_approve_requester_steps(", decide)
        self.assertIn("$requester_user_id", submit)
        self.assertIn("$request['requester_user_id']", decide)
        self.assertIn("$new_status === 'approved'", submit)
        self.assertIn("finalize_source(", submit)
        self.assertIn("finalize_source(", decide)


if __name__ == "__main__":
    unittest.main()
