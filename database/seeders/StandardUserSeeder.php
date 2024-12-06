<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\CometChatService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class StandardUserSeeder extends Seeder
{
    protected $cometChatService;

    public function __construct(CometChatService $cometChatService)
    {
        $this->cometChatService = $cometChatService;
    }

    public function run()
    {
        $role = Role::where('name', 'standard user')->first();

        if (!$role) {
            $role = Role::create(['name' => 'standard user']);
        }

        \App\Models\User::factory(10)->create()->each(function ($user) use ($role) {
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
        });
    }
}
