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
        $payload = array(
            'message' => array(
                'token' => (string) $deviceToken,
                'notification' => array('title' => (string) $title, 'body' => (string) $body),
                'data' => $data,
                'android' => array('priority' => 'high'),
                'apns' => array('payload' => array('aps' => array('sound' => 'default'))),
            ),
        );
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
            if (is_scalar($value) || $value === NULL) {
                $data[(string) $key] = (string) $value;
            }
        }
    }
    return $data;
}

$client = new FcmClient($config['credentials']);
$lastNotice = 0;

log_line('push worker started (fcm=' . ($client->configured() ? 'configured' : 'not configured') . ')');

while (TRUE) {
    try {
        $db = db_connect($config);
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
                continue;
            }

            $body = $send['body'];
            $invalidToken = $send['status'] === 404 || stripos($body, 'UNREGISTERED') !== FALSE || stripos($body, 'INVALID_ARGUMENT') !== FALSE;
            if ($invalidToken) {
                $db->query('UPDATE sek_push_queue SET status = "skipped", attempts = attempts + 1, last_error = "invalid_token" WHERE queue_id = ' . $queueId);
                if (!empty($row['device_id'])) {
                    $db->query('UPDATE sek_device SET push_token = NULL WHERE device_id = ' . (int) $row['device_id']);
                }
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
