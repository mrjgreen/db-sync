<?php

return [
    [
        'host'      => 'localhost',
        'driver'    => 'mysql',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    [
        'host'      => 'localhost',
        'driver'    => 'mysql',
        'username'  => 'root',
        'password'  => 'password',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ]
];


,
"require": {
  "psr/log": "*",
  "symfony/console": "^2.7",
  "mrjgreen/database": "^2.4",
  "symfony/debug": "^2.7",
  "ramsey/array_column": "^1.1"
},
"require-dev": {
  "satooshi/php-coveralls": "dev-master",
  "phpunit/phpunit": "^4.8"
}
