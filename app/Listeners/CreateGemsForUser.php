<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Models\Gem;

class CreateGemsForUser
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;
        if (!$user->gems) {
            Gem::create([
                'user_id' => $user->id,
                'gem_count' => 0,
            ]);
        }
    }
}
