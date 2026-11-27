<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Message history, sending, editing, and soft deletion. */
class Sek_messages extends Sek_api_base
{
    public function index($conversation_id = 0)
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        if ($this->conversation_membership((int) $conversation_id) === NULL) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'POST') {
            $this->send_message((int) $conversation_id);
            return;
        }
        $this->list_messages((int) $conversation_id);
    }

    public function show($message_id = 0)
    {
        if (!$this->require_method(array('PATCH', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $message = $this->sek_chat_model->message((int) $message_id);
        if (!is_array($message)) {
            $this->api_response->error('not_found', 'Pesan tidak ditemukan.', 404);
            return;
        }
        if ($this->conversation_membership((int) $message['conversation_id']) === NULL) {
            return;
        }

        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->delete_message($message);
            return;
        }
        $this->edit_message($message);
    }

    private function list_messages($conversation_id)
    {
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(100, $limit) : 50;
        $before = $this->input->get('before_id', TRUE);
        $after = $this->input->get('after_id', TRUE);
        $before = $before !== NULL && $before !== '' ? (int) $before : NULL;
        $after = $after !== NULL && $after !== '' ? (int) $after : NULL;

        $rows = $this->sek_chat_model->messages($conversation_id, $limit + 1, $before, $after);
        $has_more = count($rows) > $limit;
        if ($has_more) {
            $rows = $after !== NULL ? array_slice($rows, 0, $limit) : array_slice($rows, 1);
        }
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int) $row['message_id'];
        }
        if ($ids) {
            $this->sek_chat_model->mark_delivered($conversation_id, $this->api_auth->user_id(), $ids);
        }
        $counts = $this->sek_chat_model->receipt_counts($ids);
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->message_payload($row, $counts, $this->api_auth->user_id());
        }
        $this->api_response->ok(array('data' => $data, 'has_more' => $has_more));
    }

    private function send_message($conversation_id)
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $type = isset($body['type']) ? strtolower(trim((string) $body['type'])) : 'text';
        if (!in_array($type, array('text', 'image', 'file', 'audio', 'location'), TRUE)) {
            $this->api_response->error('validation_error', 'Tipe pesan tidak valid.', 422);
            return;
        }

        $payload = array('type' => $type);
        if ($type === 'text') {
            $text = isset($body['body']) ? trim((string) $body['body']) : '';
            if ($text === '' || mb_strlen($text) > 4000) {
                $this->api_response->error('validation_error', 'Isi pesan 1–4000 karakter.', 422, array('body' => 'Wajib diisi.'));
                return;
            }
            $payload['body'] = $text;
        } elseif (in_array($type, array('image', 'file', 'audio'), TRUE)) {
            $media_id = isset($body['media_id']) ? (int) $body['media_id'] : 0;
            $media = $this->sek_media_model->find($media_id);
            if (!is_array($media)
                || (int) $media['owner_user_id'] !== (int) $this->api_auth->user_id()
                || (string) $media['status'] !== 'ready'
                || !in_array((string) $media['purpose'], array('chat', 'document'), TRUE)) {
                $this->api_response->error('validation_error', 'media_id tidak valid.', 422);
                return;
            }
            $payload['media_id'] = $media_id;
            if (isset($body['body'])) {
                $payload['body'] = trim((string) $body['body']);
            }
        } else {
            if (!$this->valid_lat(isset($body['lat']) ? $body['lat'] : NULL)
                || !$this->valid_lng(isset($body['lng']) ? $body['lng'] : NULL)) {
                $this->api_response->error('validation_error', 'lat/lng tidak valid.', 422);
                return;
            }
            $label = isset($body['label']) ? trim((string) $body['label']) : NULL;
            if ($label !== NULL && mb_strlen($label) > 255) {
                $this->api_response->error('validation_error', 'Label lokasi maksimal 255 karakter.', 422);
                return;
            }
            $payload['latitude'] = (float) $body['lat'];
            $payload['longitude'] = (float) $body['lng'];
            $payload['location_label'] = $label;
        }

        if (!empty($body['reply_to_message_id'])) {
            $reply = $this->sek_chat_model->message((int) $body['reply_to_message_id']);
            if (!is_array($reply) || (int) $reply['conversation_id'] !== $conversation_id) {
                $this->api_response->error('validation_error', 'reply_to_message_id tidak valid.', 422);
                return;
            }
            $payload['reply_to_message_id'] = (int) $body['reply_to_message_id'];
        }

        $sender_account_id = $this->sender_account_id();
        $message_id = $this->sek_chat_model->create_message(
            $conversation_id,
            $this->api_auth->user_id(),
            $sender_account_id,
            $payload
        );
        if ($message_id === NULL) {
            $this->api_response->error('server_error', 'Pesan gagal disimpan.', 500);
            return;
        }
        $message = $this->sek_chat_model->message($message_id);
        $payload_out = $this->message_payload($message, array(), $this->api_auth->user_id());
        $this->sek_realtime->publish($conversation_id, 'message.created', $payload_out);

        $recipients = array();
        foreach ($this->sek_chat_model->members($conversation_id) as $member) {
            if ((int) $member['user_id'] !== (int) $this->api_auth->user_id()) {
                $recipients[] = (int) $member['user_id'];
            }
        }
        if ($recipients) {
            $preview = $type === 'text'
                ? mb_substr((string) $payload['body'], 0, 160)
                : ($type === 'image' ? 'Mengirim gambar' : ($type === 'audio' ? 'Mengirim audio' : ($type === 'location' ? 'Membagikan lokasi' : 'Mengirim file')));
            $title = (string) $this->api_auth->user()['display_name'];
            if ($sender_account_id !== NULL) {
                $sender_account = $this->sek_account_model->find($sender_account_id);
                if (is_array($sender_account) && trim((string) $sender_account['display_name']) !== '') {
                    $title = (string) $sender_account['display_name'];
                }
            }
            $this->sek_notifier->notify_users(
                $recipients,
                'chat_message',
                $title,
                $preview,
                array('conversation_id' => $conversation_id, 'message_id' => $message_id, 'message_type' => $type)
            );
        }
        $this->api_response->json(array('message' => $payload_out), 201);
    }

    private function edit_message($message)
    {
        if ((int) $message['sender_user_id'] !== (int) $this->api_auth->user_id()) {
            $this->api_response->error('forbidden', 'Hanya pengirim yang dapat mengubah pesan.', 403);
            return;
        }
        if ((string) $message['type'] !== 'text' || !empty($message['deleted_at'])) {
            $this->api_response->error('unprocessable', 'Pesan ini tidak dapat diubah.', 422);
            return;
        }
        if (strtotime((string) $message['created_at']) + 900 < time()) {
            $this->api_response->error('unprocessable', 'Batas waktu ubah pesan (15 menit) sudah lewat.', 422);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $text = isset($body['body']) ? trim((string) $body['body']) : '';
        if ($text === '' || mb_strlen($text) > 4000) {
            $this->api_response->error('validation_error', 'Isi pesan 1–4000 karakter.', 422);
            return;
        }
        $this->sek_chat_model->update_message((int) $message['message_id'], $text);
        $fresh = $this->sek_chat_model->message((int) $message['message_id']);
        $payload = $this->message_payload($fresh, array(), $this->api_auth->user_id());
        $this->sek_realtime->publish((int) $message['conversation_id'], 'message.updated', $payload);
        $this->api_response->ok(array('message' => $payload));
    }

    private function delete_message($message)
    {
        $is_sender = (int) $message['sender_user_id'] === (int) $this->api_auth->user_id();
        $membership = $this->sek_chat_model->membership((int) $message['conversation_id'], $this->api_auth->user_id());
        $is_owner = is_array($membership) && (string) $membership['role'] === 'owner';
        if (!$is_sender && !$is_owner) {
            $this->api_response->error('forbidden', 'Tidak dapat menghapus pesan ini.', 403);
            return;
        }
        $this->sek_chat_model->delete_message((int) $message['message_id']);
        $payload = array(
            'message_id' => (int) $message['message_id'],
            'conversation_id' => (int) $message['conversation_id'],
            'deleted_at' => $this->utc(gmdate('Y-m-d H:i:s')),
        );
        $this->sek_realtime->publish((int) $message['conversation_id'], 'message.deleted', $payload);
        $this->api_response->ok(array('status' => 'deleted', 'message' => $payload));
    }

    private function conversation_membership($conversation_id)
    {
        $membership = $this->sek_chat_model->membership($conversation_id, $this->api_auth->user_id());
        if (!is_array($membership)) {
            $this->api_response->error('not_found', 'Percakapan tidak ditemukan.', 404);
            return NULL;
        }
        return $membership;
    }

    private function sender_account_id()
    {
        $raw = trim((string) $this->input->get_request_header('X-Account-Id', TRUE));
        if ($raw !== '' && ctype_digit($raw)
            && $this->sek_account_model->member_role((int) $raw, $this->api_auth->user_id()) !== NULL) {
            return (int) $raw;
        }
        return $this->active_account_id();
    }
}
