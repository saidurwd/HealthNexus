<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;

/** A person's own display choices: language and whether figures use Bangla digits. Nothing here changes data, only how it is shown to this person. */
class PreferencesController extends Controller
{
    public function edit(Request $request, SettingsService $settings)
    {
        return view('preferences', ['locales' => $settings->get('localization.supported_locales', []) ?: ['en' => 'English'], 'digits' => $request->user()->bangla_digits]);
    }

    public function update(Request $request, SettingsService $settings)
    {
        $supported = array_keys($settings->get('localization.supported_locales', []) ?: ['en' => 'English']);
        $v = $request->validate(['locale' => ['required', 'in:'.implode(',', $supported)], 'digits' => ['required', 'in:hospital,bangla,latin']]);
        $request->user()->update(['locale' => $v['locale'], 'bangla_digits' => ['hospital' => null, 'bangla' => true, 'latin' => false][$v['digits']]]);
        $request->session()->put('locale', $v['locale']);

        return redirect()->route('preferences')->with('success', __('Saved.'));
    }
}
