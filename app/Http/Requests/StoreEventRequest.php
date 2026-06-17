<?php

namespace App\Http\Requests;

use App\Models\EventType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $eventType = EventType::find($this->input('event_type_id'));

        if (strtolower($eventType?->name ?? '') === 'reminder' && $this->filled('reminder_date')) {
            $this->merge([
                'start_datetime' => $this->input('reminder_date') . ' 00:00:00',
                'end_datetime' => null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'event_type_id' => ['required', 'exists:event_types,id'],
            'reminder_date' => ['nullable', 'date'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'status' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
