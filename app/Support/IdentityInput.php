<?php

namespace App\Support;

final class IdentityInput
{
    private const PLACEHOLDER_NAMES = [
        'test',
        'tester',
        'user',
        'student',
        'name',
        'none',
        'unknown',
        'na',
        'n/a',
        'asdf',
        'qwerty',
        'admin',
        'null',
    ];

    private const DISPOSABLE_EMAIL_DOMAINS = [
        'mailinator.com',
        'tempmail.com',
        '10minutemail.com',
        'guerrillamail.com',
        'yopmail.com',
        'trashmail.com',
        'temp-mail.org',
    ];

    public static function normalizeName(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    public static function normalizeEmail(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    public static function normalizeEgyptianPhone(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '20') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    public static function hasPlusAlias(string $email): bool
    {
        $localPart = explode('@', $email)[0] ?? '';

        return str_contains($localPart, '+');
    }

    public static function isDisposableEmailDomain(string $email): bool
    {
        $domain = explode('@', $email)[1] ?? '';

        return in_array($domain, self::DISPOSABLE_EMAIL_DOMAINS, true);
    }

    public static function isSuspiciousName(string $name): bool
    {
        $normalized = self::normalizeName($name);
        $lower = mb_strtolower($normalized);

        if ($normalized === '' || !preg_match('/\p{L}/u', $normalized)) {
            return true;
        }

        if (in_array($lower, self::PLACEHOLDER_NAMES, true)) {
            return true;
        }

        if (preg_match('/^[\.\-_ ]+$/u', $normalized)) {
            return true;
        }

        if (preg_match('/([.\-_])\1{2,}/u', $normalized)) {
            return true;
        }

        if (preg_match('/^(.)\1{3,}$/u', str_replace(' ', '', $lower))) {
            return true;
        }

        if (mb_strlen(preg_replace('/[^\p{L}]+/u', '', $normalized) ?? '') < 2) {
            return true;
        }

        return false;
    }
}
