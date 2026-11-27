<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Accounts, members, profiles, and locations for Sekitar. */
class Sek_account_model extends CI_Model
{
    public function types($active_only = TRUE)
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        return $this->db->order_by('sort_order', 'ASC')->get('sek_account_type')->result_array();
    }

    public function type($type_code)
    {
        return $this->db->where('type_code', (string) $type_code)
            ->limit(1)
            ->get('sek_account_type')
            ->row_array();
    }

    public function find($account_id)
    {
        return $this->db->select('a.*, t.label_id, t.label_en, t.allows_presence, t.allows_services')
            ->from('sek_account a')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('a.account_id', (int) $account_id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function list_for_user($user_id)
    {
        return $this->db->select('a.*, t.label_id, t.label_en, t.allows_presence, t.allows_services, m.role')
            ->from('sek_account a')
            ->join('sek_account_member m', 'm.account_id = a.account_id AND m.status = "active"', 'inner')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('m.user_id', (int) $user_id)
            ->where('a.status !=', 'archived')
            ->order_by('a.type_code = "personal"', 'DESC', FALSE)
            ->order_by('a.created_at', 'ASC')
            ->get()
            ->result_array();
    }

    public function member_role($account_id, $user_id)
    {
        $row = $this->db->select('role')
            ->where('account_id', (int) $account_id)
            ->where('user_id', (int) $user_id)
            ->where('status', 'active')
            ->limit(1)
            ->get('sek_account_member')
            ->row_array();
        return is_array($row) ? (string) $row['role'] : NULL;
    }

    public function primary_account($user_id)
    {
        $row = $this->db->select('a.account_id')
            ->from('sek_account a')
            ->join('sek_account_member m', 'm.account_id = a.account_id AND m.status = "active"', 'inner')
            ->where('m.user_id', (int) $user_id)
            ->where('a.status', 'active')
            ->order_by('a.type_code = "personal"', 'DESC', FALSE)
            ->order_by('a.created_at', 'ASC')
            ->limit(1)
            ->get()
            ->row_array();
        return is_array($row) ? (int) $row['account_id'] : NULL;
    }

    public function normalize_handle($handle)
    {
        $handle = strtolower(trim((string) $handle));
        return ltrim($handle, '@');
    }

    public function valid_handle($handle)
    {
        return (bool) preg_match('/^[a-z0-9._]{3,40}$/', $this->normalize_handle($handle));
    }

    public function find_by_handle($handle, $scope = NULL)
    {
        $this->db->select('a.*, t.label_id, t.label_en, t.allows_presence, t.allows_services')
            ->from('sek_account a')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('a.handle', $this->normalize_handle($handle));
        if ($scope !== NULL) {
            $this->db->where('a.handle_scope', (string) $scope);
        }
        return $this->db->order_by('a.type_code = "personal"', 'DESC', FALSE)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function find_all_by_handle($handle)
    {
        return $this->db->select('a.*, t.label_id, t.label_en, t.allows_presence, t.allows_services')
            ->from('sek_account a')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('a.handle', $this->normalize_handle($handle))
            ->where('a.status', 'active')
            ->order_by('a.type_code = "personal"', 'DESC', FALSE)
            ->limit(10)
            ->get()
            ->result_array();
    }

    public function handle_scope_for_type($type_code)
    {
        return (string) $type_code === 'personal' ? 'personal' : 'business';
    }

    public function handle_exists($handle, $scope = NULL)
    {
        $this->db->where('handle', $this->normalize_handle($handle));
        if ($scope !== NULL) {
            $this->db->where('handle_scope', (string) $scope);
        }
        return $this->db->count_all_results('sek_account') > 0;
    }

    public function suggest_handle($seed, $scope = 'personal')
    {
        $base = strtolower((string) $seed);
        $base = preg_replace('/[^a-z0-9._]+/', '', $base);
        $base = trim($base, '._');
        if (strlen($base) < 3) {
            $base = 'user' . $base;
        }
        $base = substr($base, 0, 32);
        $candidate = $base;
        $attempt = 0;
        while ($this->handle_exists($candidate, $scope) && $attempt < 20) {
            $attempt++;
            $candidate = substr($base, 0, 32) . substr(bin2hex(random_bytes(3)), 0, 4);
        }
        return $candidate;
    }

    /**
     * Create a business account with owner membership and base location.
     * Returns the new account_id or NULL.
     */
    public function create($user_id, $data)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('sek_account', array(
            'user_id' => (int) $user_id,
            'type_code' => (string) $data['type_code'],
            'handle' => strtolower((string) $data['handle']),
            'handle_scope' => $this->handle_scope_for_type((string) $data['type_code']),
            'display_name' => (string) $data['display_name'],
            'bio' => isset($data['bio']) ? $data['bio'] : NULL,
            'phone' => isset($data['phone']) ? $data['phone'] : NULL,
            'address' => isset($data['address']) ? $data['address'] : NULL,
            'service_radius_m' => isset($data['service_radius_m']) ? (int) $data['service_radius_m'] : 5000,
            'location_mode' => isset($data['location_mode']) ? (string) $data['location_mode'] : 'fixed',
            'is_online' => 0,
            'is_active' => 1,
            'active_until' => gmdate('Y-m-d H:i:s', time() + 86400),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $account_id = (int) $this->db->insert_id();

        $this->db->insert('sek_account_member', array(
            'account_id' => $account_id,
            'user_id' => (int) $user_id,
            'role' => 'owner',
            'status' => 'active',
            'created_at' => $now,
        ));

        if (isset($data['lat'], $data['lng'])) {
            $this->db->query(
                'INSERT INTO sek_account_location (account_id, location, address, source, updated_at) '
                . 'VALUES (?, ST_SRID(POINT(?, ?), 4326), ?, ?, ?)',
                array(
                    $account_id,
                    (float) $data['lng'],
                    (float) $data['lat'],
                    isset($data['address']) ? $data['address'] : NULL,
                    isset($data['source']) ? (string) $data['source'] : 'manual',
                    $now,
                )
            );
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $account_id : NULL;
    }

    public function update($account_id, $fields)
    {
        if (!$fields) {
            return TRUE;
        }
        $fields['updated_at'] = gmdate('Y-m-d H:i:s');
        return $this->db->where('account_id', (int) $account_id)
            ->update('sek_account', $fields);
    }

    public function set_location($account_id, $lat, $lng, $address = NULL, $source = 'manual')
    {
        return $this->db->query(
            'INSERT INTO sek_account_location (account_id, location, address, source, updated_at) '
            . 'VALUES (?, ST_SRID(POINT(?, ?), 4326), ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE location = VALUES(location), address = VALUES(address), '
            . 'source = VALUES(source), updated_at = VALUES(updated_at)',
            array(
                (int) $account_id,
                (float) $lng,
                (float) $lat,
                $address,
                (string) $source,
                gmdate('Y-m-d H:i:s'),
            )
        );
    }

    public function location($account_id)
    {
        return $this->db->select('ST_X(location) AS lat, ST_Y(location) AS lng, address, source, updated_at')
            ->where('account_id', (int) $account_id)
            ->limit(1)
            ->get('sek_account_location')
            ->row_array();
    }

    public function blocked_between($account_a, $account_b)
    {
        if ((int) $account_a <= 0 || (int) $account_b <= 0) {
            return FALSE;
        }
        $count = $this->db->where('blocker_account_id', (int) $account_a)
            ->where('blocked_account_id', (int) $account_b)
            ->count_all_results('sek_block');
        if ($count > 0) {
            return TRUE;
        }
        return $this->db->where('blocker_account_id', (int) $account_b)
            ->where('blocked_account_id', (int) $account_a)
            ->count_all_results('sek_block') > 0;
    }

    public function blocked_account_ids($account_id)
    {
        if ((int) $account_id <= 0) {
            return array();
        }
        $rows = $this->db->select('blocked_account_id AS account_id')
            ->where('blocker_account_id', (int) $account_id)
            ->get('sek_block')
            ->result_array();
        $rows2 = $this->db->select('blocker_account_id AS account_id')
            ->where('blocked_account_id', (int) $account_id)
            ->get('sek_block')
            ->result_array();
        $ids = array();
        foreach (array_merge($rows, $rows2) as $row) {
            $ids[(int) $row['account_id']] = (int) $row['account_id'];
        }
        return array_values($ids);
    }

    public function hours($account_id)
    {
        return $this->db->where('account_id', (int) $account_id)
            ->order_by('weekday', 'ASC')
            ->get('sek_account_hours')
            ->result_array();
    }

    public function replace_hours($account_id, $rows)
    {
        $this->db->trans_start();
        $this->db->where('account_id', (int) $account_id)->delete('sek_account_hours');
        foreach ($rows as $row) {
            $this->db->insert('sek_account_hours', array(
                'account_id' => (int) $account_id,
                'weekday' => (int) $row['weekday'],
                'open_time' => (string) $row['open_time'],
                'close_time' => (string) $row['close_time'],
                'is_closed' => !empty($row['is_closed']) ? 1 : 0,
            ));
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function members($account_id)
    {
        return $this->db->select('m.account_id, m.user_id, m.role, m.status, m.created_at, '
                . 'u.email, u.display_name')
            ->from('sek_account_member m')
            ->join('sek_user u', 'u.user_id = m.user_id', 'inner')
            ->where('m.account_id', (int) $account_id)
            ->order_by('m.role = "owner"', 'DESC', FALSE)
            ->order_by('m.created_at', 'ASC')
            ->get()
            ->result_array();
    }

    public function member_detail($account_id, $user_id)
    {
        return $this->db->select('m.account_id, m.user_id, m.role, m.status, m.created_at, '
                . 'u.email, u.display_name')
            ->from('sek_account_member m')
            ->join('sek_user u', 'u.user_id = m.user_id', 'inner')
            ->where('m.account_id', (int) $account_id)
            ->where('m.user_id', (int) $user_id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function member_row($account_id, $user_id)
    {
        return $this->db->where('account_id', (int) $account_id)
            ->where('user_id', (int) $user_id)
            ->limit(1)
            ->get('sek_account_member')
            ->row_array();
    }

    public function upsert_member($account_id, $user_id, $role)
    {
        $now = gmdate('Y-m-d H:i:s');
        $existing = $this->member_row($account_id, $user_id);
        if (is_array($existing)) {
            return $this->db->where('account_id', (int) $account_id)
                ->where('user_id', (int) $user_id)
                ->update('sek_account_member', array('role' => (string) $role, 'status' => 'active'));
        }
        return $this->db->insert('sek_account_member', array(
            'account_id' => (int) $account_id,
            'user_id' => (int) $user_id,
            'role' => (string) $role,
            'status' => 'active',
            'created_at' => $now,
        ));
    }

    public function remove_member($account_id, $user_id)
    {
        return $this->db->where('account_id', (int) $account_id)
            ->where('user_id', (int) $user_id)
            ->update('sek_account_member', array('status' => 'removed'));
    }

    public function search($query, $limit = 20, $viewer_account_id = NULL)
    {
        $like = $this->db->escape_like_str((string) $query);
        $blocked = $this->blocked_account_ids($viewer_account_id);
        $this->db->select('a.*, t.label_id, t.label_en')
            ->from('sek_account a')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('a.status', 'active')
            ->group_start()
                ->like('a.handle', $like, 'both', FALSE)
                ->or_like('a.display_name', $like, 'both', FALSE)
            ->group_end();
        if ($blocked) {
            $this->db->where_not_in('a.account_id', $blocked);
        }
        return $this->db->order_by('a.verified_at IS NULL', 'ASC', FALSE)
            ->order_by('a.display_name', 'ASC')
            ->limit(max(1, min(20, (int) $limit)))
            ->get()
            ->result_array();
    }

    public function presence($account_id)
    {
        return $this->db->select('p.account_id, ST_X(p.location) AS lat, ST_Y(p.location) AS lng, '
                . 'p.accuracy_m, p.heading, p.speed_mps, p.is_online, p.updated_at')
            ->where('p.account_id', (int) $account_id)
            ->limit(1)
            ->get('sek_account_presence p')
            ->row_array();
    }

    public function upsert_presence($account_id, $lat, $lng, $accuracy, $heading, $speed)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->query(
            'INSERT INTO sek_account_presence (account_id, location, accuracy_m, heading, speed_mps, is_online, updated_at) '
            . 'VALUES (?, ST_SRID(POINT(?, ?), 4326), ?, ?, ?, 1, ?) '
            . 'ON DUPLICATE KEY UPDATE location = VALUES(location), accuracy_m = VALUES(accuracy_m), '
            . 'heading = VALUES(heading), speed_mps = VALUES(speed_mps), is_online = 1, updated_at = VALUES(updated_at)',
            array(
                (int) $account_id,
                (float) $lng,
                (float) $lat,
                $accuracy !== NULL ? (int) $accuracy : NULL,
                $heading !== NULL ? (int) $heading : NULL,
                $speed !== NULL ? (float) $speed : NULL,
                $now,
            )
        );
        $this->db->where('account_id', (int) $account_id)
            ->update('sek_account', array('is_online' => 1, 'updated_at' => $now));
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function delete_presence($account_id)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->where('account_id', (int) $account_id)->delete('sek_account_presence');
        $this->db->where('account_id', (int) $account_id)
            ->update('sek_account', array('is_online' => 0, 'updated_at' => $now));
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Nearby business POIs plus live presence, merged and sorted by distance.
     * Coordinates use MySQL's SRID 4326 axis order: POINT(longitude, latitude).
     */
    public function nearby($lat, $lng, $radius_m, $types, $online_only, $blocked_ids, $limit, $include_realtime = TRUE)
    {
        $distance_sql = 'ST_Distance_Sphere(%s.location, ST_SRID(POINT(?, ?), 4326))';
        $base = array((float) $lng, (float) $lat);

        $place_sql = 'SELECT a.account_id, a.user_id, a.type_code, a.handle, a.display_name, a.bio, a.avatar_media_id, '
            . 'a.is_online, a.is_active, a.active_until, a.location_mode, a.verified_at, a.service_radius_m, t.label_id, t.label_en, '
            . 'ST_X(l.location) AS lat, ST_Y(l.location) AS lng, '
            . sprintf($distance_sql, 'l') . ' AS distance_m, "place" AS source, NULL AS last_seen_at '
            . 'FROM sek_account a '
            . 'INNER JOIN sek_account_type t ON t.type_code = a.type_code '
            . 'INNER JOIN sek_account_location l ON l.account_id = a.account_id '
            . 'WHERE a.status = "active" AND a.type_code <> "personal" '
            . 'AND ' . sprintf($distance_sql, 'l') . ' <= ?';
        if (!$include_realtime) {
            $place_sql .= ' AND a.location_mode = "fixed"';
        }
        $params = array_merge($base, $base, array((int) $radius_m));
        if ($types) {
            $place_sql .= ' AND a.type_code IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
            $params = array_merge($params, $types);
        }
        if ($blocked_ids) {
            $place_sql .= ' AND a.account_id NOT IN (' . implode(',', array_fill(0, count($blocked_ids), '?')) . ')';
            $params = array_merge($params, $blocked_ids);
        }
        $place_sql .= ' ORDER BY distance_m ASC LIMIT ?';
        $params[] = (int) $limit;

        $presence_sql = 'SELECT a.account_id, a.user_id, a.type_code, a.handle, a.display_name, a.bio, a.avatar_media_id, '
            . 'a.is_online, a.is_active, a.active_until, a.location_mode, a.verified_at, a.service_radius_m, t.label_id, t.label_en, '
            . 'ST_X(p.location) AS lat, ST_Y(p.location) AS lng, '
            . sprintf($distance_sql, 'p') . ' AS distance_m, "live" AS source, p.updated_at AS last_seen_at '
            . 'FROM sek_account a '
            . 'INNER JOIN sek_account_type t ON t.type_code = a.type_code '
            . 'INNER JOIN sek_account_presence p ON p.account_id = a.account_id '
            . 'WHERE a.status = "active" AND t.allows_presence = 1 AND p.is_online = 1 '
            . 'AND p.updated_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE) '
            . 'AND ' . sprintf($distance_sql, 'p') . ' <= ?';
        $presence_params = array_merge($base, $base, array((int) $radius_m));
        if ($types) {
            $presence_sql .= ' AND a.type_code IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
            $presence_params = array_merge($presence_params, $types);
        }
        if ($blocked_ids) {
            $presence_sql .= ' AND a.account_id NOT IN (' . implode(',', array_fill(0, count($blocked_ids), '?')) . ')';
            $presence_params = array_merge($presence_params, $blocked_ids);
        }
        $presence_sql .= ' ORDER BY distance_m ASC LIMIT ?';
        $presence_params[] = (int) $limit;

        // With online=1 only fresh presence is relevant; base POIs would show
        // stale online flags for accounts whose presence stopped updating.
        $places = $online_only ? array() : $this->db->query($place_sql, $params)->result_array();
        $presence = $include_realtime
            ? $this->db->query($presence_sql, $presence_params)->result_array()
            : array();

        $merged = array();
        foreach ($places as $row) {
            $merged[(int) $row['account_id']] = $row;
        }
        foreach ($presence as $row) {
            $merged[(int) $row['account_id']] = $row;
        }
        $rows = array_values($merged);
        usort($rows, function ($left, $right) {
            return (float) $left['distance_m'] <=> (float) $right['distance_m'];
        });
        return array_slice($rows, 0, (int) $limit);
    }

    /** Account id => nearest distance for accounts inside the radius. */
    public function nearby_account_distances($lat, $lng, $radius_m, $limit = 500)
    {
        $rows = $this->db->query(
            'SELECT account_id, MIN(distance_m) AS distance_m FROM ('
            . 'SELECT account_id, ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) AS distance_m '
            . 'FROM sek_account_location '
            . 'WHERE ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ? '
            . 'UNION ALL '
            . 'SELECT account_id, ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) AS distance_m '
            . 'FROM sek_account_presence '
            . 'WHERE is_online = 1 AND updated_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE) '
            . 'AND ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ? '
            . ') AS nearby GROUP BY account_id ORDER BY distance_m ASC LIMIT ?',
            array(
                (float) $lng,
                (float) $lat,
                (float) $lng,
                (float) $lat,
                (int) $radius_m,
                (float) $lng,
                (float) $lat,
                (float) $lng,
                (float) $lat,
                (int) $radius_m,
                (int) $limit,
            )
        )->result_array();
        $distances = array();
        foreach ($rows as $row) {
            $distances[(int) $row['account_id']] = (float) $row['distance_m'];
        }
        return $distances;
    }

    /** Account ids that have a base point or fresh presence inside the radius. */
    public function nearby_account_ids($lat, $lng, $radius_m, $limit = 500)
    {
        $rows = $this->db->query(
            'SELECT account_id FROM sek_account_location '
            . 'WHERE ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ? '
            . 'UNION '
            . 'SELECT account_id FROM sek_account_presence '
            . 'WHERE is_online = 1 AND updated_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE) '
            . 'AND ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ? '
            . 'LIMIT ?',
            array(
                (float) $lng,
                (float) $lat,
                (int) $radius_m,
                (float) $lng,
                (float) $lat,
                (int) $radius_m,
                (int) $limit,
            )
        )->result_array();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int) $row['account_id'];
        }
        return $ids;
    }

    public function media_belongs_to($media_id, $user_id)
    {
        return $this->db->where('media_id', (int) $media_id)
            ->where('owner_user_id', (int) $user_id)
            ->count_all_results('sek_media') > 0;
    }
}
