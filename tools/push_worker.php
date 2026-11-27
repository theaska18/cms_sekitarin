<?php
/**
 * Sekitar push worker.
 *
 * Polls sek_push_queue and delivers messages through FCM HTTP v1. Without a
 * service account file the queue rows are marked "skipped" so the plumbing can
 * be tested safely in development.
 */

date_default_timezone_set('UTC');

$config = array(
    'db_host' => getenv('DB_HOST') ?: 'cms-db',
    'db_port' => (int) (getenv('DB_PORT') ?: 3306),
    'db_name' => getenv('DB_NAME') ?: 'cmsdb_sekitarin',
    'db_user' => getenv('DB_USER') ?: '',
    'db_pass' => getenv('DB_PASSWORD') ?: '',
    'credentials' => getenv('SEKITAR_FCM_SERVICE_ACCOUNT') ?: '',
    'poll' => max(2, (int) (getenv('PUSH_POLL_SECONDS') ?: 5)),
);

function log_line($message)
{
    fwrite(STDOUT, '[' . gmdate('c') . '] ' . $message . PHP_EOL);
}

function base64url($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

final class FcmClient
{
    private $credentials;
    private $accessToken = NULL;
    private $accessExpires = 0;

    public function __construct($path)
    {
        $this->credentials = $path !== '' && is_file($path)
            ? json_decode((string) file_get_contents($path), TRUE)
            : NULL;
    }

    public function configured()
    {
        return is_array($this->credentials)
            && !empty($this->credentials['client_email'])
            && !empty($this->credentials['private_key'])
            && !empty($this->credentials['project_id']);
    }

    public function projectId()
    {
        return (string) $this->credentials['project_id'];
    }

    private function accessToken()
    {
        if ($this->accessToken !== NULL && $this->accessExpires > time() + 60) {
            return $this->accessToken;
        }
        $now = time();
        $header = base64url(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $claims = base64url(json_encode(array(
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $this->credentials['token_uri'] ?: 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        )));
        $signature = '';
        $ok = openssl_sign($header . '.' . $claims, $signature, $this->credentials['private_key'], OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new RuntimeException('unable to sign FCM service account assertion');
        }
        $jwt = $header . '.' . $claims . '.' . base64url($signature);
        $tokenUri = $this->credentials['token_uri'] ?: 'https://oauth2.googleapis.com/token';
        $handle = curl_init($tokenUri);
        curl_setopt_array($handle, array(
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            )),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 15,
        ));
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($body === FALSE || $status !== 200) {
            throw new RuntimeException('FCM oauth failed (' . $status . '): ' . ($error !== '' ? $error : $body));
        }
        $decoded = json_decode((string) $body, TRUE);
        if (!is_array($decoded) || empty($decoded['access_token'])) {
            throw new RuntimeException('FCM oauth response has no access_token');
        }
        $this->accessToken = (string) $decoded['access_token'];
        $this->accessExpires = $now + (int) ($decoded['expires_in'] ?? 3600);
        return $this->accessToken;
    }

    public function send($deviceToken, $title, $body, $data)
    {
        // FCM data values must all be strings.
        $stringData = array();
        foreach ((array) $data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $isCall = isset($stringData['call_event']);
        if ($isCall) {
            // Calls must be data-only so the app can render a native call UI
            // (CallKit / full-screen incoming call) instead of a normal tray
            // notification.
            $stringData['title'] = (string) $title;
            $stringData['body'] = (string) $body;
            $payload = array(
                'message' => array(
                    'token' => (string) $deviceToken,
                    'data' => $stringData,
                    'android' => array(
                        'priority' => 'high',
                        'ttl' => $stringData['call_event'] === 'incoming' ? '50s' : '120s',
                    ),
                    'apns' => array(
                        'headers' => array(
                            'apns-priority' => '10',
                            'apns-push-type' => 'background',
                        ),
                        'payload' => array('aps' => array('content-available' => 1)),
                    ),
                ),
            );
        } else {
            $channel = isset($stringData['channel']) && $stringData['channel'] !== ''
                ? $stringData['channel']
                : 'sekitar_chat';
            $payload = array(
                'message' => array(
                    'token' => (string) $deviceToken,
                    'notification' => array('title' => (string) $title, 'body' => (string) $body),
                    'data' => $stringData,
                    'android' => array(
                        'priority' => 'high',
                        'notification' => array(
                            'channel_id' => $channel,
                            'sound' => 'default',
                            'default_vibrate_timings' => true,
                            'notification_priority' => 'PRIORITY_HIGH',
                        ),
                    ),
                    'apns' => array('payload' => array('aps' => array('sound' => 'default'))),
                ),
            );
        }
        $handle = curl_init('https://fcm.googleapis.com/v1/projects/' . rawurlencode($this->projectId()) . '/messages:send');
        curl_setopt_array($handle, array(
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->accessToken(),
                'Content-Type: application/json',
            ),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 20,
        ));
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        return array(
            'ok' => $status === 200,
            'status' => $status,
            'body' => (string) $response,
            'error' => $error,
        );
    }
}

