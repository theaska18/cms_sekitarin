<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central time conversion.
 *
 * Storage uses the configured server timezone. Browser-facing values are
 * converted to UTC ISO and formatted to the browser local zone by app-time.js.
 */
class App_time
{
    private $storage;

    public function __construct()
    {
        $this->storage = new DateTimeZone(date_default_timezone_get());
    }

    public function storage_timezone()
    {
        return $this->storage;
    }

    public function client_timezone($candidate = NULL)
    {
        if ($candidate === NULL) {
            $CI =& get_instance();
            $candidate = $CI->input->post('client_timezone', TRUE);
        }
        $candidate = is_string($candidate) ? trim($candidate) : '';
        if ($candidate !== '' && in_array($candidate, DateTimeZone::listIdentifiers(), TRUE)) {
            return new DateTimeZone($candidate);
        }
        return $this->storage;
    }

    public function now_storage_string()
    {
        return (new DateTimeImmutable('now', $this->storage))->format('Y-m-d H:i:s');
    }

    /** Convert a stored server-time value to an unambiguous UTC ISO instant. */
    public function utc_iso($value)
    {
        if ($value === NULL || trim((string) $value) === '') {
            return NULL;
        }
        try {
            $date = new DateTimeImmutable((string) $value, $this->storage);
        } catch (Exception $error) {
            return NULL;
        }
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Convert a browser wall-clock value to the server storage timezone.
     * Accepts "Y-m-d H:i:s", "Y-m-d\TH:i", and "Y-m-d\TH:i:s".
     */
    public function to_storage($value, $client_timezone = NULL)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return NULL;
        }
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }
        try {
            $date = new DateTimeImmutable($value, $this->client_timezone($client_timezone));
        } catch (Exception $error) {
            return NULL;
        }
        return $date->setTimezone($this->storage)->format('Y-m-d H:i:s');
    }

    /** Format a stored timestamp in the browser timezone for server exports. */
    public function localize($value, $client_timezone = NULL)
    {
        if ($value === NULL || trim((string) $value) === '') {
            return '';
        }
        try {
            $date = new DateTimeImmutable((string) $value, $this->storage);
        } catch (Exception $error) {
            return '';
        }
        return $date->setTimezone($this->client_timezone($client_timezone))->format('Y-m-d H:i:s');
    }
}
