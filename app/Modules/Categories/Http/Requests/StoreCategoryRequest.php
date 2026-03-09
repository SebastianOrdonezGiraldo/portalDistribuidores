<?php

namespace App\Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'unique:categories,slug'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'synonyms' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function payload(): array
    {
        return [
            'parent_id' => $this->integer('parent_id') ?: null,
            'name' => $this->string('name')->toString(),
            'slug' => $this->filled('slug') ? $this->string('slug')->toString() : Str::slug($this->string('name')->toString()),
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => $this->integer('sort_order', 0),
        ];
    }
}

