<?php

namespace App\Http\Requests\Api\Commerce;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['prohibited'],
            'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'in_stock' => ['nullable', 'boolean'],
            'has_discount' => ['nullable', 'boolean'],
            'include_descendants' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in([
                'latest',
                'oldest',
                'price_asc',
                'price_desc',
                'name_asc',
                'name_desc',
            ])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function filters(): array
    {
        $filters = [
            'search' => $this->validated('search'),
            'sort' => $this->validated('sort', 'latest'),
            // The customer app's current build never sends per_page or
            // pages past the first one, so the default must already cover
            // the full product list.
            'per_page' => (int) $this->validated('per_page', 500),
            'status' => $this->has('status') ? $this->boolean('status') : true,
            'include_descendants' => $this->has('include_descendants')
                ? $this->boolean('include_descendants')
                : true,
        ];

        foreach (['category_slug', 'min_price', 'max_price'] as $field) {
            if ($this->filled($field)) {
                $filters[$field] = $this->validated($field);
            }
        }

        foreach (['is_featured', 'in_stock', 'has_discount'] as $field) {
            if ($this->has($field)) {
                $filters[$field] = $this->boolean($field);
            }
        }

        return $filters;
    }
}
