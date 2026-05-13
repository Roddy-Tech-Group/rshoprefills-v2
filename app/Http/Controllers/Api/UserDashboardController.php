<?php

namespace App\Http\Controllers\Api;

use App\Domain\Wallet\Resources\WalletResource;
use App\Domain\Wallet\Resources\WalletTransactionResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $wallets = $user->wallets ?? []; // Requires adding HasMany wallets to User model
        $recentTransactions = $user->walletTransactions()->latest()->take(5)->get();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'wallets' => WalletResource::collection($wallets),
            'recent_transactions' => WalletTransactionResource::collection($recentTransactions),
        ]);
    }
}
