<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Staff management for business accounts. */
class Sek_members extends Sek_api_base
{
    public function index($account_id = 0)
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->accessible_account((int) $account_id);
        if ($account === NULL) {
            return;
        }
        $role = $this->sek_account_model->member_role((int) $account['account_id'], $this->api_auth->user_id());

        if (strtoupper((string) $this->input->method(TRUE)) === 'GET') {
            $data = array();
            foreach ($this->sek_account_model->members((int) $account['account_id']) as $row) {
                $data[] = $this->member_payload($row);
            }
            $this->api_response->ok(array('data' => $data));
            return;
        }

        if ($role !== 'owner') {
            $this->api_response->error('forbidden', 'Hanya owner yang dapat menambah anggota.', 403);
            return;
        }
        $this->add_member($account);
    }

    public function show($account_id = 0, $user_id = 0)
    {
        if (!$this->require_method(array('PATCH', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->accessible_account((int) $account_id);
        if ($account === NULL) {
            return;
        }
        if ($this->sek_account_model->member_role((int) $account['account_id'], $this->api_auth->user_id()) !== 'owner') {
            $this->api_response->error('forbidden', 'Hanya owner yang dapat mengubah anggota.', 403);
            return;
        }
        $member = $this->sek_account_model->member_row((int) $account['account_id'], (int) $user_id);
        if (!is_array($member)) {
            $this->api_response->error('not_found', 'Anggota tidak ditemukan.', 404);
            return;
        }
        if ((string) $member['role'] === 'owner') {
            $this->api_response->error('unprocessable', 'Owner tidak dapat diubah atau dihapus.', 422);
            return;
        }

        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->sek_account_model->remove_member((int) $account['account_id'], (int) $user_id);
            $this->api_response->ok(array('status' => 'removed'));
            return;
        }

        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $new_role = isset($body['role']) ? strtolower(trim((string) $body['role'])) : (string) $member['role'];
        if (!in_array($new_role, array('manager', 'staff'), TRUE)) {
            $this->api_response->error('validation_error', 'Role harus manager atau staff.', 422);
            return;
        }
        $status = isset($body['status']) ? strtolower(trim((string) $body['status'])) : (string) $member['status'];
        if (!in_array($status, array('active', 'removed'), TRUE)) {
            $this->api_response->error('validation_error', 'Status tidak valid.', 422);
            return;
        }
        $this->db->where('account_id', (int) $account['account_id'])
            ->where('user_id', (int) $user_id)
            ->update('sek_account_member', array('role' => $new_role, 'status' => $status));
        $fresh = $this->sek_account_model->member_detail((int) $account['account_id'], (int) $user_id);
        $this->api_response->ok(array('member' => $this->member_payload($fresh)));
    }

    private function add_member($account)
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $email = isset($body['email']) ? strtolower(trim((string) $body['email'])) : '';
        $role = isset($body['role']) ? strtolower(trim((string) $body['role'])) : 'staff';
        $errors = array();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email tidak valid.';
        }
        if (!in_array($role, array('manager', 'staff'), TRUE)) {
            $errors['role'] = 'Role harus manager atau staff.';
        }
        if ($errors) {
            $this->api_response->error('validation_error', 'Data anggota tidak valid.', 422, $errors);
            return;
        }
        $user = $this->sek_user_model->find_by_email($email);
        if (!is_array($user) || (string) $user['status'] !== 'active') {
            $this->api_response->error('not_found', 'Pengguna dengan email tersebut tidak ditemukan.', 404);
            return;
        }
        if ((int) $user['user_id'] === (int) $account['user_id']) {
            $this->api_response->error('unprocessable', 'Owner sudah terdaftar pada akun ini.', 422);
            return;
        }
        $this->sek_account_model->upsert_member((int) $account['account_id'], (int) $user['user_id'], $role);
        $member = $this->sek_account_model->member_detail((int) $account['account_id'], (int) $user['user_id']);
        $this->api_response->json(array('member' => $this->member_payload($member)), 201);
    }

    private function accessible_account($account_id)
    {
        $account = $this->sek_account_model->find((int) $account_id);
        if (!is_array($account) || (string) $account['status'] === 'archived') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return NULL;
        }
        if ($this->sek_account_model->member_role((int) $account_id, $this->api_auth->user_id()) === NULL) {
            $this->api_response->error('forbidden', 'Anda tidak memiliki akses ke akun ini.', 403);
            return NULL;
        }
        return $account;
    }

    private function member_payload($row)
    {
        return array(
            'user_id' => (int) $row['user_id'],
            'email' => (string) $row['email'],
            'display_name' => (string) $row['display_name'],
            'role' => (string) $row['role'],
            'status' => (string) $row['status'],
            'created_at' => $this->utc($row['created_at']),
        );
    }
}
