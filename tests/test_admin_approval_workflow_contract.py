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
        self.assertIn("public function submit($flow_code, $subject, $details, $source_type = NULL, $source_id = NULL, $requester_user_id = NULL)", store)
        self.assertIn("public function decide($request_id, $decision, $note)", store)
        self.assertIn("'flow_code'=>$flow_code", store)
        self.assertIn("'requester_employee_id'", store)
        self.assertIn("'status'=>$request_status", store)
        self.assertIn("'status'=>'needs_assignment'", store)
        self.assertIn("'approver_user_id'=>$this->user", store)
        self.assertIn("$new_status=$decision==='rejected'?'rejected':($decision==='revise'?'revise':'approved')", store)
        self.assertIn("$new_status='pending'", store)
        self.assertIn("'status'=>'submitted'", store)

    def test_superior_uses_parent_head_when_requester_leads_an_organization(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        superior = store.split("if ($type === 'superior')", 1)[1].split(
            "return array('ok'=>FALSE,'code'=>'invalid_approver_type'", 1
        )[0]
        self.assertIn("'head_employee_id'=>$employee_id", superior)
        self.assertIn("count($headed_orgs)===1", superior)
        self.assertIn("A leader is approved by the head of the parent organization.", superior)
        self.assertIn("$org = $headed_orgs[0]", superior)
        self.assertIn("count($headed_orgs)>1", superior)

    def test_flow_without_steps_is_auto_approved_without_error_queue(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        form = (APP / "views/admin/approval_flow/add.php").read_text()
        script = (ROOT / "web/assets/js/approval-step-editor.js").read_text()
        submit = store.split("public function submit($flow_code, $subject, $details, $source_type = NULL, $source_id = NULL, $requester_user_id = NULL)", 1)[1].split(
            "private function create_approval_token", 1
        )[0]
        auto_branch = submit.split("if (!$steps)", 1)[1].split("$step=isset($steps[0])", 1)[0]
        self.assertIn("'status'=>'approved'", auto_branch)
        self.assertIn("'current_step'=>0", auto_branch)
        self.assertIn("'status'=>'auto_approved'", auto_branch)
        self.assertNotIn("insert_approval_error", auto_branch)
        self.assertIn("normalize_steps($steps, TRUE)", store)
        self.assertIn("private function normalize_steps($steps, $allow_empty = FALSE)", store)
        self.assertIn('data-step-editor-allow-empty="1"', form)
        self.assertIn("data-step-editor-allow-empty", script)
        self.assertIn("rows.length <= 1 && !allowEmpty", script)

    def test_error_queue_requires_sys_approval_error_access(self):
        controller = (APP / "controllers/Admin_approval.php").read_text()
        view = (APP / "views/admin/approval/index.php").read_text()
        manifest = (APP / "views/admin/menu_manifest/hr.xml").read_text()
        self.assertIn('sys_approval_error', manifest)
        self.assertIn("$can_manage_errors = $this->has_admin_access_code('sys_approval_error');", controller)
        self.assertIn("'approval_can_manage_errors'=>$can_manage_errors", controller)
        self.assertIn("!empty($approval_can_manage_errors)", view)
        for method in ("approval_errors", "approval_error_data", "approval_employee_search"):
            with self.subTest(method=method):
                body = controller.split("public function " + method, 1)[1]
                self.assertIn("has_admin_access_code('sys_approval_error')", body)

    def test_request_form_requires_submit_approval_access(self):
        controller = (APP / "controllers/Admin_approval.php").read_text()
        view = (APP / "views/admin/approval/index.php").read_text()
        manifest = (APP / "views/admin/menu_manifest/hr.xml").read_text()
        self.assertIn("sys_approval_submit_approval", manifest)
        self.assertIn("$can_add = $this->has_admin_access_code('sys_approval_submit_approval');", controller)
        self.assertIn("$hide_request_form = !$can_add;", controller)
        self.assertIn("'approval_hide_request_form'=>$hide_request_form", controller)
        self.assertIn("empty($approval_hide_request_form)", view)
        submit = controller.split("public function approval_submit", 1)[1]
        self.assertIn("!$this->has_admin_access_code('sys_approval_submit_approval')", submit)

    def test_controller_actions_require_access_and_post(self):
        cases = {
            "Admin_hierarchy.php": ("hierarchy_save", "hr_hierarchy_update"),
            "Admin_approvalFlow.php": ("approvalFlow_save", "hr_approval_flow_update"),
            "Admin_approval.php": ("approval_submit", "sys_approval_submit_approval"),
        }
        for filename, (method, permission) in cases.items():
            source = (APP / "controllers" / filename).read_text()
            body = source.split("public function " + method, 1)[1]
            self.assertIn("has_admin_access_code('" + permission + "')", body)
            self.assertRegex(body, r"\$this->input->method\(TRUE\)\s*!==\s*'POST'")
        approval = (APP / "controllers/Admin_approval.php").read_text()
        decide = approval.split("public function approval_decide", 1)[1]
        self.assertNotIn("has_admin_access_code('sys_approval_update')", decide)
        self.assertIn("has_admin_menu_code('sys_approval')", decide)
        self.assertIn("$this->input->method(TRUE)!=='POST'", decide)

    def test_schema_creates_all_workflow_tables(self):
        schema = (ROOT / "database/schema.sql").read_text()
        for table in ("app_approval_flow", "app_approval_flow_step", "app_approval_request", "app_approval_action"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", schema)

    def test_approval_submission_creates_temporary_approver_link_notification(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = (APP / "controllers/Admin_approval.php").read_text()
        self.assertIn("CREATE TABLE `app_approval_link_token`", (ROOT / "database/schema.sql").read_text())
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

    def test_approval_flow_list_is_template_overrides_without_add_or_delete(self):
        listing = (APP / "views/admin/approval_flow/index.php").read_text()
        form = (APP / "views/admin/approval_flow/add.php").read_text()
        step_editor = (APP / "views/admin/approval_flow/_step_editor.php").read_text()
        controller = (APP / "controllers/Admin_approvalFlow.php").read_text()
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        schema = (ROOT / "database/schema.sql").read_text()
        flow = schema.split("CREATE TABLE `sys_approval_flow`", 1)[1].split("ENGINE=", 1)[0]
        for column in ("flow_code", "flow_name", "active_flag", "created_by"):
            with self.subTest(column=column):
                self.assertIn("`" + column + "`", flow)
        self.assertNotIn("`request_type`", flow)
        self.assertNotIn('/admin/hr/approvalFlow/add', listing)
        self.assertNotIn('/admin/hr/approvalFlow/delete', listing)
        self.assertNotIn('<dialog', listing)
        self.assertIn('name="search"', listing)
        self.assertIn('name="per_page"', listing)
        self.assertIn('Navigasi halaman', listing)
        self.assertIn('/admin/hr/approvalFlow/edit/', listing)
        self.assertIn('Sumber tahap', listing)
        self.assertIn("public function approvalFlow_index", controller)
        self.assertIn("public function approvalFlow_edit", controller)
        self.assertIn("public function approvalFlow_save", controller)
        self.assertNotIn("public function approvalFlow_add", controller)
        self.assertNotIn("public function approvalFlow_delete", controller)
        self.assertIn("count_flows", controller)
        self.assertIn("$store->flows($search, $per_page", controller)
        self.assertIn("save_override", controller)
        self.assertIn("template_flow", controller)
        self.assertIn('<form method="post"', form)
        self.assertIn('name="flow_code"', form)
        self.assertIn("admin/approval_flow/_step_editor", form)
        self.assertIn("approval-step-editor.js", form)
        self.assertNotIn('name="flow_name"', form)
        self.assertNotIn('name="request_type"', form)
        for marker in ('data-approval-step-list', 'data-add-approval-step', 'data-approver-type', 'data-organization-search', 'data-employee-search'):
            self.assertIn(marker, step_editor)
        self.assertIn("sys_approval_flow", store)
        self.assertIn("sys_approval_flow_menu", store)
        self.assertIn("public function effective_steps", store)
        self.assertIn("public function save_override", store)

    def test_approval_flow_info_panel_uses_dark_mode_background(self):
        form = (APP / "views/admin/approval_flow/add.php").read_text()
        css = (ROOT / "web/assets/css/admin-theme.css").read_text()
        self.assertIn("approval-flow-info-panel", form)
        self.assertNotIn("bg-slate-50/60", form)
        self.assertIn(".approval-flow-info-panel", css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell .approval-flow-info-panel', css)

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
        self.assertIn("CREATE TABLE `app_approval_error`", (ROOT / "database/schema.sql").read_text())
        self.assertIn("public function approval_errors()", controller)
        self.assertIn("public function approval_error_data()", controller)
        self.assertIn("assign_approval_error", controller)
        self.assertIn("public function assign_approval_error", store)
        self.assertIn("needs_assignment", store)
        self.assertIn("insert_approval_error", store)
        self.assertIn("serverSide:true", view)
        self.assertIn("data-approval-error-form", view)
        self.assertIn("data-error-employee-search", view)
        self.assertIn("tableLanguage.aria={paginate:pagingLabels}", view)
        self.assertIn('viewBox="0 0 20 20"', view)
        css = (ROOT / "web/assets/css/admin-theme.css").read_text()
        self.assertIn("#approval-error-table_wrapper div.dt-layout-row:not(.dt-layout-table)", css)
        self.assertIn("#approval-error-table_wrapper .dt-paging-button", css)
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

    def test_approval_token_detail_names_the_requester_and_supports_revision(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = (APP / "controllers/Admin_approval.php").read_text()
        confirm = (APP / "views/admin/approval/confirm.php").read_text()
        token_request = store.split("public function approval_token_request", 1)[1].split("public function decide_approval_token", 1)[0]
        token_decide = store.split("public function decide_approval_token", 1)[1].split("public function decide(", 1)[0]
        self.assertIn("requester_name", token_request)
        self.assertIn("submitted_by_name", token_request)
        self.assertIn("'app_user'", token_request)
        self.assertIn("'revise'", token_decide)
        self.assertIn("$note", token_decide)
        self.assertIn("Dibuat oleh", confirm)
        self.assertIn("Disubmit oleh", confirm)
        self.assertIn("$approval_confirmation['requester_name']", confirm)
        self.assertIn("$approval_confirmation['submitted_by_name']", confirm)
        self.assertIn("dark:bg-slate-900/60", confirm)
        self.assertIn("dark:text-slate-100", confirm)
        self.assertIn("data-local-time", confirm)
        self.assertIn("Intl.DateTimeFormat", confirm)
        self.assertIn("timeZoneName: 'short'", confirm)
        self.assertIn('name="approval_decision" value="revise"', confirm)
        self.assertIn('name="note"', confirm)
        self.assertIn("Alasan revisi wajib diisi.", confirm)
        self.assertIn("$posted_note", controller)
        self.assertIn("in_array($posted_decision,array('approved','rejected','revise'),TRUE)", controller)

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
        self.assertIn("parse_url($matches[1])", notifications)
        self.assertIn("'url' => $matches[1]", notifications)
        self.assertIn("$token_decision==='view'", controller)
        self.assertIn("value=\"rejected\"", confirm)
        self.assertIn("value=\"approved\"", confirm)

    def test_dynamic_approval_step_types_resolve_at_request_time(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        flow_form = (APP / "views/admin/approval_flow/_step_editor.php").read_text()
        request_form = (APP / "views/admin/approval/index.php").read_text()
        schema = (ROOT / "database/schema.sql").read_text()
        request = schema.split("CREATE TABLE `app_approval_request`", 1)[1].split("ENGINE=", 1)[0]
        flow_step = schema.split("CREATE TABLE `sys_approval_flow_step`", 1)[1].split("ENGINE=", 1)[0]
        for step_type in ("superior", "organization", "employee"):
            self.assertIn("'" + step_type + "'", store)
            self.assertIn('value="' + step_type + '"', flow_form)
        self.assertIn("function resolve_approver(", store)
        self.assertIn("data-flow-search-url", request_form)
        self.assertNotIn('name="flow_id"', request_form)
        self.assertIn("if ((int)$previous_org_id>0)", store)
        self.assertIn("$this->hierarchy_org($parent_id)", store)
        self.assertIn("$step['approver_type']==='organization'", store)
        self.assertIn("`current_approver_user_id`", request)
        self.assertIn("`approver_type`", flow_step)

    def test_requester_sees_own_approval_history_and_unapproved_step_checklist(self):
        store = (APP / "libraries/Approval_workflow_store.php").read_text()
        controller = (APP / "controllers/Admin_approval.php").read_text()
        view = (APP / "views/admin/approval/index.php").read_text()
        self.assertIn("public function count_my_approval_requests($search = '', $filters = array())", store)
        self.assertIn("public function my_approval_requests($search = '', $limit = 10", store)
        self.assertIn("finance_filter_panel->render", view)
        self.assertIn("finance_list_table->open('my-approval-history-table'", view)
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
