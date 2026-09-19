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

    public function test_it_searches_tickets_by_reference_subject_or_requester(): void
    {
        Ticket::factory()->create([
            'reference' => 'PD-SEARCH01',
            'requester_name' => 'Jordan Reyes',
            'subject' => 'VPN connection issue',
        ]);
        Ticket::factory()->create([
            'reference' => 'PD-OTHER001',
            'requester_name' => 'Taylor Cruz',
            'subject' => 'Printer configuration',
        ]);

        $this->getJson('/api/tickets?search=VPN')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'PD-SEARCH01');
    }

    public function test_it_adds_and_returns_internal_ticket_notes(): void
    {
        $ticket = Ticket::factory()->create();

        $this->postJson("/api/tickets/{$ticket->id}/notes", [
            'author_name' => 'Support Agent',
            'body' => 'Reproduced the issue and confirmed the API returns a 500 response.',
        ])->assertCreated()
            ->assertJsonPath('data.author_name', 'Support Agent');

        $this->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.note_count', 1)
            ->assertJsonPath('data.notes.0.author_name', 'Support Agent');

        $this->assertDatabaseCount('ticket_notes', 1);
    }

    public function test_dashboard_returns_operational_metrics(): void
    {
        Ticket::factory()->count(3)->create([
            'status' => TicketStatus::Open,
            'assignee_id' => null,
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('open', 3)
            ->assertJsonPath('unassigned', 3);
    }
}
