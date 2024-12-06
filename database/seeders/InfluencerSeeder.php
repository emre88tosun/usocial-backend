<?php

namespace Database\Seeders;

use App\Models\Influencer;
use App\Models\User;
use App\Services\CometChatService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class InfluencerSeeder extends Seeder
{
    protected $cometChatService;

    public function __construct(CometChatService $cometChatService)
    {
        $this->cometChatService = $cometChatService;
    }

    public function run()
    {
        $role = Role::where('name', 'influencer')->first();

        if (!$role) {
            $role = Role::create(['name' => 'influencer']);
        }

        \App\Models\User::factory(30)->create()->each(function ($user) use ($role) {
            $user->assignRole($role);
            try {
                $this->cometChatService->post("users", [
                    "uid" => $user->id,
                    "name" => $user->name,
                ]);
            } catch (\Exception $e) {
                Log::error("CometChat user creation failed: " . $e);
            }
            event(new Registered($user));
            Influencer::create([
                'user_id' => $user->id,
                'bio' => 'This is a sample bio for influencer ' . $user->name,
                'gem_cost_per_dm' => rand(10, 100),
            ]);
        });
    }
}
