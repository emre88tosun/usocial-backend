<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class PaymentController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/purchase-gems",
     *     summary="Purchase gems for the authenticated user",
     *     tags={"Gems"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"paymentMethodId", "amount"},
     *             @OA\Property(property="paymentMethodId", type="string", description="Stripe payment method ID"),
     *             @OA\Property(property="amount", type="integer", description="Number of gems to purchase", example=10, minimum=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment successful"),
     *             @OA\Property(property="transaction", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="amount", type="integer", example=10),
     *                 @OA\Property(property="transaction_type", type="string", example="purchase"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="stripe_transaction_id", type="string", example="ch_1Nm6x2FEXAMPLE")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Payment failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment failed"),
     *             @OA\Property(property="error", type="string", example="Card declined")
     *         )
     *     )
     * )
     */
    public function purchaseGems(Request $request)
    {
        $validated = $request->validate([
            'paymentMethodId' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $gemPrice = 1;
        $totalPrice = $validated['amount'] * $gemPrice;

        try {
            $charge = $user->charge(
                round($totalPrice * 100),
                $validated['paymentMethodId'],
                [
                    'metadata' => [
                        'gem_amount' => $validated['amount'],
                    ],
                    "return_url" => "http://laravel.test"
                ]
            );
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'transaction_type' => 'purchase',
                'status' => 'completed',
                'stripe_transaction_id' => $charge->id,
            ]);
            $user->gems->increment('gem_count', $validated['amount']);

            return response()->json(['message' => 'Payment successful', 'transaction' => $transaction], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Payment failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/create-intent",
     *     summary="Create a payment intent for purchasing gems",
     *     description="Generates a payment intent for the given gem amount. Requires the user to be authenticated.",
     *     tags={"Gems"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="integer", description="Number of gems to purchase", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client secret for the payment intent",
     *         @OA\JsonContent(
     *             @OA\Property(property="clientSecret", type="string", description="Client secret for Stripe payment intent", example="pi_1EydQl2eZvKYlo2Cxl97")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message", example="The amount field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message", example="Something went wrong while processing the payment intent.")
     *         )
     *     )
     * )
     */
    public function createIntent(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $gemPrice = 1;
        $totalPrice = $validated['amount'] * $gemPrice;

        try {
            $intent = $user->pay($totalPrice * 100);
            return response()->json(['clientSecret' => $intent->client_secret]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/finalize-purchase",
     *     summary="Finalize the purchase of gems",
     *     description="Completes the gem purchase by recording the transaction and updating the user's gem balance. Requires the user to be authenticated.",
     *     tags={"Gems"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"paymentId", "amount"},
     *             @OA\Property(property="paymentId", type="string", description="Stripe payment ID", example="pi_1EyjdV2eZvKYlo2Cxl98"),
     *             @OA\Property(property="amount", type="integer", description="Number of gems purchased", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment finalized successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Success message", example="Payment finalized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message", example="The paymentId field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */
    public function finalizePurchase(Request $request)
    {
        $validated = $request->validate([
            'paymentId' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        Transaction::create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'transaction_type' => 'purchase',
            'status' => 'completed',
            'stripe_transaction_id' => $validated['paymentId'],
        ]);
        $user->gems->increment('gem_count', $validated['amount']);

        return response()->json(['message' => 'Payment finalized'], 200);
    }
}
