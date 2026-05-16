<?php

namespace App\Support;

class ReturnUrl
{
    /**
     * Sanitize a return URL to prevent open redirects.
     *
     * We only allow internal, relative paths (e.g. "/logs?type=bug&page=2").
     */
    public static function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Allow only internal paths.
        if (!str_starts_with($value, '/')) {
            return null;
        }

        // Block protocol-relative URLs (//evil.com)
        if (str_starts_with($value, '//')) {
            return null;
        }

        // Basic control-char hardening.
        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        return $value;
    }
}

