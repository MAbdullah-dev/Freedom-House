<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use Tests\TestCase;

class DatabaseConfigTest extends TestCase
{
    public function test_mysql_ssl_option_uses_the_available_pdo_constant(): void
    {
        $source = file_get_contents(config_path('database.php'));
        $ca = 'C:/freedom-house-unused-ca.pem';
        $repository = Env::getRepository();
        $previous = $repository->get('MYSQL_ATTR_SSL_CA');

        $repository->set('MYSQL_ATTR_SSL_CA', $ca);

        try {
            $config = require config_path('database.php');
            $constant = defined('Pdo\Mysql::ATTR_SSL_CA')
                ? \Pdo\Mysql::ATTR_SSL_CA
                : PDO::MYSQL_ATTR_SSL_CA;

            $this->assertStringContainsString("defined('Pdo\\Mysql::ATTR_SSL_CA')", $source);
            $this->assertSame($ca, $config['connections']['mysql']['options'][$constant]);
            $this->assertSame($ca, $config['connections']['mariadb']['options'][$constant]);
        } finally {
            $repository->set('MYSQL_ATTR_SSL_CA', is_string($previous) ? $previous : '');
        }
    }
}
