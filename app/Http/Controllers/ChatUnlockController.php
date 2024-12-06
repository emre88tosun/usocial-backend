<?php

namespace App\Http\Controllers;

use App\Models\ChatUnlock;
use App\Models\Influencer;
use App\Services\CometChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatUnlockController extends Controller
{
    protected CometChatService $cometChatService;

    public function __construct(CometChatService $cometChatService)
    {
        $this->cometChatService = $cometChatService;
    }

    /**
     * @OA\Post(
     *     path="/api/chat/unlock",
     *     summary="Unlock chat with an influencer",
     *     description="Deduct gems from the user and unlock chat with the specified influencer.",
     *     tags={"Chat"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"influencer_id"},
     *             @OA\Property(property="influencer_id", type="integer", example=1, description="ID of the influencer to unlock chat with")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat unlocked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Chat unlocked successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Not enough gems or chat already unlocked",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not enough gems")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Influencer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Influencer not found")
     *         )
     *     )
     * )
     */
    public function unlock(Request $request)
    {
        $user = Auth::user();
        $influencerId = $request->input('influencer_id');
        $influencer = Influencer::findOrFail($influencerId);
        if (ChatUnlock::where('user_id', $user->id)->where('influencer_id', $influencerId)->exists()) {
            return response()->json(['message' => 'Chat already unlocked'], 400);
        }
        $requiredGems = $influencer->gem_cost_per_dm;
        if ($user->gems->gem_count < $requiredGems) {
            return response()->json(['message' => 'Not enough gems'], 400);
        }
        $user->gems->gem_count -= $requiredGems;
        $user->gems->save();

        ChatUnlock::create([
            'user_id' => $user->id,
            'influencer_id' => $influencerId,
        ]);

        try {
            $this->cometChatService->post("messages", [
                "category" => "message",
                "type" => "text",
                "receiver" => (string)$influencer->user->id,
                "receiverType" => "user",
                "data" => array("text" => "Hello")
            ], $user->id);
        } catch (\Exception $e) {
            Log::error("CometChat unlock failed: " . $e);
        }

        return response()->json(['message' => 'Chat unlocked successfully'], 200);
    }
}
