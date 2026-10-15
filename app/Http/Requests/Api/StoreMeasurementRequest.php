<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'sensor_code' => ['required', 'string', 'max:64'],
            'measurement_id' => ['required', 'string', 'max:191'],
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string', 'max:32'],
            'measured_at' => ['required', 'date'],
            'quality' => ['nullable', 'in:good,warning,invalid,unknown'],
            'metadata' => ['nullable', 'array'],
            'origin' => ['prohibited'],
        ];
    }
}