<?php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class PharmacyPreset extends ThemePreset
{
    public function key(): string { return 'pharmacy'; }
    public function label(): string { return 'Pharmacy'; }
    public function baseColor(): string { return '#0D9488'; }
    public function sectionTitles(): array { return ['Medicines', 'Wellness', 'Personal Care']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Pharmacy', 'tagline' => 'Your health, delivered'],
            ['slot' => 'home_banner_1_img', 'title' => 'Wellness', 'tagline' => 'Feel your best'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Medicines', 'children' => ['OTC', 'Prescription']],
                ['name' => 'Vitamins & Supplements', 'children' => []],
                ['name' => 'Personal Care', 'children' => []],
                ['name' => 'Baby Care', 'children' => []],
            ],
            'products' => [
                ['name' => 'Demo Pain Relief Tablets', 'category' => 'OTC', 'price' => 5.00],
                ['name' => 'Demo Antibiotic (Rx)', 'category' => 'Prescription', 'price' => 14.00],
                ['name' => 'Demo Vitamin C 1000mg', 'category' => 'Vitamins & Supplements', 'price' => 9.50],
                ['name' => 'Demo Hand Sanitizer', 'category' => 'Personal Care', 'price' => 3.20],
                ['name' => 'Demo Baby Lotion', 'category' => 'Baby Care', 'price' => 6.80],
            ],
        ];
    }
}
