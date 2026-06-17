<?php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class PetShopPreset extends ThemePreset
{
    public function key(): string { return 'pet_shop'; }
    public function label(): string { return 'Pet Shop'; }
    public function baseColor(): string { return '#D97706'; }
    public function sectionTitles(): array { return ['Dogs', 'Cats', 'Pet Food & Accessories']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Pet Shop', 'tagline' => 'Everything your pet loves'],
            ['slot' => 'home_banner_1_img', 'title' => 'Pet Food', 'tagline' => 'Healthy & happy'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Dogs', 'children' => ['Dog Food', 'Dog Toys']],
                ['name' => 'Cats', 'children' => ['Cat Food', 'Cat Litter']],
                ['name' => 'Birds', 'children' => []],
                ['name' => 'Aquarium', 'children' => []],
            ],
            'products' => [
                ['name' => 'Demo Dry Dog Food 2kg', 'category' => 'Dog Food', 'price' => 18.00],
                ['name' => 'Demo Squeaky Bone Toy', 'category' => 'Dog Toys', 'price' => 6.00],
                ['name' => 'Demo Cat Food Salmon', 'category' => 'Cat Food', 'price' => 15.00],
                ['name' => 'Demo Clumping Litter 5L', 'category' => 'Cat Litter', 'price' => 9.00],
                ['name' => 'Demo Bird Seed Mix', 'category' => 'Birds', 'price' => 7.50],
            ],
        ];
    }
}
