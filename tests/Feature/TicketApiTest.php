<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_ticket_and_assigns_an_sla_deadline(): void
    {
        $response = $this->postJson('/api/tickets', [
            'requester_name' => 'Portfolio Reviewer',
            'requester_email' => 'reviewer@example.com',
            'subject' => 'Production login issue',
            'description' => 'Users receive an unexpected error after submitting valid credentials.',
            'priority' => TicketPriority::Critical->value,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', TicketStatus::Open->value)
            ->assertJsonPath('data.priority', TicketPriority::Critical->value)
            ->assertJsonPath('data.sla_breached', false);

        $this->assertDatabaseCount('tickets', 1);
        $this->assertNotNull(Ticket::first()->due_at);
    }

    public function test_it_filters_tickets_by_status(): void
    {
        Ticket::factory()->count(2)->create(['status' => TicketStatus::Open]);
        Ticket::factory()->create(['status' => TicketStatus::Resolved]);

        $this->getJson('/api/tickets?status=open')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_dashboard_returns_operational_metrics(): void
    {
        Ticket::factory()->count(3)->create(['status' => TicketStatus::Open]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('open', 3);
    }
}
