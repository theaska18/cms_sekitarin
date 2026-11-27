<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Saved contacts for the current user. */
class Sek_contacts extends Sek_api_base
{
    public function index()
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'POST') {
            $this->create_contact();
            return;
        }

        $blocked = $this->sek_account_model->blocked_account_ids($this->active_account_id());
        $data = array();
        foreach ($this->sek_social_model->list_contacts($this->api_auth->user_id()) as $row) {
            if (in_array((int) $row['account_id'], $blocked, TRUE)) {
                continue;
            }
            $data[] = $this->contact_payload($row);
        }
        $this->api_response->ok(array('data' => $data));
    }

    public function show($contact_id = 0)
    {
        if (!$this->require_method(array('PATCH', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $contact = $this->sek_social_model->find_contact((int) $contact_id, $this->api_auth->user_id());
        if (!is_array($contact)) {
            $this->api_response->error('not_found', 'Kontak tidak ditemukan.', 404);
            return;
        }

        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->sek_social_model->delete_contact((int) $contact_id, $this->api_auth->user_id());
            $this->api_response->ok(array('status' => 'deleted'));
            return;
        }

        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $alias = array_key_exists('alias', $body) ? trim((string) $body['alias']) : NULL;
        if ($alias !== NULL && mb_strlen($alias) > 80) {
            $this->api_response->error('validation_error', 'Alias maksimal 80 karakter.', 422);
            return;
        }
        $this->sek_social_model->update_contact((int) $contact_id, $alias);
        $this->api_response->ok(array('contact' => array(
            'contact_id' => (int) $contact['contact_id'],
            'account_id' => (int) $contact['account_id'],
            'alias' => $alias !== '' ? $alias : NULL,
        )));
    }

    private function create_contact()
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $account_id = isset($body['account_id']) ? (int) $body['account_id'] : 0;
        if ($account_id <= 0) {
            $this->api_response->error('validation_error', 'account_id wajib diisi.', 422, array('account_id' => 'Wajib diisi.'));
            return;
        }
        $alias = isset($body['alias']) ? trim((string) $body['alias']) : NULL;
        if ($alias !== NULL && mb_strlen($alias) > 80) {
            $this->api_response->error('validation_error', 'Alias maksimal 80 karakter.', 422);
            return;
        }

        $account = $this->sek_account_model->find($account_id);
        if (!is_array($account) || (string) $account['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return;
        }
        if ((int) $account['user_id'] === (int) $this->api_auth->user_id()) {
            $this->api_response->error('unprocessable', 'Tidak dapat menyimpan akun sendiri sebagai kontak.', 422);
            return;
        }
        $active = $this->active_account_id();
        if ($active !== NULL && $this->sek_account_model->blocked_between($active, $account_id)) {
            $this->api_response->error('forbidden', 'Akun ini diblokir.', 403);
            return;
        }

        $existing = $this->sek_social_model->contact_exists($this->api_auth->user_id(), $account_id);
        if (is_array($existing)) {
            $this->api_response->ok(array('contact' => array(
                'contact_id' => (int) $existing['contact_id'],
                'account_id' => (int) $existing['account_id'],
                'alias' => $existing['alias'],
            )), 200);
            return;
        }

        $contact_id = $this->sek_social_model->add_contact($this->api_auth->user_id(), $account_id, $alias);
        $this->api_response->json(array('contact' => array(
            'contact_id' => $contact_id,
            'account_id' => $account_id,
            'alias' => $alias !== '' ? $alias : NULL,
        )), 201);
    }

    private function contact_payload($row)
    {
        return array(
            'contact_id' => (int) $row['contact_id'],
            'account_id' => (int) $row['account_id'],
            'alias' => $row['alias'],
            'created_at' => $this->utc($row['created_at']),
            'account' => array(
                'account_id' => (int) $row['account_id'],
                'type_code' => (string) $row['type_code'],
                'handle' => (string) $row['handle'],
                'display_name' => (string) $row['display_name'],
                'bio' => $row['bio'],
                'avatar_media_id' => $row['avatar_media_id'] !== NULL ? (int) $row['avatar_media_id'] : NULL,
                'is_online' => $this->account_is_online($row),
                'verified' => !empty($row['verified_at']),
                'type_label' => (string) $row['label_id'],
            ),
        );
    }
}
