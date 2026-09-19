<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::factory()->create([
            'name' => 'Demo Support Agent',
            'email' => 'agent@pulsedesk.test',
        ]);

        Ticket::factory()
            ->count(18)
            ->create()
            ->each(function (Ticket $ticket) use ($agent) {
                if (fake()->boolean(65)) {
                    $ticket->update(['assignee_id' => $agent->id]);
                }
            });
    }
}
