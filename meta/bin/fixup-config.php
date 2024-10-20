<?php
declare(strict_types=1);

// From https://github.com/wp-cli/config-command/blob/main/src/Config_Command.php
const VALID_KEY_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';

function generate_salt(): string
{
    $out = '';
    foreach ([
                 'AUTH_KEY',
                 'SECURE_AUTH_KEY',
                 'LOGGED_IN_KEY',
                 'NONCE_KEY',
                 'AUTH_SALT',
                 'SECURE_AUTH_SALT',
                 'LOGGED_IN_SALT',
                 'NONCE_SALT',
             ] as $name) {
        $out .= sprintf("const %-20s = '%s';\n", $name, unique_key());
    }
    return $out;
}

function unique_key(int $length = 64): string
{
    $chars = VALID_KEY_CHARACTERS;
    $key = '';
    $len = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[random_int(0, $len)];
    }

    return $key;
}

$file = $argv[1] ?? 'wp-config.php';

$contents = file_get_contents($file);
$salt = generate_salt();
$contents = preg_replace('/^## BEGIN: keys.*## END: keys/sm', "## BEGIN: keys\n{$salt}## END: keys", $contents);

file_put_contents($file, $contents);
