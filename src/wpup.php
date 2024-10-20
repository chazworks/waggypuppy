<?php
declare(strict_types=1);

function wpup_api_url(string $path = ''): string {
    return WPUP_API_URL . $path;
}
