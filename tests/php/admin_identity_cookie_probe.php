<?php
putenv('CMS_TEST_ENV=development');
putenv('CMS_TEST_APP_PATH=/var/www/html/application');

$descriptors = array(
    0 => array('pipe', 'r'),
    1 => array('file', '/dev/null', 'a'),
    2 => array('file', '/dev/null', 'a'),
);
$process = proc_open(
    array(PHP_BINARY, '-S', '127.0.0.1:18083', '/tmp/admin_identity_test.php'),
    $descriptors,
    $pipes,
    NULL,
    NULL,
    array('bypass_shell' => TRUE)
);
if (!is_resource($process)) {
    fwrite(STDERR, "FAIL: could not start isolated cookie-test server\n");
    exit(1);
}
fclose($pipes[0]);

$socket = FALSE;
for ($attempt = 0; $attempt < 40; $attempt++) {
    $socket = @stream_socket_client('tcp://127.0.0.1:18083', $errno, $errstr, 0.2);
    if (is_resource($socket)) {
        break;
    }
    usleep(100000);
}

$ok = FALSE;
if (is_resource($socket)) {
    fwrite($socket, "GET / HTTP/1.1\r\nHost: localhost\r\nConnection: close\r\n\r\n");
    $response = stream_get_contents($socket);
    fclose($socket);
    $separator = strpos($response, "\r\n\r\n");
    $headers = $separator === FALSE ? '' : substr($response, 0, $separator);
    $body = $separator === FALSE ? '' : substr($response, $separator + 4);
    preg_match('/^HTTP\/\S+\s+(\d{3})/i', $headers, $status_match);
    preg_match_all('/^Set-Cookie:\s*ci_session=.*$/im', $headers, $cookie_matches);
    $cookie_header = count($cookie_matches[0]) ? strtolower(end($cookie_matches[0])) : '';
    $status_ok = isset($status_match[1]) && (int) $status_match[1] === 200;
    $cookie_cleared = preg_match('/ci_session=(?:;|deleted;)/', $cookie_header)
        && (strpos($cookie_header, 'max-age=0') !== FALSE || strpos($cookie_header, 'expires=') !== FALSE);
    $cookie_attributes = strpos($cookie_header, 'httponly') !== FALSE
        && strpos($cookie_header, 'samesite=lax') !== FALSE;
    $unit_checks = strpos($body, 'PASS: valid/invalid auth') !== FALSE;
    $ok = $status_ok && $cookie_cleared && $cookie_attributes && $unit_checks;
    echo json_encode(array(
        'http_status_200' => (bool) $status_ok,
        'session_cookie_expired' => (bool) $cookie_cleared,
        'cookie_httponly_samesite' => (bool) $cookie_attributes,
        'unit_checks_passed' => (bool) $unit_checks,
    )) . "\n";
} else {
    fwrite(STDERR, "FAIL: isolated cookie-test server did not start\n");
}

proc_terminate($process, 15);
proc_close($process);
exit($ok ? 0 : 1);
