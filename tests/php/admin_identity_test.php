<?php
define('BASEPATH', TRUE);
$runtime_environment = getenv('CMS_TEST_ENV') ?: (isset($argv[1]) ? $argv[1] : 'development');
define('ENVIRONMENT', $runtime_environment);

class Admin_identity_test_session
{
    public $data = array();
    public $events = array();

    public function userdata($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : NULL;
    }

    public function set_userdata($key, $value)
    {
        $this->events[] = 'set:' . $key;
        $this->data[$key] = $value;
    }

    public function unset_userdata($key)
    {
        unset($this->data[$key]);
    }

    public function sess_regenerate($destroy = FALSE)
    {
        $this->events[] = 'regenerate:' . ($destroy ? 'true' : 'false');
    }

    public function sess_destroy()
    {
        $this->events[] = 'destroy';
        $this->data = array();
    }
}

$CI = new stdClass();
$CI->session = new Admin_identity_test_session();
function get_instance()
{
    global $CI;
    return $CI;
}

function config_item($key)
{
    $config = array(
        'sess_cookie_name' => 'ci_session',
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => FALSE,
        'sess_samesite' => 'Lax',
    );
    return isset($config[$key]) ? $config[$key] : NULL;
}

$app_path = getenv('CMS_TEST_APP_PATH') ?: '/var/www/html/application';
require $app_path . '/libraries/Admin_identity.php';

function check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
}

putenv('CMS_ADMIN_USERNAME=dev_operator');
putenv('CMS_ADMIN_NAME=Development Operator');
$configured_hash = password_hash('synthetic-test-only-password', PASSWORD_DEFAULT);
putenv('CMS_ADMIN_PASSWORD_HASH=' . $configured_hash);
$identity = new Admin_identity();

if ($runtime_environment !== 'development') {
    check($identity->authenticate('dev_operator', 'synthetic-test-only-password') === FALSE, 'non-development environment must fail closed');
    check($CI->session->userdata('admin_user') === NULL, 'non-development authentication must not create an admin session');
    echo "PASS: non-development authentication fails closed\n";
    exit(0);
}

$user = $identity->authenticate('dev_operator', 'synthetic-test-only-password');
check(is_array($user), 'valid configured development credentials authenticate');
check($user['display_name'] === 'Development Operator', 'identity display name comes from configuration');
check($CI->session->events === array('regenerate:true', 'set:admin_user'), 'session id is regenerated before writing identity');
check($identity->current_user() === $user, 'current user is read from the authenticated session');

putenv('CMS_ADMIN_PASSWORD_HASH');
check($identity->current_user() === NULL, 'missing configured hash fails closed for an existing session');
putenv('CMS_ADMIN_PASSWORD_HASH=' . $configured_hash);

$CI->session->data = array();
$CI->session->events = array();
check($identity->authenticate('dev_operator', 'wrong-test-password') === FALSE, 'invalid password is rejected');
check($identity->authenticate('another_operator', 'synthetic-test-only-password') === FALSE, 'unknown username is rejected');
check($identity->current_user() === NULL, 'invalid credentials do not create an admin session');
check($CI->session->events === array(), 'failed authentication does not regenerate or write an admin session');

$CI->session->data['admin_user'] = $user;
$_COOKIE['ci_session'] = 'synthetic-test-cookie';
$identity->logout();
check($CI->session->userdata('admin_user') === NULL, 'logout destroys the server-side identity');
check(end($CI->session->events) === 'destroy', 'logout invokes session destruction');
check(!isset($_COOKIE['ci_session']), 'logout removes the session cookie from the request scope');

echo "PASS: valid/invalid auth, fixation defense, session identity, logout\n";
