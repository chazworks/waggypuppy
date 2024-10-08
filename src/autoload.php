<?php

if ( ! file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
    echo( "/vendor/autoload.php not found -- did you run 'composer install'?" );
    exit( 1 );
}

require_once __DIR__ . '/../vendor/autoload.php';
