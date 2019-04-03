<?php

namespace Sentrasoft\Netutils\Test;

use Tests\TestCase;

class NetutilsTest extends TestCase
{
    /**
     * Test ping() function.
     *
     * @return void
     */
    public function testPing()
    {
        $network = new \Netutils;
        $latency = $network::ping('127.0.0.1')->ping();

        $this->assertContains('ms', $latency);
    }
}
