<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Themes\ThemeApplier;
use App\Themes\ThemePreset;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function index(ThemeApplier $applier)
    {
        return view('backend.theme.index', [
            'presets' => ThemePreset::all(),
            'active'  => $applier->activeApplication(),
        ]);
    }

    public function apply(Request $request, ThemeApplier $applier)
    {
        $data = $request->validate([
            'vertical'  => 'required|in:electronics,supershop,pharmacy,pet_shop',
            'load_demo' => 'nullable',
        ]);

        $applier->apply($data['vertical'], loadDemo: (bool) $request->boolean('load_demo'));

        return redirect()->back()->with('success', 'Theme applied.');
    }

    public function reset(ThemeApplier $applier)
    {
        $applier->reset();
        return redirect()->back()->with('success', 'Theme reset to default look.');
    }
}
