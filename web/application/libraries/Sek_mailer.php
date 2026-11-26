<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transactional email placeholder.
 *
 * No SMTP provider is configured yet, so messages are written to
 * application/logs/sekitar-mail.log. Replace send() with the chosen provider
 * before email verification is required in production.
 */
class Sek_mailer
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function send_token($email, $purpose, $token, $ttl_seconds)
    {
        $label = $purpose === 'verify_email' ? 'Verifikasi email' : 'Reset password';
        $line = sprintf(
            "[%s] to=%s purpose=%s expires_in=%ss token=%s\n",
            gmdate('c'),
            (string) $email,
            (string) $purpose,
            (int) $ttl_seconds,
            (string) $token
        );
        $path = APPPATH . 'logs/sekitar-mail.log';
        @file_put_contents($path, $line, FILE_APPEND);
        @chmod($path, 0600);

        if (ENVIRONMENT !== 'production') {
            log_message('info', 'Sekitar mail queued for ' . $email . ' (' . $label . ').');
        }
        return TRUE;
    }
}
