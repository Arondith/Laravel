<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketSlaService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $priority = fake()->randomElement(TicketPriority::cases());
        $status = fake()->randomElement(TicketStatus::cases());

        return [
            'reference' => 'PD-'.strtoupper(Str::random(8)),
            'requester_name' => fake()->name(),
            'requester_email' => fake()->safeEmail(),
            'subject' => fake()->randomElement([
                'Cannot access account',
                'Checkout page returns an error',
                'Need help configuring deployment',
                'Password reset email not arriving',
                'Dashboard data is not refreshing',
            ]),
            'description' => fake()->paragraphs(2, true),
            'priority' => $priority,
            'status' => $status,
            'due_at' => app(TicketSlaService::class)->dueAt($priority),
            'resolved_at' => in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true) ? now() : null,
        ];
    }
}
