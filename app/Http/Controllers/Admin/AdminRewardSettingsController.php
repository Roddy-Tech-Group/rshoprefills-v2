<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminRewardSettingsController extends Controller
{
    public function index()
    {
        $settings = \App\Models\Setting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->value];
        });

        return response()->json([
            'data' => $settings,
        ]);
    }

    public function update(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = \App\Models\Setting::where('key', $key)->first();
            if ($setting) {
                // Ensure correct type cast
                if ($setting->type === 'boolean') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } elseif ($setting->type === 'integer') {
                    $value = (int) $value;
                } elseif ($setting->type === 'float') {
                    $value = (float) $value;
                }

                \App\Models\Setting::set($key, $value, $setting->type, $setting->description);
            }
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
        ]);
    }
}
