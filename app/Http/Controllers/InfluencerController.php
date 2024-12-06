<?php

namespace App\Http\Controllers;

use App\Models\ChatUnlock;
use App\Models\Influencer;
use App\Models\User;
use Illuminate\Http\Request;

class InfluencerController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/influencers/become",
     *     summary="Become an influencer",
     *     tags={"Influencers"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bio", "gem_cost_per_dm"},
     *             @OA\Property(property="bio", type="string", maxLength=1000, description="The bio of the user"),
     *             @OA\Property(property="gem_cost_per_dm", type="integer", minimum=1, description="Cost of gems per direct message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successfully became an influencer",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You are now an influencer"),
     *             @OA\Property(property="influencer", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="bio", type="string", example="Passionate influencer about tech and gadgets"),
     *                 @OA\Property(property="gem_cost_per_dm", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is already an influencer",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User is already an influencer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="bio", type="array",
     *                     @OA\Items(type="string", example="The bio field is required.")
     *                 ),
     *                 @OA\Property(property="gem_cost_per_dm", type="array",
     *                     @OA\Items(type="string", example="The gem_cost_per_dm must be at least 1.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function becomeInfluencer(Request $request)
    {
        $validated = $request->validate([
            'bio' => 'required|string|max:1000',
            'gem_cost_per_dm' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        if ($user->influencer) {
            return response()->json(['message' => 'User is already an influencer'], 400);
        }

        $influencer = Influencer::create([
            'user_id' => $user->id,
            'bio' => $validated['bio'],
            'gem_cost_per_dm' => $validated['gem_cost_per_dm'],
        ]);
        $user->syncRoles(["influencer"]);

        return response()->json([
            'message' => 'You are now an influencer',
            'influencer' => $influencer,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/influencers",
     *     summary="List all influencers",
     *     description="Retrieve a paginated list of all influencers, excluding the authenticated user's influencer profile if they are an influencer.",
     *     operationId="listInfluencers",
     *     tags={"Influencers"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of influencers per page (default is 10).",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with a paginated list of influencers.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", description="Current page number."),
     *             @OA\Property(property="data", type="array", description="List of influencers.",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="Influencer ID."),
     *                     @OA\Property(property="user_id", type="integer", description="User ID of the influencer."),
     *                     @OA\Property(property="bio", type="string", description="Bio of the influencer."),
     *                     @OA\Property(property="gem_cost_per_dm", type="integer", description="Gem cost per direct message."),
     *                     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp of when the influencer was created."),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp of when the influencer was last updated.")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", description="Total number of influencers."),
     *             @OA\Property(property="per_page", type="integer", description="Number of influencers per page."),
     *             @OA\Property(property="last_page", type="integer", description="Last page number."),
     *             @OA\Property(property="next_page_url", type="string", nullable=true, description="URL of the next page."),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true, description="URL of the previous page.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, missing or invalid token.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function listInfluencers(Request $request)
    {
        $authUser = $request->user();
        $authUserId = $authUser->id;
        $perPage = $request->input('per_page', 10);
        $influencers = Influencer::with('user:id,name,email')
            ->where('user_id', '!=', $authUserId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $influencers->getCollection()->transform(function ($influencer) use ($authUserId) {
            $influencer->chat_unlocked = ChatUnlock::where('user_id', $authUserId)
                ->where('influencer_id', $influencer->id)
                ->exists();
            return $influencer;
        });

        return response()->json($influencers, 200);
    }
}
