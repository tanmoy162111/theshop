<?php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class ElectronicsPreset extends ThemePreset
{
    public function key(): string { return 'electronics'; }
    public function label(): string { return 'Electronics'; }
    public function baseColor(): string { return '#2563EB'; }
    public function sectionTitles(): array { return ['Featured', 'Best Sellers', 'New Arrivals']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Electronics', 'tagline' => 'Tech that moves you'],
            ['slot' => 'home_banner_1_img', 'title' => 'Top Gadgets', 'tagline' => 'Shop the latest'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Phones', 'children' => ['Smartphones', 'Accessories']],
                ['name' => 'Computers', 'children' => ['Laptops', 'Monitors']],
                ['name' => 'Audio', 'children' => ['Headphones']],
            ],
            'products' => [
                ['name' => 'Demo Smartphone X', 'category' => 'Smartphones', 'price' => 299.00],
                ['name' => 'Demo Phone Case', 'category' => 'Accessories', 'price' => 12.00],
                ['name' => 'Demo Laptop Pro', 'category' => 'Laptops', 'price' => 899.00],
                ['name' => 'Demo 27" Monitor', 'category' => 'Monitors', 'price' => 199.00],
                ['name' => 'Demo Wireless Headphones', 'category' => 'Headphones', 'price' => 79.00],
            ],
        ];
    }
}
