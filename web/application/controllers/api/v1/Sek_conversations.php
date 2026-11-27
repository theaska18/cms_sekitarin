<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Chat conversations, membership, read state, typing, and live location. */
class Sek_conversations extends Sek_api_base
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
            $this->create_conversation();
            return;
        }

        $data = array();
        foreach ($this->sek_chat_model->list_conversations($this->api_auth->user_id()) as $row) {
            $data[] = $this->conversation_payload($row, $this->api_auth->user_id(), FALSE);
        }
        $this->api_response->ok(array('data' => $data));
    }

    public function show($conversation_id = 0)
    {
        if (!$this->require_method(array('GET', 'PATCH', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'PATCH') {
            $this->update($conversation_id);
            return;
        }
        $conversation = $this->conversation_for((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->sek_chat_model->leave((int) $conversation_id, $this->api_auth->user_id());
            $this->api_response->ok(array('status' => 'left'));
            return;
        }
        $this->api_response->ok(array(
            'conversation' => $this->conversation_payload($conversation, $this->api_auth->user_id(), TRUE),
        ));
    }

    public function update($conversation_id = 0)
    {
        if (!$this->require_method('PATCH')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $conversation = $this->conversation_for((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        if ((string) $conversation['type'] !== 'group') {
            $this->api_response->error('unprocessable', 'Hanya grup yang dapat diubah.', 422);
            return;
        }
        $membership = $this->sek_chat_model->membership((int) $conversation_id, $this->api_auth->user_id());
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $fields = array();
        if (array_key_exists('title', $body)) {
            if ((string) $membership['role'] !== 'owner') {
                $this->api_response->error('forbidden', 'Hanya owner grup yang dapat mengubah nama grup.', 403);
                return;
            }
            $title = trim((string) $body['title']);
            if ($title === '' || mb_strlen($title) > 120) {
                $this->api_response->error('validation_error', 'Judul grup wajib (maks 120 karakter).', 422);
                return;
            }
            $fields['title'] = $title;
        }
        if (array_key_exists('avatar_media_id', $body)) {
            $media_id = (int) $body['avatar_media_id'];
            if ($media_id > 0 && !$this->sek_account_model->media_belongs_to($media_id, $this->api_auth->user_id())) {
                $this->api_response->error('validation_error', 'avatar_media_id tidak valid.', 422);
                return;
            }
            $fields['avatar_media_id'] = $media_id > 0 ? $media_id : NULL;
        }
        if (!$fields) {
            $this->api_response->error('validation_error', 'Tidak ada perubahan.', 422);
            return;
        }
        $this->sek_chat_model->update_conversation((int) $conversation_id, $fields);
        $fresh = $this->sek_chat_model->find((int) $conversation_id);
        $this->sek_realtime->publish((int) $conversation_id, 'conversation.updated', array(
            'conversation_id' => (int) $conversation_id,
            'title' => $fresh['title'],
            'avatar_media_id' => $fresh['avatar_media_id'] !== NULL ? (int) $fresh['avatar_media_id'] : NULL,
        ));
        $this->api_response->ok(array(
            'conversation' => $this->conversation_payload($fresh, $this->api_auth->user_id(), TRUE),
        ));
    }

    public function members($conversation_id = 0)
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $conversation = $this->conversation_for((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'GET') {
            $data = array();
            foreach ($this->sek_chat_model->members((int) $conversation_id) as $member) {
                $data[] = $this->member_payload($member);
            }
            $this->api_response->ok(array('data' => $data));
            return;
        }
        $this->add_member($conversation);
    }

    public function remove_member($conversation_id = 0, $user_id = 0)
    {
        if (!$this->require_method('DELETE')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $conversation = $this->conversation_for((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        $membership = $this->sek_chat_model->membership((int) $conversation_id, $this->api_auth->user_id());
        if ((string) $conversation['type'] !== 'group' || (string) $membership['role'] !== 'owner') {
            $this->api_response->error('forbidden', 'Hanya owner grup yang dapat menghapus anggota.', 403);
            return;
        }
        if ((int) $user_id === (int) $this->api_auth->user_id()) {
            $this->api_response->error('unprocessable', 'Gunakan endpoint keluar grup untuk diri sendiri.', 422);
            return;
        }
        $this->sek_chat_model->leave((int) $conversation_id, (int) $user_id);
        $this->sek_realtime->publish((int) $conversation_id, 'member.removed', array('user_id' => (int) $user_id));
        $this->api_response->ok(array('status' => 'removed', 'user_id' => (int) $user_id));
    }

    public function read($conversation_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $conversation = $this->conversation_for((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $message_id = isset($body['message_id']) ? (int) $body['message_id'] : 0;
        $message = $this->sek_chat_model->message($message_id);
        if (!is_array($message) || (int) $message['conversation_id'] !== (int) $conversation_id) {
            $this->api_response->error('validation_error', 'message_id tidak valid untuk percakapan ini.', 422);
            return;
        }
        $this->sek_chat_model->mark_read((int) $conversation_id, $this->api_auth->user_id(), $message_id);
        $this->sek_realtime->publish((int) $conversation_id, 'read.updated', array(
            'user_id' => (int) $this->api_auth->user_id(),
            'message_id' => $message_id,
        ), $this->api_auth->user_id());
        $this->api_response->ok(array('status' => 'read', 'message_id' => $message_id, 'unread_count' => 0));
    }

    public function typing($conversation_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $conversation = $this->conversation_for((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $typing = !array_key_exists('is_typing', $body) || $this->api_validation->bool_value($body['is_typing']);
        $this->sek_realtime->publish((int) $conversation_id, 'typing', array(
            'user_id' => (int) $this->api_auth->user_id(),
            'display_name' => (string) $this->api_auth->user()['display_name'],
            'is_typing' => $typing,
        ), $this->api_auth->user_id());
        $this->api_response->json(array('status' => 'ok'), 202);
    }

    public function live_location($conversation_id = 0)
    {
        if (!$this->require_method(array('GET', 'PUT', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $conversation = $this->conversation_for((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        $method = strtoupper((string) $this->input->method(TRUE));
        if ($method === 'GET') {
            $data = array();
            foreach ($this->sek_chat_model->live_locations((int) $conversation_id) as $row) {
                $data[] = array(
                    'user_id' => (int) $row['user_id'],
                    'display_name' => (string) $row['display_name'],
                    'lat' => (float) $row['lat'],
                    'lng' => (float) $row['lng'],
                    'accuracy_m' => $row['accuracy_m'] !== NULL ? (int) $row['accuracy_m'] : NULL,
                    'expires_at' => $this->utc($row['expires_at']),
                    'updated_at' => $this->utc($row['updated_at']),
                );
            }
            $this->api_response->ok(array('data' => $data));
            return;
        }
        if ($method === 'DELETE') {
            $this->sek_chat_model->delete_live_location((int) $conversation_id, $this->api_auth->user_id());
            $this->sek_realtime->publish((int) $conversation_id, 'live_location.stopped', array(
                'user_id' => (int) $this->api_auth->user_id(),
            ));
            $this->api_response->ok(array('status' => 'stopped'));
            return;
        }

        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        if (!$this->location_consent_granted()) {
            $this->api_response->error('forbidden', 'Persetujuan lokasi belum diberikan.', 403);
            return;
        }
        if (!$this->valid_lat(isset($body['lat']) ? $body['lat'] : NULL)
            || !$this->valid_lng(isset($body['lng']) ? $body['lng'] : NULL)) {
            $this->api_response->error('validation_error', 'lat/lng tidak valid.', 422);
            return;
        }
        $expires_in = isset($body['expires_in']) ? (int) $body['expires_in'] : 300;
        $expires_in = max(60, min(3600, $expires_in));
        $expires_at = gmdate('Y-m-d H:i:s', time() + $expires_in);
        $accuracy = isset($body['accuracy_m']) ? (int) $body['accuracy_m'] : NULL;
        $this->sek_chat_model->live_location_upsert(
            (int) $conversation_id,
            $this->api_auth->user_id(),
            (float) $body['lat'],
            (float) $body['lng'],
            $accuracy,
            $expires_at
        );
        $payload = array(
            'user_id' => (int) $this->api_auth->user_id(),
            'lat' => (float) $body['lat'],
            'lng' => (float) $body['lng'],
            'accuracy_m' => $accuracy,
            'expires_at' => $this->utc($expires_at),
        );
        $this->sek_realtime->publish((int) $conversation_id, 'live_location.updated', $payload);
        $this->api_response->ok(array('live_location' => $payload));
    }

    private function create_conversation()
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $type = isset($body['type']) ? strtolower(trim((string) $body['type'])) : '';
        if (!in_array($type, array('direct', 'group'), TRUE)) {
            $this->api_response->error('validation_error', 'type harus direct atau group.', 422);
            return;
        }

        if ($type === 'direct') {
            $target_user_id = 0;
            $target_account_id = isset($body['account_id']) ? (int) $body['account_id'] : 0;
            if ($target_account_id > 0) {
                $target_account = $this->sek_account_model->find($target_account_id);
                if (!is_array($target_account) || (string) $target_account['status'] !== 'active') {
                    $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
                    return;
                }
                $target_user_id = (int) $target_account['user_id'];
                $viewer_account = $this->viewer_account_id();
                if ($viewer_account !== NULL && $this->sek_account_model->blocked_between($viewer_account, $target_account_id)) {
                    $this->api_response->error('forbidden', 'Akun ini diblokir.', 403);
                    return;
                }
            } elseif (isset($body['user_id'])) {
                $target_user_id = (int) $body['user_id'];
                $target_user = $this->sek_user_model->find($target_user_id);
                if (!is_array($target_user) || (string) $target_user['status'] !== 'active') {
                    $this->api_response->error('not_found', 'Pengguna tidak ditemukan.', 404);
                    return;
                }
            }
            if ($target_user_id <= 0 || $target_user_id === (int) $this->api_auth->user_id()) {
                $this->api_response->error('validation_error', 'Tujuan percakapan tidak valid.', 422);
                return;
            }
            $existing = $this->sek_chat_model->find_direct($this->api_auth->user_id(), $target_user_id);
            if (is_array($existing)) {
                $this->sek_chat_model->add_member((int) $existing['conversation_id'], $this->api_auth->user_id());
                $this->api_response->ok(array(
                    'conversation' => $this->conversation_payload($existing, $this->api_auth->user_id(), TRUE),
                    'created' => FALSE,
                ));
                return;
            }
            $conversation_id = $this->sek_chat_model->create_conversation('direct', $this->api_auth->user_id(), NULL, array($target_user_id));
            if ($conversation_id === NULL) {
                $this->api_response->error('conflict', 'Percakapan gagal dibuat.', 409);
                return;
            }
            $conversation = $this->sek_chat_model->find($conversation_id);
            $this->api_response->json(array(
                'conversation' => $this->conversation_payload($conversation, $this->api_auth->user_id(), TRUE),
                'created' => TRUE,
            ), 201);
            return;
        }

        $title = isset($body['title']) ? trim((string) $body['title']) : '';
        if ($title === '' || mb_strlen($title) > 120) {
            $this->api_response->error('validation_error', 'title grup wajib (maks 120 karakter).', 422);
            return;
        }
        $member_ids = isset($body['member_user_ids']) && is_array($body['member_user_ids'])
            ? array_values(array_unique(array_map('intval', $body['member_user_ids'])))
            : array();
        $member_ids = array_values(array_filter($member_ids, function ($id) {
            return $id > 0;
        }));
        if (isset($body['member_account_ids']) && is_array($body['member_account_ids'])) {
            foreach (array_unique(array_map('intval', $body['member_account_ids'])) as $member_account_id) {
                if ($member_account_id <= 0) {
                    continue;
                }
                $member_account = $this->sek_account_model->find($member_account_id);
                if (!is_array($member_account) || (string) $member_account['status'] !== 'active') {
                    $this->api_response->error('validation_error', 'Akun anggota tidak valid.', 422);
                    return;
                }
                $member_ids[] = (int) $member_account['user_id'];
            }
            $member_ids = array_values(array_unique($member_ids));
        }
        if (!$member_ids || count($member_ids) > 99) {
            $this->api_response->error('validation_error', 'Pilih 1–99 anggota grup.', 422);
            return;
        }
        foreach ($member_ids as $member_id) {
            $member = $this->sek_user_model->find($member_id);
            if (!is_array($member) || (string) $member['status'] !== 'active') {
                $this->api_response->error('validation_error', 'Anggota ' . $member_id . ' tidak valid.', 422);
                return;
            }
        }
        $conversation_id = $this->sek_chat_model->create_conversation('group', $this->api_auth->user_id(), $title, $member_ids);
        if ($conversation_id === NULL) {
            $this->api_response->error('conflict', 'Grup gagal dibuat.', 409);
            return;
        }
        $conversation = $this->sek_chat_model->find($conversation_id);
        $this->api_response->json(array(
            'conversation' => $this->conversation_payload($conversation, $this->api_auth->user_id(), TRUE),
            'created' => TRUE,
        ), 201);
    }

    private function add_member($conversation)
    {
        if ((string) $conversation['type'] !== 'group') {
            $this->api_response->error('unprocessable', 'Hanya grup yang memiliki anggota tambahan.', 422);
            return;
        }
        $membership = $this->sek_chat_model->membership((int) $conversation['conversation_id'], $this->api_auth->user_id());
        if ((string) $membership['role'] !== 'owner') {
            $this->api_response->error('forbidden', 'Hanya owner grup yang dapat menambah anggota.', 403);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $user = NULL;
        if (isset($body['user_id'])) {
            $user = $this->sek_user_model->find((int) $body['user_id']);
        } elseif (isset($body['email'])) {
            $user = $this->sek_user_model->find_by_email((string) $body['email']);
        } elseif (isset($body['username']) || isset($body['handle'])) {
            $handle = $this->sek_account_model->normalize_handle(
                isset($body['username']) ? (string) $body['username'] : (string) $body['handle']
            );
            $account = $this->sek_account_model->find_by_handle($handle);
            if (is_array($account) && (string) $account['status'] === 'active') {
                $user = $this->sek_user_model->find((int) $account['user_id']);
            }
        }
        if (!is_array($user) || (string) $user['status'] !== 'active') {
            $this->api_response->error('not_found', 'Pengguna tidak ditemukan.', 404);
            return;
        }
        $active_members = $this->sek_chat_model->members((int) $conversation['conversation_id']);
        if (count($active_members) >= 100) {
            $this->api_response->error('unprocessable', 'Batas anggota grup tercapai.', 422);
            return;
        }
        $this->sek_chat_model->add_member((int) $conversation['conversation_id'], (int) $user['user_id']);
        $this->sek_realtime->publish((int) $conversation['conversation_id'], 'member.added', array(
            'user_id' => (int) $user['user_id'],
            'display_name' => (string) $user['display_name'],
        ));
        $member = $this->sek_chat_model->member_detail((int) $conversation['conversation_id'], (int) $user['user_id']);
        $this->api_response->json(array('member' => $this->member_payload($member)), 201);
    }

    private function conversation_for($conversation_id)
    {
        $conversation = $this->sek_chat_model->find($conversation_id);
        if (!is_array($conversation) || !is_array($this->sek_chat_model->membership($conversation_id, $this->api_auth->user_id()))) {
            $this->api_response->error('not_found', 'Percakapan tidak ditemukan.', 404);
            return NULL;
        }
        return $conversation;
    }

    private function conversation_payload($conversation, $user_id, $with_members)
    {
        $conversation_id = (int) $conversation['conversation_id'];
        $members = $this->sek_chat_model->members($conversation_id);
        $member_list = array();
        $peer = NULL;
        foreach ($members as $member) {
            if ($with_members) {
                $member_list[] = $this->member_payload($member);
            }
            if ((string) $conversation['type'] === 'direct'
                && (int) $member['user_id'] !== (int) $user_id && $peer === NULL) {
                $peer = array(
                    'user_id' => (int) $member['user_id'],
                    'account_id' => isset($member['personal_account_id']) && $member['personal_account_id'] !== NULL
                        ? (int) $member['personal_account_id'] : NULL,
                    'display_name' => (string) $member['display_name'],
                    'handle' => isset($member['personal_handle']) ? $member['personal_handle'] : NULL,
                    'avatar_media_id' => isset($member['personal_avatar_media_id']) && $member['personal_avatar_media_id'] !== NULL
                        ? (int) $member['personal_avatar_media_id'] : NULL,
                );
            }
        }
        $last = $this->sek_chat_model->last_message($conversation_id);
        $membership = $this->sek_chat_model->membership($conversation_id, $user_id);
        $unread = $membership !== NULL
            ? $this->sek_chat_model->unread_count($conversation_id, $user_id, (int) $membership['last_read_message_id'])
            : 0;

        return array(
            'conversation_id' => $conversation_id,
            'type' => (string) $conversation['type'],
            'title' => $conversation['title'],
            'avatar_media_id' => $conversation['avatar_media_id'] !== NULL ? (int) $conversation['avatar_media_id'] : NULL,
            'peer' => $peer,
            'member_count' => count($members),
            'members' => $with_members ? $member_list : NULL,
            'last_message' => is_array($last) ? $this->message_payload($last, array(), $user_id) : NULL,
            'unread_count' => $unread,
            'last_message_at' => $this->utc($conversation['last_message_at']),
            'created_at' => $this->utc($conversation['created_at']),
            'updated_at' => $this->utc($conversation['updated_at']),
        );
    }

    private function member_payload($member)
    {
        return array(
            'user_id' => (int) $member['user_id'],
            'account_id' => isset($member['personal_account_id']) && $member['personal_account_id'] !== NULL
                ? (int) $member['personal_account_id'] : NULL,
            'display_name' => (string) $member['display_name'],
            'handle' => isset($member['personal_handle']) ? $member['personal_handle'] : NULL,
            'avatar_media_id' => isset($member['personal_avatar_media_id']) && $member['personal_avatar_media_id'] !== NULL
                ? (int) $member['personal_avatar_media_id'] : NULL,
            'role' => (string) $member['role'],
            'last_read_message_id' => $member['last_read_message_id'] !== NULL ? (int) $member['last_read_message_id'] : NULL,
            'joined_at' => $this->utc($member['joined_at']),
        );
    }

    private function location_consent_granted()
    {
        $row = $this->db->where('user_id', $this->api_auth->user_id())
            ->where('scope', 'location')
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get('sek_consent')
            ->row_array();
        return is_array($row) && (int) $row['granted'] === 1;
    }
}
