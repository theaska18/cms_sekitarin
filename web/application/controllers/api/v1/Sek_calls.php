<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/**
 * Voice/video calls.
 *
 * Direct calls ring the callee (WebSocket + push, CallKit-friendly).
 * Group calls never ring: they only expose an "active call" marker on the
 * group conversation so members can join it from the chat.
 */
class Sek_calls extends Sek_api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('sek_jitsi');
        $this->load->model('sek_call_model');
    }

    public function index()
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'POST') {
            $this->start_direct();
            return;
        }
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(100, $limit) : 50;
        $before = (int) $this->input->get('before_id', TRUE);
        $this->sek_call_model->expire_stale();
        $this->sek_call_model->expire_abandoned();
        $rows = $this->sek_call_model->list_for_user(
            $this->api_auth->user_id(),
            $before,
            $limit
        );
        $has_more = count($rows) > $limit;
        if ($has_more) {
            $rows = array_slice($rows, 0, $limit);
        }
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->call_payload($row, $this->api_auth->user_id());
        }
        $this->api_response->ok(array('data' => $data, 'has_more' => $has_more));
    }

    public function show($call_id = 0)
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $call = $this->sek_call_model->for_user((int) $call_id, $this->api_auth->user_id());
        if (!is_array($call)) {
            $this->api_response->error('not_found', 'Panggilan tidak ditemukan.', 404);
            return;
        }
        $this->sek_call_model->expire_abandoned();
        $call = $this->sek_call_model->find((int) $call_id);
        if ((string) $call['status'] === 'ringing'
            && strtotime((string) $call['created_at']) + Sek_call_model::RING_TIMEOUT < time()) {
            $this->sek_call_model->update((int) $call_id, array(
                'status' => 'missed',
                'ended_at' => gmdate('Y-m-d H:i:s'),
            ));
            $call = $this->sek_call_model->find((int) $call_id);
        }
        $payload = array(
            'call' => $this->call_payload($call, $this->api_auth->user_id()),
        );
        if ((string) $call['status'] === 'answered') {
            $payload['jitsi'] = $this->jitsi_payload($call, $this->api_auth->user_id());
        }
        $this->api_response->ok($payload);
    }

    public function heartbeat($call_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $call = $this->sek_call_model->for_user((int) $call_id, $this->api_auth->user_id());
        if (!is_array($call)) {
            $this->api_response->error('not_found', 'Panggilan tidak ditemukan.', 404);
            return;
        }
        if (in_array((string) $call['status'], array('ringing', 'answered'), TRUE)) {
            $this->sek_call_model->touch((int) $call_id);
        }
        $this->api_response->ok(array(
            'call_id' => (int) $call_id,
            'status' => (string) $call['status'],
        ));
    }

    public function answer($call_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $call = $this->sek_call_model->find((int) $call_id);
        if (!is_array($call) || (string) $call['scope'] !== 'direct') {
            $this->api_response->error('not_found', 'Panggilan tidak ditemukan.', 404);
            return;
        }
        if ((int) $call['callee_user_id'] !== (int) $this->api_auth->user_id()) {
            $this->api_response->error('forbidden', 'Hanya penerima yang dapat menjawab.', 403);
            return;
        }
        if ((string) $call['status'] !== 'ringing') {
            $this->api_response->error('unprocessable', 'Panggilan ini tidak lagi berdering.', 422);
            return;
        }
        if (strtotime((string) $call['created_at']) + Sek_call_model::RING_TIMEOUT < time()) {
            $this->sek_call_model->update((int) $call_id, array(
                'status' => 'missed',
                'ended_at' => gmdate('Y-m-d H:i:s'),
            ));
            $this->api_response->error('call_expired', 'Panggilan sudah berakhir.', 410);
            return;
        }
        $this->sek_call_model->update((int) $call_id, array(
            'status' => 'answered',
            'answered_at' => gmdate('Y-m-d H:i:s'),
            'last_activity_at' => gmdate('Y-m-d H:i:s'),
        ));
        $call = $this->sek_call_model->find((int) $call_id);
        $payload = $this->call_payload($call, (int) $call['caller_user_id']);
        $this->sek_realtime->publish_user(
            (int) $call['caller_user_id'],
            'call.answered',
            $payload
        );
        $this->api_response->ok(array(
            'call' => $this->call_payload($call, $this->api_auth->user_id()),
            'jitsi' => $this->jitsi_payload($call, $this->api_auth->user_id()),
        ));
    }

    public function decline($call_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $call = $this->sek_call_model->find((int) $call_id);
        if (!is_array($call)) {
            $this->api_response->error('not_found', 'Panggilan tidak ditemukan.', 404);
            return;
        }
        $user_id = (int) $this->api_auth->user_id();
        if ((string) $call['scope'] === 'direct'
            && (int) $call['callee_user_id'] !== $user_id) {
            $this->api_response->error('forbidden', 'Hanya penerima yang dapat menolak.', 403);
            return;
        }
        if ((string) $call['status'] !== 'ringing') {
            $this->api_response->error('unprocessable', 'Panggilan ini tidak lagi berdering.', 422);
            return;
        }
        $this->sek_call_model->update((int) $call_id, array(
            'status' => 'declined',
            'ended_at' => gmdate('Y-m-d H:i:s'),
        ));
        $call = $this->sek_call_model->find((int) $call_id);
        $this->sek_realtime->publish_user(
            (int) $call['caller_user_id'],
            'call.ended',
            $this->call_payload($call, (int) $call['caller_user_id'])
        );
        $this->api_response->ok(array(
            'status' => 'declined',
            'call' => $this->call_payload($call, $user_id),
        ));
    }

    public function end($call_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $call = $this->sek_call_model->find((int) $call_id);
        if (!is_array($call)) {
            $this->api_response->error('not_found', 'Panggilan tidak ditemukan.', 404);
            return;
        }
        $user_id = (int) $this->api_auth->user_id();
        if ((string) $call['scope'] === 'group') {
            $this->end_group_call($call, $user_id);
            return;
        }
        if ((int) $call['caller_user_id'] !== $user_id && (int) $call['callee_user_id'] !== $user_id) {
            $this->api_response->error('forbidden', 'Anda bukan peserta panggilan ini.', 403);
            return;
        }
        if (in_array((string) $call['status'], array('ended', 'missed', 'canceled', 'declined'), TRUE)) {
            $this->api_response->ok(array(
                'status' => (string) $call['status'],
                'call' => $this->call_payload($call, $user_id),
            ));
            return;
        }
        $was_ringing = (string) $call['status'] === 'ringing';
        $is_caller = (int) $call['caller_user_id'] === $user_id;
        $status = 'ended';
        if ($was_ringing) {
            $status = $is_caller ? 'canceled' : 'declined';
        }
        $fields = array(
            'status' => $status,
            'ended_at' => gmdate('Y-m-d H:i:s'),
        );
        if (!$was_ringing && !empty($call['answered_at'])) {
            $fields['duration_seconds'] = max(
                0,
                strtotime((string) $fields['ended_at']) - strtotime((string) $call['answered_at'])
            );
        }
        $this->sek_call_model->update((int) $call_id, $fields);
        $call = $this->sek_call_model->find((int) $call_id);
        $other_user_id = $is_caller ? (int) $call['callee_user_id'] : (int) $call['caller_user_id'];
        $this->sek_realtime->publish_user(
            $other_user_id,
            'call.ended',
            $this->call_payload($call, $other_user_id)
        );
        $this->sek_notifier->push_users(
            array($other_user_id),
            $was_ringing ? 'Panggilan tak terjawab' : 'Panggilan berakhir',
            $this->peer_display_name($call, $other_user_id),
            array(
                'call_event' => 'cancel',
                'call_id' => (int) $call_id,
                'conversation_id' => $call['conversation_id'] !== NULL ? (int) $call['conversation_id'] : 0,
            )
        );
        if ($was_ringing && $is_caller) {
            $this->notify_missed($call);
        }
        $this->api_response->ok(array(
            'status' => $status,
            'call' => $this->call_payload($call, $user_id),
        ));
    }

    // ----- Group calls -----------------------------------------------------

    public function group($conversation_id = 0)
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $conversation = $this->group_conversation((int) $conversation_id);
        if ($conversation === NULL) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'GET') {
            $active = $this->sek_call_model->active_group_call((int) $conversation_id);
            $this->api_response->ok(array(
                'active_call' => is_array($active)
                    ? $this->group_call_payload($active, $this->api_auth->user_id())
                    : NULL,
            ));
            return;
        }

        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $kind = isset($body['kind']) ? strtolower(trim((string) $body['kind'])) : 'voice';
        $kind = $kind === 'video' ? 'video' : 'voice';
        $active = $this->sek_call_model->active_group_call((int) $conversation_id);
        if (!is_array($active)) {
            $call_id = $this->sek_call_model->create(array(
                'scope' => 'group',
                'caller_user_id' => $this->api_auth->user_id(),
                'caller_account_id' => $this->viewer_account_id(),
                'conversation_id' => (int) $conversation_id,
                'room' => $this->sek_jitsi->room_name(),
                'kind' => $kind,
                'status' => 'active',
            ));
            $active = $this->sek_call_model->find($call_id);
            $this->sek_realtime->publish(
                (int) $conversation_id,
                'call.started',
                $this->group_call_payload($active, $this->api_auth->user_id())
            );
        }
        $this->sek_call_model->join_participant(
            (int) $active['call_id'],
            $this->api_auth->user_id(),
            $this->viewer_account_id()
        );
        $this->publish_group_state($active);
        $active = $this->sek_call_model->find((int) $active['call_id']);
        $this->api_response->json(array(
            'active_call' => $this->group_call_payload($active, $this->api_auth->user_id()),
            'jitsi' => $this->jitsi_payload($active, $this->api_auth->user_id()),
        ), is_array($active) ? 200 : 404);
    }

    public function join($call_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $call = $this->sek_call_model->find((int) $call_id);
        if (!is_array($call) || (string) $call['scope'] !== 'group') {
            $this->api_response->error('not_found', 'Panggilan tidak ditemukan.', 404);
            return;
        }
        if ((string) $call['status'] !== 'active') {
            $this->api_response->error('unprocessable', 'Panggilan grup sudah berakhir.', 422);
            return;
        }
        $conversation = $this->group_conversation((int) $call['conversation_id']);
        if ($conversation === NULL) {
            return;
        }
        $this->sek_call_model->join_participant(
            (int) $call_id,
            $this->api_auth->user_id(),
            $this->viewer_account_id()
        );
        $this->publish_group_state($call);
        $this->api_response->ok(array(
            'active_call' => $this->group_call_payload($call, $this->api_auth->user_id()),
            'jitsi' => $this->jitsi_payload($call, $this->api_auth->user_id()),
        ));
    }

    public function leave($call_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $call = $this->sek_call_model->find((int) $call_id);
        if (!is_array($call) || (string) $call['scope'] !== 'group') {
            $this->api_response->error('not_found', 'Panggilan tidak ditemukan.', 404);
            return;
        }
        $conversation = $this->group_conversation((int) $call['conversation_id']);
        if ($conversation === NULL) {
            return;
        }
        $this->sek_call_model->leave_participant((int) $call_id, $this->api_auth->user_id());
        if ($this->sek_call_model->active_participant_count((int) $call_id) === 0
            && (string) $call['status'] === 'active') {
            $this->sek_call_model->update((int) $call_id, array(
                'status' => 'ended',
                'ended_at' => gmdate('Y-m-d H:i:s'),
            ));
            $call = $this->sek_call_model->find((int) $call_id);
            $this->sek_realtime->publish(
                (int) $call['conversation_id'],
                'call.ended',
                array('conversation_id' => (int) $call['conversation_id'], 'active_call' => NULL)
            );
        } else {
            $this->publish_group_state($call);
        }
        $this->api_response->ok(array('status' => 'left'));
    }

    private function end_group_call($call, $user_id)
    {
        $conversation_id = (int) $call['conversation_id'];
        $membership = $this->sek_chat_model->membership($conversation_id, $user_id);
        if (!is_array($membership)) {
            $this->api_response->error('forbidden', 'Anda bukan anggota grup ini.', 403);
            return;
        }
        $is_starter = (int) $call['caller_user_id'] === $user_id;
        $is_owner = (string) $membership['role'] === 'owner';
        if (!$is_starter && !$is_owner) {
            $this->api_response->error('forbidden', 'Hanya pembuat panggilan atau owner grup yang dapat mengakhirinya.', 403);
            return;
        }
        $this->db->where('call_id', (int) $call['call_id'])
            ->where('left_at IS NULL', NULL, FALSE)
            ->update('sek_call_participant', array('left_at' => gmdate('Y-m-d H:i:s')));
        $this->sek_call_model->update((int) $call['call_id'], array(
            'status' => 'ended',
            'ended_at' => gmdate('Y-m-d H:i:s'),
        ));
        $this->sek_realtime->publish(
            $conversation_id,
            'call.ended',
            array('conversation_id' => $conversation_id, 'active_call' => NULL)
        );
        $this->api_response->ok(array('status' => 'ended'));
    }

    private function publish_group_state($call)
    {
        $this->sek_realtime->publish(
            (int) $call['conversation_id'],
            'call.updated',
            $this->group_call_payload($call, $this->api_auth->user_id())
        );
    }

    // ----- Direct call creation -------------------------------------------

    private function start_direct()
    {
        if (!$this->sek_jitsi->configured()) {
            $this->api_response->error(
                'calls_unavailable',
                'Layanan panggilan belum dikonfigurasi.',
                503
            );
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $callee_account_id = isset($body['account_id']) ? (int) $body['account_id'] : 0;
        if ($callee_account_id <= 0) {
            $this->api_response->error('validation_error', 'account_id wajib.', 422, array('account_id' => 'Wajib diisi.'));
            return;
        }
        $kind = isset($body['kind']) ? strtolower(trim((string) $body['kind'])) : 'voice';
        $kind = $kind === 'video' ? 'video' : 'voice';
        $callee = $this->sek_account_model->find($callee_account_id);
        if (!is_array($callee) || (string) $callee['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun tujuan tidak ditemukan.', 404);
            return;
        }
        $user_id = (int) $this->api_auth->user_id();
        if ((int) $callee['user_id'] === $user_id) {
            $this->api_response->error('validation_error', 'Tidak dapat menelepon diri sendiri.', 422);
            return;
        }
        $caller_account_id = $this->viewer_account_id();
        $caller_account = $caller_account_id !== NULL
            ? $this->sek_account_model->find((int) $caller_account_id)
            : NULL;
        if (!is_array($caller_account)) {
            $caller_account_id = $this->active_account_id();
            $caller_account = $caller_account_id !== NULL
                ? $this->sek_account_model->find((int) $caller_account_id)
                : NULL;
        }
        if (!is_array($caller_account)) {
            $this->api_response->error('unprocessable', 'Akun aktif tidak ditemukan.', 422);
            return;
        }
        if ($this->sek_account_model->blocked_between(
            (int) $caller_account['account_id'],
            $callee_account_id
        )) {
            $this->api_response->error('forbidden', 'Panggilan tidak diizinkan.', 403);
            return;
        }
        if (!$this->may_call($user_id, $callee, $callee_account_id)) {
            $this->api_response->error(
                'forbidden',
                'Simpan kontak atau mulai percakapan terlebih dahulu.',
                403
            );
            return;
        }
        $this->sek_call_model->expire_stale();
        $this->sek_call_model->expire_abandoned();
        if ($this->sek_call_model->active_for_user((int) $callee['user_id'])) {
            $this->api_response->error('busy', 'Penerima sedang dalam panggilan lain.', 409);
            return;
        }
        if ($this->sek_call_model->active_for_user($user_id)) {
            $this->api_response->error('busy', 'Anda masih dalam panggilan lain.', 409);
            return;
        }
        if ($this->sek_call_model->recent_by_caller($user_id, 60) >= 5) {
            $this->api_response->error('rate_limited', 'Terlalu banyak panggilan. Coba lagi nanti.', 429);
            return;
        }

        $conversation_id = isset($body['conversation_id']) ? (int) $body['conversation_id'] : 0;
        if ($conversation_id <= 0) {
            $direct = $this->sek_chat_model->find_direct_accounts(
                (int) $caller_account['account_id'],
                $callee_account_id
            );
            if (is_array($direct)) {
                $conversation_id = (int) $direct['conversation_id'];
            }
        }
        $call_id = $this->sek_call_model->create(array(
            'scope' => 'direct',
            'caller_user_id' => $user_id,
            'caller_account_id' => (int) $caller_account['account_id'],
            'callee_user_id' => (int) $callee['user_id'],
            'callee_account_id' => $callee_account_id,
            'conversation_id' => $conversation_id > 0 ? $conversation_id : NULL,
            'room' => $this->sek_jitsi->room_name(),
            'kind' => $kind,
        ));
        $call = $this->sek_call_model->find($call_id);
        $caller_payload = $this->call_payload($call, $user_id);

        $this->sek_realtime->publish_user(
            (int) $callee['user_id'],
            'call.incoming',
            $this->call_payload($call, (int) $callee['user_id'])
        );
        $callee_token = $this->sek_jitsi->token($call['room'], $this->jitsi_user($callee, $call));
        $this->sek_notifier->push_users(
            array((int) $callee['user_id']),
            $kind === 'video' ? 'Panggilan video masuk' : 'Panggilan suara masuk',
            (string) $caller_account['display_name'],
            array(
                'call_event' => 'incoming',
                'call_id' => (int) $call_id,
                'conversation_id' => $conversation_id,
                'kind' => $kind,
                'room' => (string) $call['room'],
                'server_url' => $this->sek_jitsi->server_url(),
                'token' => (string) $callee_token,
                'caller_user_id' => $user_id,
                'caller_account_id' => (int) $caller_account['account_id'],
                'caller_name' => (string) $caller_account['display_name'],
                'caller_handle' => isset($caller_account['handle']) ? (string) $caller_account['handle'] : '',
                'caller_avatar_media_id' => $caller_account['avatar_media_id'] !== NULL
                    ? (int) $caller_account['avatar_media_id'] : 0,
                'caller_type_code' => (string) $caller_account['type_code'],
                'expires_at' => gmdate('Y-m-d H:i:s', time() + Sek_call_model::RING_TIMEOUT),
                'channel' => 'sekitar_call',
            )
        );

        $this->api_response->json(array(
            'call' => $caller_payload,
            'jitsi' => $this->jitsi_payload($call, $user_id),
        ), 201);
    }

    // ----- Helpers --------------------------------------------------------

    private function may_call($user_id, $callee, $callee_account_id)
    {
        if (is_array($this->sek_social_model->contact_exists($user_id, $callee_account_id))) {
            return TRUE;
        }
        foreach ($this->sek_account_model->list_for_user($user_id) as $account) {
            if (is_array($this->sek_chat_model->find_direct_accounts(
                (int) $account['account_id'],
                (int) $callee_account_id
            ))) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function group_conversation($conversation_id)
    {
        $conversation = $this->sek_chat_model->find((int) $conversation_id);
        if (!is_array($conversation)
            || (string) $conversation['type'] !== 'group'
            || !is_array($this->sek_chat_model->membership((int) $conversation_id, $this->api_auth->user_id()))) {
            $this->api_response->error('not_found', 'Grup tidak ditemukan.', 404);
            return NULL;
        }
        return $conversation;
    }

    private function jitsi_user($account, $call)
    {
        $avatar = '';
        if (isset($account['avatar_media_id']) && $account['avatar_media_id'] !== NULL) {
            $avatar = rtrim(base_url(), '/') . '/api/v1/media/' . (int) $account['avatar_media_id'];
        }
        return array(
            'user_id' => (string) $account['user_id'],
            'display_name' => (string) $account['display_name'],
            'email' => '',
            'avatar_url' => $avatar,
            'moderator' => TRUE,
        );
    }

    private function jitsi_payload($call, $user_id)
    {
        if ((string) $call['scope'] === 'group') {
            $account = $this->sek_account_model->find($this->viewer_account_id());
        } else {
            $account = (int) $call['caller_user_id'] === (int) $user_id
                ? $this->account_for_call($call, 'caller')
                : $this->account_for_call($call, 'callee');
        }
        if (!is_array($account)) {
            $account = $this->sek_account_model->find($this->viewer_account_id());
        }
        return array(
            'server_url' => $this->sek_jitsi->server_url(),
            'room' => (string) $call['room'],
            'token' => (string) $this->sek_jitsi->token((string) $call['room'], $this->jitsi_user($account, $call)),
            'kind' => (string) $call['kind'],
        );
    }

    private function account_for_call($call, $side)
    {
        $account_id = $side === 'caller' ? $call['caller_account_id'] : $call['callee_account_id'];
        if ($account_id !== NULL) {
            $account = $this->sek_account_model->find((int) $account_id);
            if (is_array($account)) {
                return $account;
            }
        }
        $user_id = $side === 'caller' ? $call['caller_user_id'] : $call['callee_user_id'];
        if ($user_id !== NULL) {
            return $this->sek_account_model->personal_for_user((int) $user_id);
        }
        return NULL;
    }

    private function account_summary($account)
    {
        if (!is_array($account)) {
            return NULL;
        }
        return array(
            'user_id' => (int) $account['user_id'],
            'account_id' => (int) $account['account_id'],
            'display_name' => (string) $account['display_name'],
            'handle' => $account['handle'],
            'avatar_media_id' => $account['avatar_media_id'] !== NULL
                ? (int) $account['avatar_media_id'] : NULL,
            'type_code' => (string) $account['type_code'],
            'type_label' => isset($account['label_id']) ? (string) $account['label_id'] : NULL,
        );
    }

    private function call_payload($call, $viewer_user_id)
    {
        $viewer_user_id = (int) $viewer_user_id;
        $caller = $this->account_summary($this->account_for_call($call, 'caller'));
        $callee = $this->account_summary($this->account_for_call($call, 'callee'));
        $direction = (int) $call['caller_user_id'] === $viewer_user_id ? 'outgoing' : 'incoming';
        $peer = $direction === 'outgoing' ? $callee : $caller;
        return array(
            'call_id' => (int) $call['call_id'],
            'scope' => (string) $call['scope'],
            'kind' => (string) $call['kind'],
            'status' => (string) $call['status'],
            'direction' => $direction,
            'room' => (string) $call['room'],
            'server_url' => $this->sek_jitsi->server_url(),
            'conversation_id' => $call['conversation_id'] !== NULL ? (int) $call['conversation_id'] : NULL,
            'caller' => $caller,
            'callee' => $callee,
            'peer' => $peer,
            'created_at' => $this->utc($call['created_at']),
            'answered_at' => $this->utc($call['answered_at']),
            'ended_at' => $this->utc($call['ended_at']),
            'duration_seconds' => (int) $call['duration_seconds'],
            'ring_timeout' => Sek_call_model::RING_TIMEOUT,
        );
    }

    private function group_call_payload($call, $viewer_user_id)
    {
        return array(
            'call_id' => (int) $call['call_id'],
            'conversation_id' => (int) $call['conversation_id'],
            'kind' => (string) $call['kind'],
            'status' => (string) $call['status'],
            'room' => (string) $call['room'],
            'server_url' => $this->sek_jitsi->server_url(),
            'started_at' => $this->utc($call['created_at']),
            'participant_count' => $this->sek_call_model->active_participant_count((int) $call['call_id']),
            'joined' => $this->sek_call_model->is_participant((int) $call['call_id'], (int) $viewer_user_id),
        );
    }

    private function peer_display_name($call, $viewer_user_id)
    {
        $account = (int) $call['caller_user_id'] === (int) $viewer_user_id
            ? $this->account_for_call($call, 'callee')
            : $this->account_for_call($call, 'caller');
        return is_array($account) ? (string) $account['display_name'] : 'Panggilan';
    }

    private function notify_missed($call)
    {
        if (empty($call['callee_user_id'])) {
            return;
        }
        $caller = $this->account_for_call($call, 'caller');
        $name = is_array($caller) ? (string) $caller['display_name'] : 'Seseorang';
        $this->sek_notifier->notify_user(
            (int) $call['callee_user_id'],
            'call_missed',
            'Panggilan tak terjawab',
            $name,
            array(
                'call_id' => (int) $call['call_id'],
                'conversation_id' => $call['conversation_id'] !== NULL ? (int) $call['conversation_id'] : 0,
            )
        );
    }
}
