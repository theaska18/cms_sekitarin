<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** User reports against accounts, reviewed later in the CMS. */
class Sek_reports extends Sek_api_base
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
            $this->create_report();
            return;
        }

        $data = array();
        foreach ($this->sek_social_model->list_reports($this->api_auth->user_id()) as $row) {
            $data[] = array(
                'report_id' => (int) $row['report_id'],
                'target_account_id' => (int) $row['target_account_id'],
                'category' => (string) $row['category'],
                'description' => $row['description'],
                'status' => (string) $row['status'],
                'created_at' => $this->utc($row['created_at']),
                'reviewed_at' => $this->utc($row['reviewed_at']),
                'target' => array(
                    'handle' => (string) $row['handle'],
                    'display_name' => (string) $row['display_name'],
                    'type_code' => (string) $row['type_code'],
                ),
            );
        }
        $this->api_response->ok(array('data' => $data));
    }

    private function create_report()
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $target_id = isset($body['target_account_id']) ? (int) $body['target_account_id'] : 0;
        $category = isset($body['category']) ? strtolower(trim((string) $body['category'])) : '';
        $description = isset($body['description']) ? trim((string) $body['description']) : NULL;
        $categories = array('spam', 'abuse', 'fraud', 'inappropriate', 'other');

        $errors = array();
        if ($target_id <= 0) {
            $errors['target_account_id'] = 'Wajib diisi.';
        }
        if (!in_array($category, $categories, TRUE)) {
            $errors['category'] = 'Kategori tidak valid.';
        }
        if ($description !== NULL && mb_strlen($description) > 1000) {
            $errors['description'] = 'Maksimal 1000 karakter.';
        }
        if ($errors) {
            $this->api_response->error('validation_error', 'Data laporan tidak valid.', 422, $errors);
            return;
        }

        $target = $this->sek_account_model->find($target_id);
        if (!is_array($target) || (string) $target['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return;
        }
        if ((int) $target['user_id'] === (int) $this->api_auth->user_id()) {
            $this->api_response->error('unprocessable', 'Tidak dapat melaporkan akun sendiri.', 422);
            return;
        }
        if (!$this->api_auth->rate_limit('report|' . $this->api_auth->user_id(), 10, 86400, 3600)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak laporan hari ini.', 429);
            return;
        }

        $report_id = $this->sek_social_model->create_report(
            $this->api_auth->user_id(),
            $target_id,
            $category,
            $description
        );
        $this->api_response->json(array(
            'report' => array(
                'report_id' => $report_id,
                'target_account_id' => $target_id,
                'category' => $category,
                'status' => 'open',
            ),
        ), 201);
    }
}
