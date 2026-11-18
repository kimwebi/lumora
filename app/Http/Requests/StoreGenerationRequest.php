<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $references = collect($this->input('reference_images', []))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        $this->merge([
            'prompt' => is_string($this->input('prompt')) ? trim($this->input('prompt')) : $this->input('prompt'),
            'reference_images' => $references,
        ]);
    }

    public function rules(): array
    {
        $sizes = collect(config('lumora.generation.size_options', []))->pluck('value')->all();
        $qualities = collect(config('lumora.generation.quality_options', []))->pluck('value')->all();

        return [
            'prompt' => ['required', 'string', 'min:3', 'max:1200'],
            'size' => ['required', 'string', Rule::in($sizes)],
            'quality' => ['required', 'string', Rule::in($qualities)],
            'reference_images' => ['nullable', 'array', 'max:2'],
            'reference_images.*' => ['nullable', 'string', 'url:http,https', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Describe the image you want to create.',
            'prompt.min' => 'Give your prompt a little more detail (at least 3 characters).',
            'prompt.max' => 'Keep prompts under 1,200 characters for the best results.',
            'size.in' => 'Choose one of the supported image sizes.',
            'quality.in' => 'Choose one of the supported quality levels.',
            'reference_images.max' => 'You can attach up to two reference images.',
            'reference_images.*.url' => 'Reference images must be valid http(s) URLs.',
        ];
    }

    public function attributes(): array
    {
        return [
            'prompt' => 'prompt',
            'size' => 'image size',
            'quality' => 'quality',
        ];
    }
}
