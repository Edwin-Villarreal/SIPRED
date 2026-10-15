<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeartbeatRequest extends FormRequest
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
            'device_id' => ['nullable', 'string', 'max:120'],
            'firmware_version' => ['nullable', 'string', 'max:64'],
            'signal_strength' => ['nullable', 'integer', 'between:-120,0'],
            'battery_level' => ['nullable', 'numeric', 'between:0,100'],
            'status' => ['required', 'in:online,offline,warning,unknown'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}