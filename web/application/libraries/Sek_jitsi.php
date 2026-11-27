<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mints short-lived JWT tokens for the Jitsi deployment used by Sekitar.
 *
 * Configuration (environment, never committed):
 *   SEKITAR_JITSI_URL       public base URL, e.g. https://jitsi.ckamal.com
 *   SEKITAR_JITSI_APP_ID    JWT app id (iss), e.g. webcast
 *   SEKITAR_JITSI_SECRET    JWT shared secret
 *   SEKITAR_JITSI_DOMAIN    XMPP domain claim (sub), default meet.jitsi
 *   SEKITAR_JITSI_AUDIENCE  accepted audience claim, default jitsi
 */
class Sek_jitsi
{
    private $url;
    private $app_id;
    private $secret;
    private $domain;
    private $audience;

    public function __construct()
    {
        $this->url = rtrim(trim((string) getenv('SEKITAR_JITSI_URL')), '/');
        if ($this->url === '') {
            $this->url = 'https://jitsi.ckamal.com';
        }
        $this->app_id = trim((string) getenv('SEKITAR_JITSI_APP_ID'));
        $this->secret = trim((string) getenv('SEKITAR_JITSI_SECRET'));
        $this->domain = trim((string) getenv('SEKITAR_JITSI_DOMAIN'));
        if ($this->domain === '') {
            $this->domain = 'meet.jitsi';
        }
        $this->audience = trim((string) getenv('SEKITAR_JITSI_AUDIENCE'));
        if ($this->audience === '') {
            $this->audience = 'jitsi';
        }
    }

    public function configured()
    {
        return $this->app_id !== '' && $this->secret !== '';
    }

    public function server_url()
    {
        return $this->url;
    }

    public function room_name()
    {
        return 'sekitarin-' . bin2hex(random_bytes(12));
    }

    /**
     * @param string $room
     * @param array  $user   keys: user_id, display_name, email, avatar_url, moderator
     * @param int    $ttl    token lifetime in seconds
     */
    public function token($room, $user, $ttl = 3600)
    {
        if (!$this->configured()) {
            return NULL;
        }
        $now = time();
        $claims = array(
            'aud' => $this->audience,
            'iss' => $this->app_id,
            'sub' => $this->domain,
            'room' => (string) $room,
            'iat' => $now,
            'nbf' => $now - 10,
            'exp' => $now + max(60, (int) $ttl),
            'context' => array(
                'user' => array(
                    'id' => (string) ($user['user_id'] ?? ''),
                    'name' => (string) ($user['display_name'] ?? ''),
                    'email' => (string) ($user['email'] ?? ''),
                    'avatar' => (string) ($user['avatar_url'] ?? ''),
                    'moderator' => !empty($user['moderator']),
                ),
            ),
        );
        $segments = array(
            $this->base64url(json_encode(array('typ' => 'JWT', 'alg' => 'HS256'))),
            $this->base64url(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        );
        $signing = implode('.', $segments);
        $signature = hash_hmac('sha256', $signing, $this->secret, TRUE);
        return $signing . '.' . $this->base64url($signature);
    }

    private function base64url($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