function db_connect($config)
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name'], $config['db_port']);
    if ($db->connect_errno) {
        throw new RuntimeException('DB connection failed: ' . $db->connect_error);
    }
    $db->set_charset('utf8mb4');
    return $db;
}

function json_encode_data($json)
{
    $decoded = $json !== NULL && $json !== '' ? json_decode((string) $json, TRUE) : array();
    $data = array();
    if (is_array($decoded)) {
        foreach ($decoded as $key => $value) {
            $key = (string) $key;
            // FCM rejects reserved data keys (e.g. message_type).
            if (in_array($key, array('message_type', 'from', 'collapse_key'), TRUE)
                || strpos($key, 'google') === 0
                || strpos($key, 'gcm') === 0) {
                continue;
            }
            if (is_scalar($value) || $value === NULL) {
                $data[$key] = (string) $value;
            }
        }
    }
    return $data;
}

function publish_ws($config, $conversation_id, $event, $payload, $exclude_user_id = NULL)
{
    $url = getenv('SEKITAR_WS_PUBLISH_URL') ?: '';
    $secret = getenv('SEKITAR_WS_SECRET') ?: '';
    if ($url === '' || $secret === '') {
        return;
    }
    $body = array(
        'conversation_id' => (int) $conversation_id,
        'event' => (string) $event,
        'payload' => $payload,
    );
    if ($exclude_user_id !== NULL) {
        $body['exclude_user_id'] = (int) $exclude_user_id;
    }
    $handle = curl_init($url);
    curl_setopt_array($handle, array(
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-Internal-Secret: ' . $secret,
        ),
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_CONNECTTIMEOUT_MS => 500,
        CURLOPT_TIMEOUT_MS => 1500,
    ));
    curl_exec($handle);
    curl_close($handle);
}

function publish_ws_user($config, $user_id, $event, $payload)
{
    $url = getenv('SEKITAR_WS_PUBLISH_URL') ?: '';
    $secret = getenv('SEKITAR_WS_SECRET') ?: '';
    if ($url === '' || $secret === '') {
        return;
    }
    if (substr($url, -8) === '/publish') {
        $url = substr($url, 0, -8) . '/publish-user';
    }
    $body = array(
        'user_id' => (int) $user_id,
        'event' => (string) $event,
        'payload' => $payload,
    );
    $handle = curl_init($url);
    curl_setopt_array($handle, array(
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-Internal-Secret: ' . $secret,
        ),
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_CONNECTTIMEOUT_MS => 500,
        CURLOPT_TIMEOUT_MS => 1500,
    ));
    curl_exec($handle);
    curl_close($handle);
}

