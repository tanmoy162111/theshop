<?php
namespace App\Agent;

class AgentConfig
{
    public function get(string $key, ?string $default = null): ?string
    {
        return optional(AgentSetting::where('key', $key)->first())->value ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        AgentSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function isRegistered(): bool
    {
        return ! empty($this->get('token'));
    }
}
