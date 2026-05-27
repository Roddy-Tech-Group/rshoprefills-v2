<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function index()
    {
        return response()->json([
            'rcoin' => [
                'enabled' => (bool) Setting::get('rcoin_enabled', true),
                'cashback_percentage' => (float) Setting::get('cashback_percentage', 1.0),
                'usd_rate' => (float) Setting::get('rcoin_usd_rate', 0.005),
                'redemption' => [
                    'enabled' => (bool) Setting::get('redemption_enabled', true),
                    'min_rcoin' => (int) Setting::get('redemption_min_rcoin', 2000),
                    'max_percentage' => (float) Setting::get('redemption_max_percentage', 30.0),
                ]
            ],
            // Add other frontend configs here in the future
        ]);
    }
}
