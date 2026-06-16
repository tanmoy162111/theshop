<?php
namespace App\Agent;

use Illuminate\Database\Eloquent\Model;

class AgentSetting extends Model
{
    protected $fillable = ['key', 'value'];
}
