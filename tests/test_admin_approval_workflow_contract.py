from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AdminApprovalWorkflowContractTests(unittest.TestCase):
    def test_hierarchy_uses_tenant_scoped_hr_org_parent_relationships(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = (APP / "controllers/Admin_hierarchy.php").read_text()
        self.assertIn("private function would_cycle_org", store)
        self.assertIn("public function set_parent_org", store)
        self.assertIn("'org_id'=>$org", store)
        self.assertIn("'parent_org'=>$parent", store)
        self.assertIn("get('hr_org')", store)
        self.assertIn("o.org_id, o.parent_org, o.org_code, o.org_name", store)
        self.assertIn("public function hierarchy_available()", store)
        self.assertIn("$store->hierarchy_available()", controller)

    def test_hierarchy_root_creation_uses_property_style_form_and_add_permission(self):
        controller = (APP / "controllers/Admin_hierarchy.php").read_text()
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        listing = (APP / "views/admin/hierarchy/index.php").read_text()
        form = (APP / "views/admin/hierarchy/new.php").read_text()
        self.assertIn("function hierarchy_new", controller)
        self.assertIn("hr_hierarchy_add", controller)
        self.assertIn("create_root_org", controller)
        self.assertIn('href="/admin/hr/hierarchy/new"', listing)
        self.assertIn("hierarchy_can_add", listing)
        self.assertIn('name="<?= html_escape($csrf_token_name) ?>"', form)
        self.assertIn('name="org_code"', form)
        self.assertIn('name="org_name"', form)
        self.assertIn("'parent_org' => NULL", store)
        self.assertIn("'tenant_id' => $this->tenant", store)
        self.assertIn("'create_on' => date('Y-m-d H:i:s')", store)
        self.assertIn("set_parent_org", controller)
        self.assertNotIn("set_manager", controller)
        self.assertNotIn("get('hr_employee_hierarchy')", store)

    def test_hierarchy_view_renders_tenant_relationships_as_an_org_chart(self):
        view = (APP / "views/admin/hierarchy/index.php").read_text()
        self.assertIn("$children_by_parent[$parent_id][] = $org_id", view)
        self.assertIn("$root_ids[] = $org_id", view)
        self.assertIn("$render_node($child_id, $level + 1)", view)
        self.assertIn('class="hierarchy-tree-table"', view)
        self.assertIn("$labels['hierarchy_legend']", view)
        self.assertIn("$rendered_ids[$org_id]", view)
        css = (ROOT / "tailwind/input.css").read_text()
        self.assertIn(".org-chart li > ul > li::before", css)
        self.assertIn(".org-chart li > ul::before", css)
        self.assertIn(".org-chart-card", css)
        self.assertIn("html_escape($name)", view)

    def test_workflow_submission_and_decision_enforce_step_and_tenant(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        self.assertIn("public function submit($request_type, $subject, $details)", store)
        self.assertIn("public function decide($request_id, $decision, $note)", store)
        self.assertIn("'request_type'=>$request_type", store)
        self.assertIn("'requester_employee_id'", store)
        self.assertIn("'status'=>$request_status", store)
        self.assertIn("'status'=>'needs_assignment'", store)
        self.assertIn("'approver_user_id'=>$this->user", store)
        self.assertIn("$new_status=$decision==='rejected'?'rejected':'approved'", store)
        self.assertIn("'status'=>'approved'", store)
        self.assertIn("'status'=>'auto_approved'", store)

    def test_controller_actions_require_access_and_post(self):
        cases = {
            "Admin_hierarchy.php": ("hierarchy_save", "hr_hierarchy_update"),
            "Admin_approvalFlow.php": ("approvalFlow_save", "hr_approval_flow_add"),
            "Admin_approval.php": ("approval_submit", "sys_approval_add"),
        }
        for filename, (method, permission) in cases.items():
            source = (APP / "controllers" / filename).read_text()
            body = source.split("public function " + method, 1)[1]
            self.assertIn("has_admin_access_code('" + permission + "')", body)
            self.assertRegex(body, r"\$this->input->method\(TRUE\)\s*!==\s*'POST'")
        approval = (APP / "controllers/Admin_approval.php").read_text()
        decide = approval.split("public function approval_decide", 1)[1]
        self.assertIn("has_admin_access_code('sys_approval_update')", decide)
        self.assertIn("$this->input->method(TRUE)!=='POST'", decide)

    def test_migration_creates_all_workflow_tables_and_is_non_destructive_on_rollback(self):
        migration = (APP / "migrations/20261112000200_create_hr_approval_workflow.php").read_text()
        for table in ("app_approval_flow", "app_approval_flow_step", "app_approval_request", "app_approval_action"):
            self.assertIn("'" + table + "'", migration)
        self.assertNotIn("hr_employee_hierarchy", migration)
        self.assertIn("Retain approval history", migration)
        self.assertNotRegex(migration, r"function down\(\).*?drop_table")

    def test_approval_submission_creates_temporary_approver_link_notification(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = (APP / "controllers/Admin_approval.php").read_text()
        migration = (APP / "migrations/20261112000300_create_approval_link_tokens.php").read_text()
        self.assertIn("app_approval_link_token", migration)
        self.assertIn("random_bytes(32)", store)
        self.assertIn("hash('sha256'", store)
        self.assertIn("expires_at", store)
        self.assertIn("app_notification", store)
        self.assertIn("approval_token", controller)
        self.assertIn("decide_approval_token", store)
        self.assertIn("$this->input->method(TRUE)==='POST'", controller)

    def test_approval_notifies_next_approver_after_intermediate_approval(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        decide = store.split("public function decide($request_id, $decision, $note)", 1)[1].split("public function users()", 1)[0]
        self.assertIn("$this->resolve_approver(", decide)
        self.assertIn("$this->deliver_approval(", decide)
        self.assertIn("'current_approver_user_id'", decide)
        self.assertIn("create_approval_token", store)
        self.assertIn("notify_approver", store)

    def test_approval_flow_list_and_add_form_are_separate_pages(self):
        listing = (APP / "views/admin/approval_flow/index.php").read_text()
        form = (APP / "views/admin/approval_flow/add.php").read_text()
        controller = (APP / "controllers/Admin_approvalFlow.php").read_text()
        self.assertNotIn('action="/admin/hr/approvalFlow/save"', listing)
        self.assertIn('name="search"', listing)
        self.assertIn('name="per_page"', listing)
        self.assertIn('Navigasi halaman', listing)
        self.assertIn('/admin/hr/approvalFlow/edit/', listing)
        self.assertIn('/admin/hr/approvalFlow/delete/', listing)
        self.assertIn('<dialog', listing)
        self.assertIn("public function approvalFlow_edit", controller)
        self.assertIn("public function approvalFlow_delete", controller)
        self.assertIn("count_flows", controller)
        self.assertIn("$store->flows($search, $per_page", controller)
        self.assertIn('/admin/hr/approvalFlow/add', listing)
        self.assertIn('<form method="post"', form)
        self.assertIn("public function approvalFlow_add()", controller)
        self.assertIn("'hr_approval_flow_add'", controller)
        self.assertIn("'approval_flow', '/admin/hr/approvalFlow/add'", controller)
        self.assertIn("'approval_flow', '/admin/hr/approvalFlow/edit/'", controller)
        property_form = (APP / "views/admin/property/form.php").read_text()
        migration = (APP / "migrations/20261112000200_create_hr_approval_workflow.php").read_text()
        for marker in ('rounded-t-2xl rounded-b-none', 'sm:p-8', 'space-y-6', 'sticky bottom-0', 'bg-white/10', 'border-t border-slate-100'):
            self.assertIn(marker, form)
            self.assertIn(marker, property_form)
        self.assertIn("'flow_name' => array('type' => 'VARCHAR', 'constraint' => 120)", migration)
        self.assertIn('name="flow_name" type="text" required maxlength="120"', form)
        self.assertIn("'request_type' => array('type' => 'VARCHAR', 'constraint' => 64)", migration)
        self.assertIn('name="request_type" type="text" required maxlength="64"', form)
        self.assertIn('data-approval-step-list', form)
        self.assertIn('data-add-approval-step', form)
        self.assertIn('data-approver-type', form)
        self.assertIn('data-organization-search', form)
        self.assertIn('data-employee-search', form)
        self.assertNotIn('<select', form)
        self.assertIn('text-base', form)
        self.assertIn('sm:text-sm', form)
        self.assertIn('focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100', form)

    def test_routes_remain_generic_without_workflow_specific_routes(self):
        routes = (APP / "config/routes.php").read_text()
        for literal in ("admin/hr/hierarchy", "admin/hr/approvalFlow", "admin/system/approval"):
            self.assertNotIn("$route['" + literal + "']", routes)
        self.assertIn("$route['admin/(:any)/(:any)/(:any)']", routes)

    def test_hierarchy_member_duplicates_are_rejected_and_job_titles_are_saved(self):
        controller = (APP / "controllers/Admin_hierarchy.php").read_text()
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        form = (APP / "views/admin/hierarchy/new.php").read_text()
        self.assertIn("$errors[] = 'members_duplicate'", controller)
        self.assertIn("isset($unique_members[(int)$employee_id])", controller)
        self.assertIn("members[<?= (int)$index ?>][job_title]", form)
        self.assertIn("members[<?= (int)$index ?>][employee_id]", form)
        self.assertIn("data-member-job-title", form)
        self.assertIn("$data['job_title']=$member['job_title']", store)
        self.assertIn("$row['job_title']=$member['job_title']", store)
        self.assertIn("m.job_title", store)

    def test_approval_errors_are_queued_and_admin_can_route_to_an_employee(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = (APP / "controllers/Admin_approval.php").read_text()
        view = (APP / "views/admin/approval/errors.php").read_text()
        approval_view = (APP / "views/admin/approval/index.php").read_text()
        migration = (APP / "migrations/20261116000100_add_dynamic_approval_routing.php").read_text()
        self.assertIn("public function approval_errors()", controller)
        self.assertIn("public function approval_error_data()", controller)
        self.assertIn("assign_approval_error", controller)
        self.assertIn("public function assign_approval_error", store)
        self.assertIn("needs_assignment", store)
        self.assertIn("insert_approval_error", store)
        self.assertIn("'app_approval_error'", migration)
        self.assertIn("serverSide:true", view)
        self.assertIn("data-approval-error-form", view)
        self.assertIn("data-error-employee-search", view)
        self.assertIn("$approval_error_count", approval_view)

    def test_email_approval_confirmation_renders_inside_admin_shell(self):
        controller = (APP / "controllers/Admin_approval.php").read_text()
        view = (APP / "views/admin/approval/confirm.php").read_text()
        token_branch = controller.split("if ($token !== NULL)", 1)[1].split("$available=", 1)[0]
        self.assertIn("render_admin('admin/approval/confirm'", token_branch)
        self.assertNotIn("<!doctype html>", token_branch.lower())
        self.assertIn("csrf_token_name", view)
        self.assertIn("approval_confirmation_token", view)
        self.assertIn("Konfirmasi keputusan", view)

    def test_approval_notifications_only_offer_a_view_link_before_decision(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        notifications = (ROOT / "web/application/views/admin/notifications/index.php").read_text()
        controller = (APP / "controllers/Admin_approval.php").read_text()
        confirm = (APP / "views/admin/approval/confirm.php").read_text()
        self.assertIn("'view'", store)
        self.assertIn('"\\nLihat: "', store)
        self.assertNotIn('"\\nSetuju: "', store)
        self.assertNotIn('"\\nTidak setuju: "', store)
        self.assertIn("notifications_approval_view", notifications)
        self.assertIn("$token_decision==='view'", controller)
        self.assertIn("value=\"rejected\"", confirm)
        self.assertIn("value=\"approved\"", confirm)

    def test_dynamic_approval_step_types_resolve_at_request_time(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        flow_form = (APP / "views/admin/approval_flow/add.php").read_text()
        request_form = (APP / "views/admin/approval/index.php").read_text()
        migration = (APP / "migrations/20261116000100_add_dynamic_approval_routing.php").read_text()
        for step_type in ("superior", "organization", "employee"):
            self.assertIn("'" + step_type + "'", store)
            self.assertIn('value="' + step_type + '"', flow_form)
        self.assertIn("function resolve_approver(", store)
        self.assertIn("data-request-type-search-url", request_form)
        self.assertNotIn('name="flow_id"', request_form)
        self.assertIn("if ((int)$previous_org_id>0)", store)
        self.assertIn("$this->hierarchy_org($parent_id)", store)
        self.assertIn("$step['approver_type']==='organization'", store)
        self.assertIn("'request_type'", migration)
        self.assertIn("'current_approver_user_id'", migration)

    def test_requester_sees_own_approval_history_and_unapproved_step_checklist(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = (APP / "controllers/Admin_approval.php").read_text()
        view = (APP / "views/admin/approval/index.php").read_text()
        self.assertIn("public function count_my_approval_requests($search = '')", store)
        self.assertIn("public function my_approval_requests($search = '', $limit = 10", store)
        self.assertIn("function approval_my_request_data()", controller)
        self.assertIn("serverSide:true", view)
        self.assertNotIn("my_approval_requests(30)", controller)
        self.assertIn("r.requester_user_id'=>$this->user", store)
        self.assertIn("'approval_my_requests_available'=>$available", controller)
        self.assertIn('id="my-approval-requests-heading"', view)
        self.assertIn("data:'approval_steps'", view)
        self.assertIn("assigned_approver_name", store)
        self.assertIn("statusIcons", view)
        self.assertIn("statusIcon(value)", view)
        self.assertNotIn("Riwayat 30 pengajuan", view)
        self.assertIn("needs_assignment:'Menunggu penetapan admin'", view)
        self.assertIn('Checklist persetujuan', view)


if __name__ == "__main__":
    unittest.main()
