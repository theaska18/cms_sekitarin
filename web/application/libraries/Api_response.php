<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** JSON response helpers shared by every /api/v1 controller. */
class Api_response
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function json($payload, $status = 200, $allow = '')
    {
        if ($allow !== '') {
            $this->CI->output->set_header('Allow: ' . $allow);
        }
        $this->CI->output
            ->set_status_header((int) $status)
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function error($code, $message, $status, $fields = NULL)
    {
        $error = array('code' => (string) $code, 'message' => (string) $message);
        if (is_array($fields) && $fields) {
            $error['fields'] = $fields;
        }
        $this->json(array('error' => $error), $status);
    }

    public function ok($payload = array(), $status = 200)
    {
        $this->json($payload, $status);
    }
}
