<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Best-effort publisher to the Node WebSocket gateway.
 * REST remains the source of truth; a failed publish never fails the request.
 */
class Sek_realtime
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function publish($conversation_id, $event, $payload, $exclude_user_id = NULL)
    {
        $url = trim((string) getenv('SEKITAR_WS_PUBLISH_URL'));
        $secret = trim((string) getenv('SEKITAR_WS_SECRET'));
        if ($url === '' || $secret === '' || !function_exists('curl_init')) {
            return FALSE;
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
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === FALSE || $status < 200 || $status >= 300) {
            log_message('error', 'Sekitar realtime publish failed (' . $status . '): ' . $error);
            return FALSE;
        }
        return TRUE;
    }
}
