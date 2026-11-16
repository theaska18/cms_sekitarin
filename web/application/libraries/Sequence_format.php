<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Renders brace-delimited sequence tokens while preserving literal text. */
class Sequence_format
{
    /**
     * Render a format such as EMP_{NNNNNN}_{Y}{M}{D}.
     * A run of N tokens controls zero-padding width; date tokens are case-sensitive.
     * Returns FALSE for malformed or unsupported tokens.
     */
    public function generate($format, $value, $date = NULL)
    {
        if (!is_string($format) || !is_numeric($value) || (int) $value < 0) {
            return FALSE;
        }
        if ($date === NULL) {
            $date = new DateTimeImmutable('today');
        } elseif (!$date instanceof DateTimeInterface) {
            return FALSE;
        }

        $result = '';
        $length = strlen($format);
        for ($offset = 0; $offset < $length; $offset++) {
            $character = $format[$offset];
            if ($character === '}') {
                return FALSE;
            }
            if ($character !== '{') {
                if (preg_match('/[A-Za-z]/', $character)) {
                    $start = $offset;
                    while ($offset + 1 < $length && preg_match('/[A-Za-z]/', $format[$offset + 1])) {
                        $offset++;
                    }
                    $literal = substr($format, $start, $offset - $start + 1);
                    if (strspn($literal, 'NdDmMyY') === strlen($literal)) {
                        return FALSE;
                    }
                    $result .= $literal;
                } else {
                    $result .= $character;
                }
                continue;
            }

            $end = strpos($format, '}', $offset + 1);
            if ($end === FALSE) {
                return FALSE;
            }
            $token = substr($format, $offset + 1, $end - $offset - 1);
            if ($token === '' || strpos($token, '{') !== FALSE) {
                return FALSE;
            }
            if (preg_match('/\AN+\z/', $token)) {
                $result .= str_pad((string) (int) $value, strlen($token), '0', STR_PAD_LEFT);
            } elseif ($token === 'd') {
                $result .= $date->format('j');
            } elseif ($token === 'D') {
                $result .= $date->format('d');
            } elseif ($token === 'm') {
                $result .= $date->format('n');
            } elseif ($token === 'M') {
                $result .= $date->format('m');
            } elseif ($token === 'y') {
                $result .= $date->format('y');
            } elseif ($token === 'Y') {
                $result .= $date->format('Y');
            } else {
                return FALSE;
            }
            $offset = $end;
        }
        return $result;
    }

    public function is_valid($format)
    {
        return $this->generate($format, 1, new DateTimeImmutable('2026-11-16')) !== FALSE;
    }
}
