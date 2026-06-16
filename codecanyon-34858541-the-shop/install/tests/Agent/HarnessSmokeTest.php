<?php
namespace Tests\Agent;

use Illuminate\Support\Facades\Schema;

class HarnessSmokeTest extends AgentTestCase
{
    public function test_harness_boots_and_builds_tables(): void
    {
        $this->assertTrue(Schema::hasTable('agent_settings'));
        $this->assertTrue(Schema::hasTable('orders'));
        $this->assertSame('sqlite_testing', config('database.default'));
    }
}
