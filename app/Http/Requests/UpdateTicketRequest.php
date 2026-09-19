<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'assignee_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'subject' => ['sometimes', 'string', 'max:180'],
            'description' => ['sometimes', 'string', 'max:5000'],
        ];
    }
}
