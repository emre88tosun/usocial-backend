<?php

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\CometChatService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected CometChatService $cometChatService;

    public function __construct(CometChatService $cometChatService)
    {
        $this->cometChatService = $cometChatService;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", format="password", example="Test123!", description="User's password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Test123!", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The email field is required.")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('standard user');
        try {
            $this->cometChatService->post("users", [
                "uid" => $user->id,
                "name" => $user->name,
            ]);
        } catch (\Exception $e) {
            Log::error("CometChat user creation failed: " . $e);
        }
        event(new Registered($user));

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login a user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", format="password", example="Test123!", description="User's password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="example-api-token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid login credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid login credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The email field is required.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validatedData['email'])->first();

        if (!$user || !Hash::check($validatedData['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $chatToken = "";
        try {
            $result = $this->cometChatService->post("users/" . $user->id . "/auth_tokens");
            $chatToken = $result['data']['authToken'];
        } catch (\Exception $e) {
            Log::error("CometChat login failed: " . $e);
        }
        $expiresAt = Carbon::now()->addMinutes(config('sanctum.ac_expiration'));
        $token = $user->createToken('auth_token', [TokenAbility::ACCESS_API->value], $expiresAt)->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')))->plainTextToken;

        return response()->json([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'chat_token' => $chatToken,
            'expires_at' => $expiresAt->timestamp,
            'user' => $user,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Logout the authenticated user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User is not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        try {
            $this->cometChatService->delete("users/" . $request->user()->id . "/auth_tokens");
        } catch (\Exception $e) {
            Log::error("CometChat logout failed: " . $e);
        }

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Get authenticated user's details",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1, description="User ID"),
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com", description="User's email address"),
     *             @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-01T12:00:00Z", description="Account creation date"),
     *             @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-02T12:00:00Z", description="Last account update date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh-token",
     *     summary="Refresh access and refresh tokens",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tokens refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *             @OA\Property(property="refresh_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User is not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request)
    {
        $request->user()->tokens()->delete();
        $chatToken = "";
        try {
            $result = $this->cometChatService->post("users/" . $request->user()->id . "/auth_tokens");
            $chatToken = $result['data']['authToken'];
        } catch (\Exception $e) {
            Log::error("CometChat login failed: " . $e);
        }
        $expiresAt = Carbon::now()->addMinutes(config('sanctum.ac_expiration'));
        $token = $request->user()->createToken('auth_token', [TokenAbility::ACCESS_API->value], $expiresAt)->plainTextToken;
        $refreshToken = $request->user()->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')))->plainTextToken;
        return response()->json([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'chat_token' => $chatToken,
            'expires_at' => $expiresAt->timestamp
        ]);
    }
}
