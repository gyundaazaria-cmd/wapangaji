<?php
declare(strict_types=1);

function env_value(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

return [
    'db_host' => env_value('GYUNDA_DB_HOST', '127.0.0.1'),
    'db_port' => env_value('GYUNDA_DB_PORT', '3306'),
    'db_name' => env_value('GYUNDA_DB_NAME', 'gyunda_house'),
    'db_user' => env_value('GYUNDA_DB_USER', 'root'),
    'db_password' => env_value('GYUNDA_DB_PASSWORD'),
    'setup_key' => env_value('GYUNDA_SETUP_KEY'),
];
