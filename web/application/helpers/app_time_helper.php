<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Current timestamp in the configured server storage timezone. */
function app_now()
{
    $CI =& get_instance();
    return $CI->app_time->now_storage_string();
}

/** Server-time value converted to UTC ISO for HTML/API output. */
function app_utc_iso($value)
{
    $CI =& get_instance();
    return $CI->app_time->utc_iso($value);
}

/** Browser wall-clock value converted to the server storage timezone. */
function app_to_storage($value, $client_timezone = NULL)
{
    $CI =& get_instance();
    return $CI->app_time->to_storage($value, $client_timezone);
}
