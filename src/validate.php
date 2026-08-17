<?php
function is_valid_email(string $email): bool
{
    return strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_password(string $password): bool
{
    // Length/format check only — never log or echo the value.
    return strlen($password) >= 8 && strlen($password) <= 128;
}

function is_valid_full_name(string $name): bool
{
    return preg_match('/^[\p{L}\s\.\'-]{2,100}$/u', $name) === 1;
}

function is_valid_course_id($id): bool
{
    return filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0;
}
