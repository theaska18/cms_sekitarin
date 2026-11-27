<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Posts, contact/nearby feed, profile grid, likes, comments, and reports. */
class Sek_posts extends Sek_api_base
{
    public function feed()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $lat = $this->input->get('lat', TRUE);
        $lng = $this->input->get('lng', TRUE);
        $radius = (int) $this->input->get('radius', TRUE);
        $radius = $radius > 0 ? max(100, min(5000, $radius)) : 2000;
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(50, $limit) : 20;
        $before = (int) $this->input->get('before_id', TRUE);

        if (($lat !== NULL && $lat !== '') || ($lng !== NULL && $lng !== '')) {
            if (!$this->valid_lat($lat) || !$this->valid_lng($lng)) {
                $this->api_response->error('validation_error', 'lat/lng tidak valid.', 422);
                return;
            }
        } else {
            $lat = NULL;
            $lng = NULL;
        }
        // The viewer position is only used to filter nearby posts; it is never
        // exposed to other users, so feed viewing does not need a share consent.
        $scope = $this->feed_scope($lat, $lng, $radius);
        $rows = $this->sek_post_model->feed($scope['ids'], $before, $limit);
        $meta = $scope['meta'];
        $fallback = FALSE;
        $has_nearby = FALSE;
        foreach ($meta as $entry) {
            if (isset($entry['feed_source']) && $entry['feed_source'] === 'nearby') {
                $has_nearby = TRUE;
                break;
            }
        }
        if (!$has_nearby && $before <= 0) {
            $blocked = $this->sek_account_model->blocked_account_ids($this->viewer_account_id());
            $exclude = array_merge($scope['ids'], $blocked);
            $recent = $this->sek_post_model->recent_public($exclude, $limit);
            if ($recent) {
                $fallback = TRUE;
                foreach ($recent as $row) {
                    $account_id = (int) $row['account_id'];
                    if (!isset($meta[$account_id])) {
                        $meta[$account_id] = array('feed_source' => 'recent', 'distance_m' => NULL);
                    }
                }
                $rows = array_merge($rows, $recent);
                usort($rows, function ($left, $right) {
                    return (int) $right['post_id'] <=> (int) $left['post_id'];
                });
            }
        }
        $has_more = count($rows) > $limit;
        if ($has_more) {
            $rows = array_slice($rows, 0, $limit);
        }
        $this->api_response->ok(array(
            'data' => $this->post_payloads($rows, $meta),
            'has_more' => $has_more,
            'fallback' => $fallback,
            'radius_m' => $lat !== NULL ? $radius : NULL,
        ));
    }

    public function index()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->account_context(TRUE);
        if ($account === NULL) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $caption = isset($body['caption']) ? trim((string) $body['caption']) : '';
        if (mb_strlen($caption) > 2200) {
            $this->api_response->error('validation_error', 'Caption maksimal 2200 karakter.', 422);
            return;
        }
        $media_ids = isset($body['media_ids']) && is_array($body['media_ids'])
            ? array_values(array_unique(array_map('intval', $body['media_ids'])))
            : array();
        $media_ids = array_values(array_filter($media_ids, function ($id) {
            return $id > 0;
        }));
        if (count($media_ids) > 5) {
            $this->api_response->error('validation_error', 'Maksimal 5 media per post.', 422);
            return;
        }
        if ($caption === '' && !$media_ids) {
            $this->api_response->error('validation_error', 'Post memerlukan caption atau media.', 422);
            return;
        }
        foreach ($media_ids as $media_id) {
            $media = $this->sek_media_model->find($media_id);
            if (!is_array($media)
                || (int) $media['owner_user_id'] !== (int) $this->api_auth->user_id()
                || (string) $media['status'] !== 'ready'
                || (string) $media['purpose'] !== 'post'
                || !preg_match('#^(image|video)/#', (string) $media['mime'])) {
                $this->api_response->error('validation_error', 'media_ids berisi media yang tidak valid.', 422);
                return;
            }
        }

        $post_id = $this->sek_post_model->create(
            $this->api_auth->user_id(),
            (int) $account['account_id'],
            $caption,
            $media_ids
        );
        if ($post_id === NULL) {
            $this->api_response->error('server_error', 'Post gagal disimpan.', 500);
            return;
        }
        $post = $this->sek_post_model->find($post_id);
        $this->api_response->json(array('post' => $this->post_payload($post)), 201);
    }

    public function show($post_id = 0)
    {
        if (!$this->require_method(array('GET', 'PATCH', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $post = $this->post_for((int) $post_id);
        if ($post === NULL) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'PATCH') {
            $this->update_post($post);
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->delete_post($post);
            return;
        }
        $this->api_response->ok(array('post' => $this->post_payload($post)));
    }

    private function update_post($post)
    {
        $role = $this->sek_account_model->member_role((int) $post['account_id'], $this->api_auth->user_id());
        if ((int) $post['user_id'] !== (int) $this->api_auth->user_id()
            && !in_array($role, array('owner', 'manager'), TRUE)) {
            $this->api_response->error('forbidden', 'Tidak dapat mengubah post ini.', 403);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $caption = array_key_exists('caption', $body)
            ? trim((string) $body['caption'])
            : (string) $post['caption'];
        if (mb_strlen($caption) > 2200) {
            $this->api_response->error('validation_error', 'Caption maksimal 2200 karakter.', 422);
            return;
        }
        $media_ids = NULL;
        if (array_key_exists('media_ids', $body) && is_array($body['media_ids'])) {
            $media_ids = array_values(array_unique(array_map('intval', $body['media_ids'])));
            $media_ids = array_values(array_filter($media_ids, function ($id) {
                return $id > 0;
            }));
            if (count($media_ids) > 5) {
                $this->api_response->error('validation_error', 'Maksimal 5 media per post.', 422);
                return;
            }
            foreach ($media_ids as $media_id) {
                $media = $this->sek_media_model->find($media_id);
                if (!is_array($media)
                    || (int) $media['owner_user_id'] !== (int) $this->api_auth->user_id()
                    || (string) $media['status'] !== 'ready'
                    || (string) $media['purpose'] !== 'post'
                    || !preg_match('#^(image|video)/#', (string) $media['mime'])) {
                    $this->api_response->error('validation_error', 'media_ids berisi media yang tidak valid.', 422);
                    return;
                }
            }
        }
        $media_count = $media_ids !== NULL
            ? count($media_ids)
            : $this->db->where('post_id', (int) $post['post_id'])->count_all_results('sek_post_media');
        if ($caption === '' && $media_count === 0) {
            $this->api_response->error('validation_error', 'Post memerlukan caption atau media.', 422);
            return;
        }
        $this->sek_post_model->update((int) $post['post_id'], $caption, $media_ids);
        $fresh = $this->sek_post_model->find((int) $post['post_id']);
        $this->api_response->ok(array('post' => $this->post_payload($fresh)));
    }

    public function profile($account_id = 0)
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->sek_account_model->find((int) $account_id);
        if (!is_array($account) || (string) $account['status'] !== 'active' || $this->viewer_blocked((int) $account_id)) {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return;
        }
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(50, $limit) : 20;
        $before = (int) $this->input->get('before_id', TRUE);
        $rows = $this->sek_post_model->profile_posts((int) $account_id, $before, $limit);
        $has_more = count($rows) > $limit;
        if ($has_more) {
            $rows = array_slice($rows, 0, $limit);
        }
        $this->api_response->ok(array(
            'data' => $this->post_payloads($rows),
            'has_more' => $has_more,
        ));
    }

    public function like($post_id = 0)
    {
        if (!$this->require_method(array('POST', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $post = $this->post_for((int) $post_id);
        if ($post === NULL) {
            return;
        }
        $before = $this->sek_post_model->counts(array((int) $post_id), $this->api_auth->user_id());
        $was_liked = !empty($before[(int) $post_id]['liked']);
        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->sek_post_model->unlike((int) $post_id, $this->api_auth->user_id());
            $liked = FALSE;
        } else {
            $this->sek_post_model->like((int) $post_id, $this->api_auth->user_id());
            $liked = TRUE;
            if (!$was_liked && (int) $post['user_id'] !== (int) $this->api_auth->user_id()) {
                $this->sek_notifier->notify_user(
                    (int) $post['user_id'],
                    'post_like',
                    'Suka baru',
                    (string) $this->api_auth->user()['display_name'] . ' menyukai post Anda.',
                    array('post_id' => (int) $post_id)
                );
            }
        }
        $counts = $this->sek_post_model->counts(array((int) $post_id), $this->api_auth->user_id());
        $this->api_response->ok(array(
            'status' => $liked ? 'liked' : 'unliked',
            'post_id' => (int) $post_id,
            'liked' => $liked,
            'like_count' => isset($counts[(int) $post_id]) ? $counts[(int) $post_id]['like_count'] : 0,
        ));
    }

    public function comments($post_id = 0)
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $post = $this->post_for((int) $post_id);
        if ($post === NULL) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'GET') {
            $limit = (int) $this->input->get('limit', TRUE);
            $limit = $limit > 0 ? min(100, $limit) : 50;
            $before = (int) $this->input->get('before_id', TRUE);
            $rows = $this->sek_post_model->comments((int) $post_id, $before, $limit);
            $has_more = count($rows) > $limit;
            if ($has_more) {
                $rows = array_slice($rows, 0, $limit);
            }
            $data = array();
            foreach ($rows as $row) {
                $data[] = $this->comment_payload($row);
            }
            $this->api_response->ok(array('data' => $data, 'has_more' => $has_more));
            return;
        }

        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $text = isset($body['body']) ? trim((string) $body['body']) : '';
        if ($text === '' || mb_strlen($text) > 1000) {
            $this->api_response->error('validation_error', 'Komentar 1–1000 karakter.', 422);
            return;
        }
        $comment_id = $this->sek_post_model->add_comment(
            (int) $post_id,
            $this->api_auth->user_id(),
            $text,
            $this->viewer_account_id()
        );
        $comment = $this->sek_post_model->comment($comment_id);
        $comment['display_name'] = (string) $this->api_auth->user()['display_name'];
        $account = $this->db
            ->select('account_id, handle, avatar_media_id, display_name')
            ->where('account_id', $this->viewer_account_id())
            ->limit(1)
            ->get('sek_account')
            ->row_array();
        if (is_array($account)) {
            $comment['account_id'] = (int) $account['account_id'];
            $comment['handle'] = $account['handle'];
            $comment['avatar_media_id'] = $account['avatar_media_id'];
            $comment['display_name'] = $account['display_name'];
        }
        if ((int) $post['user_id'] !== (int) $this->api_auth->user_id()) {
            $this->sek_notifier->notify_user(
                (int) $post['user_id'],
                'post_comment',
                'Komentar baru',
                (string) $this->api_auth->user()['display_name'] . ': ' . mb_substr($text, 0, 120),
                array('post_id' => (int) $post_id, 'comment_id' => $comment_id)
            );
        }
        $this->api_response->json(array('comment' => $this->comment_payload($comment)), 201);
    }

    public function delete_comment($comment_id = 0)
    {
        if (!$this->require_method('DELETE')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $comment = $this->sek_post_model->comment((int) $comment_id);
        if (!is_array($comment) || !empty($comment['deleted_at'])) {
            $this->api_response->error('not_found', 'Komentar tidak ditemukan.', 404);
            return;
        }
        $post = $this->sek_post_model->find((int) $comment['post_id']);
        $is_comment_author = (int) $comment['user_id'] === (int) $this->api_auth->user_id();
        $is_post_owner = is_array($post) && (int) $post['user_id'] === (int) $this->api_auth->user_id();
        if (!$is_comment_author && !$is_post_owner) {
            $this->api_response->error('forbidden', 'Tidak dapat menghapus komentar ini.', 403);
            return;
        }
        $this->sek_post_model->delete_comment((int) $comment_id);
        $this->api_response->ok(array('status' => 'deleted', 'comment_id' => (int) $comment_id));
    }

    public function report($post_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $post = $this->post_for((int) $post_id);
        if ($post === NULL) {
            return;
        }
        if ((int) $post['user_id'] === (int) $this->api_auth->user_id()) {
            $this->api_response->error('unprocessable', 'Tidak dapat melaporkan post sendiri.', 422);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $category = isset($body['category']) ? strtolower(trim((string) $body['category'])) : '';
        $description = isset($body['description']) ? trim((string) $body['description']) : NULL;
        if (!in_array($category, array('spam', 'abuse', 'fraud', 'inappropriate', 'other'), TRUE)) {
            $this->api_response->error('validation_error', 'Kategori laporan tidak valid.', 422);
            return;
        }
        if ($description !== NULL && mb_strlen($description) > 1000) {
            $this->api_response->error('validation_error', 'Deskripsi maksimal 1000 karakter.', 422);
            return;
        }
        if (!$this->api_auth->rate_limit('post_report|' . $this->api_auth->user_id(), 10, 86400, 3600)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak laporan hari ini.', 429);
            return;
        }
        $report_id = $this->sek_social_model->create_report(
            $this->api_auth->user_id(),
            (int) $post['account_id'],
            $category,
            $description,
            (int) $post_id
        );
        $this->api_response->json(array(
            'report' => array(
                'report_id' => $report_id,
                'post_id' => (int) $post_id,
                'category' => $category,
                'status' => 'open',
            ),
        ), 201);
    }

    private function delete_post($post)
    {
        $is_author = (int) $post['user_id'] === (int) $this->api_auth->user_id();
        $role = $this->sek_account_model->member_role((int) $post['account_id'], $this->api_auth->user_id());
        if (!$is_author && !in_array($role, array('owner', 'manager'), TRUE)) {
            $this->api_response->error('forbidden', 'Tidak dapat menghapus post ini.', 403);
            return;
        }
        $this->sek_post_model->soft_delete((int) $post['post_id']);
        $this->api_response->ok(array('status' => 'deleted', 'post_id' => (int) $post['post_id']));
    }

    private function post_for($post_id)
    {
        $post = $this->sek_post_model->find($post_id);
        if (!is_array($post) || (string) $post['status'] !== 'active'
            || (string) $post['account_status'] !== 'active'
            || $this->viewer_blocked((int) $post['account_id'])) {
            $this->api_response->error('not_found', 'Post tidak ditemukan.', 404);
            return NULL;
        }
        return $post;
    }

    private function viewer_blocked($account_id)
    {
        $viewer = $this->viewer_account_id();
        return $viewer !== NULL && $this->sek_account_model->blocked_between($viewer, (int) $account_id);
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

    private function feed_scope($lat, $lng, $radius)
    {
        $ids = array();
        $meta = array();
        foreach ($this->sek_account_model->list_for_user($this->api_auth->user_id()) as $account) {
            $account_id = (int) $account['account_id'];
            $ids[] = $account_id;
            $meta[$account_id] = array('feed_source' => 'own', 'distance_m' => NULL);
        }
        foreach ($this->sek_social_model->contact_account_ids($this->api_auth->user_id()) as $account_id) {
            $account_id = (int) $account_id;
            $ids[] = $account_id;
            if (!isset($meta[$account_id])) {
                $meta[$account_id] = array('feed_source' => 'contact', 'distance_m' => NULL);
            }
        }
        if ($lat !== NULL && $lng !== NULL) {
            foreach ($this->sek_account_model->nearby_account_distances($lat, $lng, $radius, 500) as $account_id => $distance) {
                $account_id = (int) $account_id;
                $ids[] = $account_id;
                $meters = (int) round($distance);
                if (!isset($meta[$account_id])) {
                    $meta[$account_id] = array(
                        'feed_source' => 'nearby',
                        'distance_m' => $meters,
                    );
                } else {
                    // Keep own/contact labels but still expose the radius distance.
                    $meta[$account_id]['distance_m'] = $meters;
                }
            }
        }
        $blocked = $this->sek_account_model->blocked_account_ids($this->viewer_account_id());
        $ids = array_values(array_unique(array_diff($ids, $blocked)));
        foreach ($blocked as $blocked_id) {
            unset($meta[$blocked_id]);
        }
        return array('ids' => $ids, 'meta' => $meta);
    }

    private function post_payloads($rows, $meta = array())
    {
        if (!$rows) {
            return array();
        }
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int) $row['post_id'];
        }
        $counts = $this->sek_post_model->counts($ids, $this->api_auth->user_id());
        $media = $this->sek_post_model->media_for($ids);
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->post_payload($row, $counts, $media, $meta);
        }
        return $data;
    }

    private function post_payload($post, $counts = NULL, $media_map = NULL, $scope_meta = array())
    {
        $post_id = (int) $post['post_id'];
        if ($counts === NULL) {
            $counts = $this->sek_post_model->counts(array($post_id), $this->api_auth->user_id());
        }
        if ($media_map === NULL) {
            $media_map = $this->sek_post_model->media_for(array($post_id));
        }
        $media = array();
        if (isset($media_map[$post_id])) {
            foreach ($media_map[$post_id] as $item) {
                $media[] = array(
                    'media_id' => (int) $item['media_id'],
                    'mime' => (string) $item['mime'],
                    'width' => $item['width'] !== NULL ? (int) $item['width'] : NULL,
                    'height' => $item['height'] !== NULL ? (int) $item['height'] : NULL,
                    'url' => '/api/v1/media/' . (int) $item['media_id'],
                );
            }
        }
        $count = isset($counts[$post_id]) ? $counts[$post_id] : array('like_count' => 0, 'comment_count' => 0, 'liked' => FALSE);
        $meta = isset($scope_meta[(int) $post['account_id']])
            ? $scope_meta[(int) $post['account_id']]
            : array('feed_source' => NULL, 'distance_m' => NULL);
        return array(
            'post_id' => $post_id,
            'feed_source' => $meta['feed_source'],
            'distance_m' => $meta['distance_m'],
            'caption' => $post['caption'],
            'media' => $media,
            'like_count' => (int) $count['like_count'],
            'comment_count' => (int) $count['comment_count'],
            'liked' => (bool) $count['liked'],
            'account' => array(
                'account_id' => (int) $post['account_id'],
                'type_code' => (string) $post['type_code'],
                'type_label' => (string) $post['label_id'],
                'handle' => (string) $post['handle'],
                'display_name' => (string) $post['display_name'],
                'avatar_media_id' => $post['avatar_media_id'] !== NULL ? (int) $post['avatar_media_id'] : NULL,
            ),
            'created_at' => $this->utc($post['created_at']),
            'updated_at' => $this->utc($post['updated_at']),
        );
    }

    private function comment_payload($row)
    {
        return array(
            'comment_id' => (int) $row['comment_id'],
            'post_id' => (int) $row['post_id'],
            'user_id' => (int) $row['user_id'],
            'account_id' => isset($row['account_id']) && $row['account_id'] !== NULL ? (int) $row['account_id'] : NULL,
            'handle' => isset($row['handle']) ? $row['handle'] : NULL,
            'avatar_media_id' => isset($row['avatar_media_id']) && $row['avatar_media_id'] !== NULL ? (int) $row['avatar_media_id'] : NULL,
            'display_name' => isset($row['display_name']) ? (string) $row['display_name'] : NULL,
            'type_code' => isset($row['type_code']) ? (string) $row['type_code'] : 'personal',
            'handle_scope' => isset($row['handle_scope']) ? (string) $row['handle_scope'] : 'personal',
            'body' => (string) $row['body'],
            'created_at' => $this->utc($row['created_at']),
        );
    }
}
