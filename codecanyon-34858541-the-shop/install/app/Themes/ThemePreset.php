<?php
namespace App\Themes;

use App\Themes\Presets\ElectronicsPreset;
use App\Themes\Presets\PetShopPreset;
use App\Themes\Presets\PharmacyPreset;
use App\Themes\Presets\SupershopPreset;

abstract class ThemePreset
{
    abstract public function key(): string;
    abstract public function label(): string;
    abstract public function baseColor(): string;

    /** @return string[] exactly 3 section titles */
    abstract public function sectionTitles(): array;

    /** @return array<int,array{slot:string,title:string,tagline:string}> */
    abstract public function banners(): array;

    /** @return array{categories: array, products: array} */
    abstract public function catalog(): array;

    /** @return ThemePreset[] */
    public static function all(): array
    {
        return [
            new ElectronicsPreset(),
            new SupershopPreset(),
            new PharmacyPreset(),
            new PetShopPreset(),
        ];
    }

    public static function for(string $key): ThemePreset
    {
        foreach (self::all() as $preset) {
            if ($preset->key() === $key) {
                return $preset;
            }
        }
        throw new \InvalidArgumentException("Unknown theme preset: {$key}");
    }
}
