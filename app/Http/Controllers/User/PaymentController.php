<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\StripePayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Transaction::with(['wallet', 'stripePayment', 'relatedTransaction'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }

        $transactions = $query->get();

        $operations = [];
        foreach ($transactions as $index => $tx) {
            $amount = number_format($tx->amount, 2);
            $description = match ($tx->type) {
                'deposit'       => "Deposited \$$amount",
                'withdrawal'    => "Withdrew \$$amount",
                'transfer_in'   => "Received transfer of \$$amount",
                'transfer_out'  => "Sent transfer of \$$amount",
                'dividend'      => "Received dividend of \$$amount",
                'fee'           => "Fee charged: \$$amount",
                default         => "Unknown transaction of \$$amount"
            };

            $operations[] = [
                'title'       => "Operation " . ($index + 1),
                'description' => $description,
                'type'        => $tx->type,
                'amount'      => $tx->amount,
                'status'      => $tx->status,
                'created_at'  => $tx->created_at,
                'wallet'      => [
                    'currency'     => $tx->wallet->currency ?? null,
                    'wallet_type'  => $tx->wallet->wallet_type ?? null,
                ],
                'stripe'      => $tx->stripePayment ? [
                    'receipt_url' => $tx->stripePayment->receipt_url,
                ] : null,
            ];
        }

        return response()->json([
            'user' => Arr::only($user->toArray(), ['id', 'name', 'email']),
            'operations' => $operations
        ]);
    }
}