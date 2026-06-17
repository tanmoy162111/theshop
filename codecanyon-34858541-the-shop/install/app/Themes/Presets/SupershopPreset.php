<?php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class SupershopPreset extends ThemePreset
{
    public function key(): string { return 'supershop'; }
    public function label(): string { return 'Supershop / Grocery'; }
    public function baseColor(): string { return '#16A34A'; }
    public function sectionTitles(): array { return ['Daily Deals', 'Groceries', 'Household']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Supershop', 'tagline' => 'Fresh, every day'],
            ['slot' => 'home_banner_1_img', 'title' => 'Daily Deals', 'tagline' => 'Save on essentials'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Fruits & Vegetables', 'children' => []],
                ['name' => 'Beverages', 'children' => []],
                ['name' => 'Snacks', 'children' => []],
                ['name' => 'Household', 'children' => ['Cleaning', 'Paper']],
            ],
            'products' => [
                ['name' => 'Demo Fresh Apples 1kg', 'category' => 'Fruits & Vegetables', 'price' => 3.50],
                ['name' => 'Demo Orange Juice 1L', 'category' => 'Beverages', 'price' => 2.20],
                ['name' => 'Demo Potato Chips', 'category' => 'Snacks', 'price' => 1.80],
                ['name' => 'Demo Dish Soap', 'category' => 'Cleaning', 'price' => 2.90],
                ['name' => 'Demo Paper Towels', 'category' => 'Paper', 'price' => 4.00],
            ],
        ];
    }
}
