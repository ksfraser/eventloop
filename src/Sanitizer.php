<?php

namespace Eventloop;

/**
 * Provides input sanitization functionality.
 */
class Sanitizer
{
    /**
     * Sanitize input to prevent XSS and other vulnerabilities.
     *
     * @param string $input
     * @return string
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize an array of inputs.
     *
     * @param array $inputs
     * @return array
     */
    public static function sanitizeArray(array $inputs): array
    {
        return array_map([self::class, 'sanitize'], $inputs);
    }

    /**
     * Sanitize a string for use in URLs.
     *
     * @param string $input
     * @return string
     */
    public static function sanitizeUrl(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize a string for use in email addresses.
     *
     * @param string $input
     * @return string
     */
    public static function sanitizeEmail(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }
}