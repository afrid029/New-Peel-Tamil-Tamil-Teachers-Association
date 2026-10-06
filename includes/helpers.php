<?php

/**
 * Misc helper functions
 */

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function generateTempPassword(int $length = 10): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function requiredFields(array $fields, array $source): ?string
{
    foreach ($fields as $f) {
        if (!isset($source[$f]) || trim((string) $source[$f]) === '') {
            return "Field '{$f}' is required.";
        }
    }
    return null;
}

function paginationParams(): array
{
    $page  = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    return [$page, $limit, $offset];
}

function getFreeLowId(PDO $db): int
{
    // one query: all used ids below 1000
    $used = array_flip(
        $db->query('SELECT id FROM users WHERE id < 1000')->fetchAll(PDO::FETCH_COLUMN)
    );

    // your logic: count + 1, then keep going until free
    $id = count($used) + 1;
    while ($id < 1000 && isset($used[$id])) {
        $id++;
    }

    // if we ran past 999, fall back to the lowest free gap
    if ($id >= 1000) {
        for ($i = 1; $i < 1000; $i++) {
            if (!isset($used[$i])) return $i;
        }
        throw new RuntimeException('No free ids left below 1000.');
    }

    return $id;
}
