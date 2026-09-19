<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketSlaService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(TicketSlaService $sla): JsonResponse
    {
        $openStatuses = [TicketStatus::Open->value, TicketStatus::InProgress->value];
        $active = Ticket::query()->whereIn('status', $openStatuses)->get();

        return response()->json([
            'total' => Ticket::count(),
            'open' => Ticket::where('status', TicketStatus::Open)->count(),
            'in_progress' => Ticket::where('status', TicketStatus::InProgress)->count(),
            'resolved' => Ticket::where('status', TicketStatus::Resolved)->count(),
            'critical' => Ticket::where('priority', TicketPriority::Critical)->whereIn('status', $openStatuses)->count(),
            'sla_breached' => $active->filter(fn (Ticket $ticket) => $sla->isBreached($ticket->due_at))->count(),
        ]);
    }
}
