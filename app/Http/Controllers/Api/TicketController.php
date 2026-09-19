<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketSlaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function __construct(private readonly TicketSlaService $sla)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $tickets = Ticket::query()
            ->with('assignee')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): TicketResource
    {
        $priority = TicketPriority::from($request->validated('priority'));

        $ticket = Ticket::create([
            ...$request->safe()->except('priority'),
            'priority' => $priority,
            'status' => TicketStatus::Open,
            'reference' => 'PD-'.strtoupper(Str::random(8)),
            'due_at' => $this->sla->dueAt($priority),
        ]);

        return new TicketResource($ticket->load('assignee'));
    }

    public function show(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket->load('assignee'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $data = $request->validated();

        if (isset($data['priority']) && $data['priority'] !== $ticket->priority->value) {
            $priority = TicketPriority::from($data['priority']);
            $data['priority'] = $priority;
            $data['due_at'] = $this->sla->dueAt($priority);
        }

        if (($data['status'] ?? null) === TicketStatus::Resolved->value && !$ticket->resolved_at) {
            $data['resolved_at'] = now();
        }

        if (($data['status'] ?? null) && $data['status'] !== TicketStatus::Resolved->value) {
            $data['resolved_at'] = null;
        }

        $ticket->update($data);

        return new TicketResource($ticket->fresh()->load('assignee'));
    }
}
