<?php
namespace App\Themes;

use Illuminate\Database\Eloquent\Model;

class ThemeApplicationItem extends Model
{
    protected $fillable = ['theme_application_id', 'kind', 'ref_id', 'setting_type', 'prior_value'];
}
