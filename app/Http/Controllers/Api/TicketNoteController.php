<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketNoteRequest;
use App\Http\Resources\TicketNoteResource;
use App\Models\Ticket;

class TicketNoteController extends Controller
{
    public function store(StoreTicketNoteRequest $request, Ticket $ticket): TicketNoteResource
    {
        $note = $ticket->notes()->create($request->validated());

        return new TicketNoteResource($note);
    }
}
