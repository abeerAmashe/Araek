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
    public function ChargeInvestmentWallet(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'payment_method' => 'required|string',
            'currency' => 'nullable|string',
        ]);

        try {
            $user = auth()->user();
            $wallet = $user->wallets()->where('wallet_type', 'investment')->first();

            if (!$wallet) {
                return response()->json(['message' => 'Wallet not found'], 404);
            }

            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            $charge = $stripe->charges->create([
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'usd',
                'source' => $request->token,
                'description' => $request->description ?? 'Wallet top-up',
            ]);

            $amountInDollars = $request->amount;

            DB::transaction(function () use ($user, $wallet, $charge, $amountInDollars, $request) {
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amountInDollars,
                    'type' => 'deposit',
                    'status' => 'completed',
                    'stripe_payment_id' => $charge->id,
                ]);

                StripePayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_intent_id' => $charge->id,
                    'amount' => $amountInDollars,
                    'currency' => $charge->currency,
                    'payment_method' => $charge->payment_method ?? $request->payment_method,
                    'status' => $charge->status,
                    'receipt_url' => $charge->receipt_url ?? null,
                ]);

                $wallet->increment('balance', $amountInDollars);
            });

            return response()->json(['message' => 'Wallet charged successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * عرض سجل العمليات المالية لمستخدم معين
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Transaction::with(['wallet', 'stripePayment', 'relatedTransaction'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }

        // استخدام get() بدلاً من paginate() لإرجاع كل العمليات بدون معلومات التصفّح
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
