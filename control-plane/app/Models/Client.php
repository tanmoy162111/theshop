<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'business_name','contact_email','primary_domain','status',
        'commission_type','commission_rate','token','app_version',
        'registered_at','approved_at','last_report_at','last_seen_at',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'registered_at' => 'datetime',
        'approved_at' => 'datetime',
        'last_report_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(SalesReport::class);
    }
}
