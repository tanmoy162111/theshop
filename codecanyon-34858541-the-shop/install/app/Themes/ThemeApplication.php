<?php
namespace App\Themes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThemeApplication extends Model
{
    protected $fillable = ['vertical', 'demo_loaded', 'applied_at'];
    protected $casts = ['demo_loaded' => 'boolean', 'applied_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(ThemeApplicationItem::class);
    }
}
