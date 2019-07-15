<?php

namespace Tests;

use VOLLdigital\LaravelNtlm\Client;

class ClientTest extends \PHPUnit\Framework\TestCase
{

    public function testNoConfiguration()
    {
        $client = new Client([]);

        $this->expectException(\RunTimeException::class);
        $client->fetchCollection('test');
    }

    public function testEmptyResponse()
    {
        $client = new Client([
            'ntlm_host' => 'test',
            'ntlm_user' => 'test',
            'ntlm_password' => 'test',
            'ntlm_auth_type' => 'credentials',
            'ntlm_token' => ''
        ]);

        $this->assertNull($client->fetchCollection('test'));
    }

}