/** Expire unanswered direct calls and abandoned group calls. */
function expire_calls($db, $config)
{
    $result = $db->query(
        'SELECT c.call_id, c.caller_user_id, c.callee_user_id, c.conversation_id, '
        . 'COALESCE(a.display_name, u.display_name) AS caller_name '
        . 'FROM sek_call c '
        . 'LEFT JOIN sek_account a ON a.account_id = c.caller_account_id '
        . 'LEFT JOIN sek_user u ON u.user_id = c.caller_user_id '
        . 'WHERE c.scope = "direct" AND c.status = "ringing" '
        . 'AND c.created_at < UTC_TIMESTAMP() - INTERVAL 45 SECOND LIMIT 50'
    );
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
    foreach ($rows as $row) {
        $callId = (int) $row['call_id'];
        $updated = $db->query(
            'UPDATE sek_call SET status = "missed", ended_at = UTC_TIMESTAMP() '
            . 'WHERE call_id = ' . $callId . ' AND status = "ringing"'
        );
        if (!$updated || $db->affected_rows < 1) {
            continue;
        }
        $payload = array(
            'call_id' => $callId,
            'status' => 'missed',
            'conversation_id' => $row['conversation_id'] !== NULL ? (int) $row['conversation_id'] : 0,
        );
        publish_ws_user($config, (int) $row['caller_user_id'], 'call.ended', $payload);
        publish_ws_user($config, (int) $row['callee_user_id'], 'call.ended', $payload);
        publish_ws_user($config, (int) $row['callee_user_id'], 'call.canceled', $payload);

        $user_id = (int) $row['callee_user_id'];
        $title = 'Panggilan tak terjawab';
        $name = (string) ($row['caller_name'] ?: 'Seseorang');
        $dataJson = $db->real_escape_string(json_encode(array(
            'call_id' => $callId,
            'conversation_id' => $row['conversation_id'] !== NULL ? (int) $row['conversation_id'] : 0,
        )));
        $titleSql = $db->real_escape_string($title);
        $nameSql = $db->real_escape_string($name);
        $db->query(
            'INSERT INTO sek_notification (user_id, type, title, body, data_json, created_at) VALUES ('
            . $user_id . ', "call_missed", "' . $titleSql . '", "' . $nameSql . '", "' . $dataJson . '", UTC_TIMESTAMP())'
        );
        $notificationId = (int) $db->insert_id;
        $devices = $db->query(
            'SELECT device_id, push_token FROM sek_device WHERE user_id = ' . $user_id
            . ' AND revoked_at IS NULL AND push_token IS NOT NULL AND push_token <> ""'
        );
        $deviceRows = $devices ? $devices->fetch_all(MYSQLI_ASSOC) : array();
        foreach ($deviceRows as $device) {
            $tokenSql = $db->real_escape_string((string) $device['push_token']);
            $db->query(
                'INSERT INTO sek_push_queue (notification_id, user_id, device_id, push_token, title, body, data_json, status, next_attempt_at, created_at) VALUES ('
                . $notificationId . ', ' . $user_id . ', ' . (int) $device['device_id'] . ', "' . $tokenSql
                . '", "' . $titleSql . '", "' . $nameSql . '", "' . $dataJson
                . '", "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
        }
        log_line('call ' . $callId . ' marked missed');
    }

    $groupResult = $db->query(
        'SELECT call_id, conversation_id FROM sek_call WHERE scope = "group" AND status = "active" '
        . 'AND created_at < UTC_TIMESTAMP() - INTERVAL 4 HOUR LIMIT 20'
    );
    $groupRows = $groupResult ? $groupResult->fetch_all(MYSQLI_ASSOC) : array();
    foreach ($groupRows as $row) {
        $callId = (int) $row['call_id'];
        $db->query(
            'UPDATE sek_call SET status = "ended", ended_at = UTC_TIMESTAMP() '
            . 'WHERE call_id = ' . $callId . ' AND status = "active"'
        );
        if ($db->affected_rows < 1) {
            continue;
        }
        $conversationId = (int) $row['conversation_id'];
        publish_ws($config, $conversationId, 'call.ended', array(
            'conversation_id' => $conversationId,
            'active_call' => NULL,
        ));
        log_line('group call ' . $callId . ' expired');
    }
}

$client = new FcmClient($config['credentials']);
$lastNotice = 0;

log_line('push worker started (fcm=' . ($client->configured() ? 'configured' : 'not configured') . ')');

while (TRUE) {
    try {
        $db = db_connect($config);
        expire_calls($db, $config);
        $result = $db->query(
            'SELECT queue_id, notification_id, user_id, device_id, push_token, title, body, data_json, attempts '
            . 'FROM sek_push_queue WHERE status = "pending" AND next_attempt_at <= UTC_TIMESTAMP() '
            . 'ORDER BY queue_id ASC LIMIT 50'
        );
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();

        if (!$client->configured()) {
            if ($rows) {
                $ids = implode(',', array_map(function ($row) { return (int) $row['queue_id']; }, $rows));
                $db->query('UPDATE sek_push_queue SET status = "skipped", last_error = "fcm_not_configured" WHERE queue_id IN (' . $ids . ')');
                log_line('marked ' . count($rows) . ' queued push messages as skipped (no FCM credentials)');
            } elseif (time() - $lastNotice > 300) {
                log_line('waiting for push jobs; FCM credentials not configured');
                $lastNotice = time();
            }
            $db->close();
            sleep($config['poll']);
            continue;
        }

        foreach ($rows as $row) {
            $data = json_encode_data($row['data_json']);
            try {
                $send = $client->send($row['push_token'], $row['title'], $row['body'], $data);
            } catch (Throwable $error) {
                $send = array('ok' => FALSE, 'status' => 0, 'body' => '', 'error' => $error->getMessage());
            }
            $queueId = (int) $row['queue_id'];
            if ($send['ok']) {
                $db->query('UPDATE sek_push_queue SET status = "sent", attempts = attempts + 1, sent_at = UTC_TIMESTAMP(), last_error = NULL WHERE queue_id = ' . $queueId);
                $data = $row['data_json'] !== NULL && $row['data_json'] !== ''
                    ? json_decode((string) $row['data_json'], TRUE)
                    : NULL;
                if (is_array($data) && !empty($data['message_id']) && !empty($data['conversation_id'])) {
                    $message_id = (int) $data['message_id'];
                    $conversation_id = (int) $data['conversation_id'];
                    $user_id = (int) $row['user_id'];
                    $db->query(
                        'INSERT INTO sek_message_receipt (message_id, user_id, delivered_at) '
                        . 'SELECT message_id, ' . $user_id . ', UTC_TIMESTAMP() FROM sek_message '
                        . 'WHERE message_id = ' . $message_id . ' AND sender_user_id <> ' . $user_id . ' '
                        . 'ON DUPLICATE KEY UPDATE delivered_at = COALESCE(delivered_at, VALUES(delivered_at))'
                    );
                    publish_ws($config, $conversation_id, 'messages.delivered', array(
                        'message_ids' => array($message_id),
                        'user_id' => $user_id,
                    ), $user_id);
                }
                continue;
            }

            $body = $send['body'];
            $invalidToken = $send['status'] === 404 || stripos($body, 'UNREGISTERED') !== FALSE;
            if ($invalidToken) {
                $db->query('UPDATE sek_push_queue SET status = "skipped", attempts = attempts + 1, last_error = "invalid_token" WHERE queue_id = ' . $queueId);
                if (!empty($row['device_id'])) {
                    $db->query('UPDATE sek_device SET push_token = NULL WHERE device_id = ' . (int) $row['device_id']);
                }
                continue;
            }
            if ($send['status'] === 400 && stripos($body, 'INVALID_ARGUMENT') !== FALSE) {
                $errorText = $db->real_escape_string(mb_substr(trim($body), 0, 255));
                $db->query(
                    'UPDATE sek_push_queue SET status = "failed", attempts = attempts + 1, '
                    . 'last_error = "' . $errorText . '" WHERE queue_id = ' . $queueId
                );
                continue;
            }

            $attempts = (int) $row['attempts'] + 1;
            $status = $attempts >= 5 ? 'failed' : 'pending';
            $delay = min(3600, 60 * (2 ** min(6, $attempts)));
            $errorText = mb_substr(trim(($send['error'] !== '' ? $send['error'] : $body)), 0, 255);
            $errorSql = $db->real_escape_string($errorText);
            $db->query(
                'UPDATE sek_push_queue SET status = "' . $status . '", attempts = ' . $attempts
                . ', last_error = "' . $errorSql . '", next_attempt_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL ' . $delay . ' SECOND) '
                . 'WHERE queue_id = ' . $queueId
            );
        }
        $db->close();
    } catch (Throwable $error) {
        log_line('worker error: ' . $error->getMessage());
    }
    sleep($config['poll']);
}
