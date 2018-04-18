<?php

namespace Lichee\Helpers;

/**
 * Class Str
 * @package Lichee\Helpers
 */
class Str
{
    public static function template($string, array $variables)
    {
        if (empty($variables)) {
            return $string;
        }

        $search = array_keys($variables);
        array_walk($search, function (&$item) {
            $item = '{{' . $item . '}}';
        });

        $replace = array_values($variables);

        return str_replace($search, $replace, $string);
    }

    public static function plain($text, $length = 0)
    {
        $text = strip_tags($text);

        $text = str_replace(array("\n", "\r", "\t"), '', $text);
        $text = str_replace('&nbsp;', ' ', $text);
        $text = trim($text);

        $length = (int)$length;
        if (($length > 0) && (mb_strlen($text) > $length)) {
            $text = mb_substr($text, 0, $length, 'UTF-8');
            $text .= '...';
        }

        return $text;
    }

    public static function jsonEncode($data)
    {
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $data = urlencode(json_encode($data));

            return urldecode($data);
        }
    }

    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    public static function after($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    public static function before($subject, $search)
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }

    public static function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function endWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string)$needle) {
                return true;
            }
        }

        return false;
    }

    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    public static function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * Removes comments from a PHP source string.
     *
     * We don't use the PHP php_strip_whitespace() function
     * as we want the content to be readable and well-formatted.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the comments removed
     */
    public static function stripPhpComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $rawChunk = '';
        $output = '';
        $tokens = token_get_all($source);
        $ignoreSpace = false;
        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];
            if (!isset($token[1]) || 'b"' === $token) {
                $rawChunk .= $token;
            } elseif (T_START_HEREDOC === $token[0]) {
                $output .= $rawChunk . $token[1];
                do {
                    $token = $tokens[++$i];
                    $output .= isset($token[1]) && 'b"' !== $token ? $token[1] : $token;
                } while ($token[0] !== T_END_HEREDOC);
                $rawChunk = '';
            } elseif (T_WHITESPACE === $token[0]) {
                if ($ignoreSpace) {
                    $ignoreSpace = false;

                    continue;
                }

                // replace multiple new lines with a single newline
                $rawChunk .= preg_replace(array('/\n{2,}/S'), "\n", $token[1]);
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $ignoreSpace = true;
            } else {
                $rawChunk .= $token[1];

                // The PHP-open tag already has a new-line
                if (T_OPEN_TAG === $token[0]) {
                    $ignoreSpace = true;
                }
            }
        }

        $output .= $rawChunk;

        if (PHP_VERSION_ID >= 70000) {
            // PHP 7 memory manager will not release after token_get_all(), see https://bugs.php.net/70098
            unset($tokens, $rawChunk);
            gc_mem_caches();
        }

        return $output;
    }

}