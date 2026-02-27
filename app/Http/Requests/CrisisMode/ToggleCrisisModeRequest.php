<?php

namespace App\Http\Requests\CrisisMode;

use Illuminate\Foundation\Http\FormRequest;

class ToggleCrisisModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['nullable', 'in:facebook,linkedin,all'],
        ];
    }

    public function normalizedPlatform(): ?string
    {
        $platform = $this->validated('platform');

        return $platform === 'all' ? null : $platform;
    }
}
