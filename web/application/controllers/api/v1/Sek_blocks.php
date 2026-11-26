<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Account-level blocking, scoped by X-Account-Id. */
class Sek_blocks extends Sek_api_base
{
    public function index()
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->account_context(TRUE);
        if ($account === NULL) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'POST') {
            $this->create_block($account);
            return;
        }
        $data = array();
        foreach ($this->sek_social_model->list_blocks((int) $account['account_id']) as $row) {
            $data[] = array(
                'account_id' => (int) $row['blocked_account_id'],
                'handle' => (string) $row['handle'],
                'display_name' => (string) $row['display_name'],
                'type_code' => (string) $row['type_code'],
                'avatar_media_id' => $row['avatar_media_id'] !== NULL ? (int) $row['avatar_media_id'] : NULL,
                'reason' => $row['reason'],
                'created_at' => $this->utc($row['created_at']),
            );
        }
        $this->api_response->ok(array('data' => $data));
    }

    public function show($account_id = 0)
    {
        if (!$this->require_method('DELETE')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->account_context(TRUE);
        if ($account === NULL) {
            return;
        }
        $this->sek_social_model->remove_block((int) $account['account_id'], (int) $account_id);
        $this->api_response->ok(array('status' => 'unblocked'));
    }

    private function create_block($account)
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $target_id = isset($body['account_id']) ? (int) $body['account_id'] : 0;
        if ($target_id <= 0) {
            $this->api_response->error('validation_error', 'account_id wajib diisi.', 422, array('account_id' => 'Wajib diisi.'));
            return;
        }
        if ($target_id === (int) $account['account_id']) {
            $this->api_response->error('unprocessable', 'Tidak dapat memblokir akun sendiri.', 422);
            return;
        }
        $target = $this->sek_account_model->find($target_id);
        if (!is_array($target) || (string) $target['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return;
        }
        $reason = isset($body['reason']) ? trim((string) $body['reason']) : NULL;
        if ($reason !== NULL && mb_strlen($reason) > 255) {
            $this->api_response->error('validation_error', 'Alasan maksimal 255 karakter.', 422);
            return;
        }
        $this->sek_social_model->add_block((int) $account['account_id'], $target_id, $reason);
        $this->api_response->json(array('status' => 'blocked', 'account_id' => $target_id), 201);
    }
}
