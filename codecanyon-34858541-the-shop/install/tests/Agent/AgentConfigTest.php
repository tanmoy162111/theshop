<?php
namespace Tests\Agent;

use App\Agent\AgentConfig;

class AgentConfigTest extends AgentTestCase
{
    public function test_get_set_roundtrip_with_default(): void
    {
        $config = app(AgentConfig::class);
        $this->assertSame('unregistered', $config->get('status', 'unregistered'));

        $config->set('status', 'active');
        $config->set('commission_rate', '12.50');

        $this->assertSame('active', $config->get('status'));
        $this->assertSame('12.50', $config->get('commission_rate'));
    }

    public function test_is_registered_reflects_token_presence(): void
    {
        $config = app(AgentConfig::class);
        $this->assertFalse($config->isRegistered());
        $config->set('token', 'abc');
        $this->assertTrue($config->isRegistered());
    }
}
