<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Wallet\Resources\WalletFundingResource;
use App\Domain\Wallet\Resources\WalletResource;
use App\Domain\Wallet\Resources\WalletTransactionResource;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletFunding;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class AdminFintechController extends Controller
{
    public function transactions(Request $request)
    {
        $transactions = WalletTransaction::with('user')
            ->when($request->query('currency'), fn ($q, $c) => $q->where('currency', strtoupper($c)))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return WalletTransactionResource::collection($transactions);
    }

    public function fundings(Request $request)
    {
        $fundings = WalletFunding::with(['user', 'wallet'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return WalletFundingResource::collection($fundings);
    }

    public function wallets(Request $request)
    {
        $wallets = Wallet::with('user')
            ->when($request->query('currency'), fn ($q, $c) => $q->where('currency', strtoupper($c)))
            ->orderByDesc('balance')
            ->paginate((int) $request->query('per_page', 15));

        return WalletResource::collection($wallets);
    }
}
