<?php

namespace App\Http\Requests\Api\Commerce;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class CategoryIndexRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'parent_id' => ['prohibited'],
            'parent_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'root_only' => ['nullable', 'boolean'],
            'include_children' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'name', 'sort_order'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function filters(): array
    {
        $filters = [
            'search' => $this->validated('search'),
            'sort' => $this->validated('sort', 'sort_order'),
            // The customer app's current build never sends per_page or
            // pages past the first one, so the default must already cover
            // the full category list.
            'per_page' => (int) $this->validated('per_page', 500),
            'status' => $this->has('status') ? $this->boolean('status') : true,
        ];

        if ($this->filled('parent_slug')) {
            $filters['parent_slug'] = $this->validated('parent_slug');
        } else {
            $filters['root_only'] = $this->has('root_only')
                ? $this->boolean('root_only')
                : ! $this->filled('search');
        }

        $filters['include_children'] = $this->has('include_children')
            ? $this->boolean('include_children')
            : (($filters['root_only'] ?? false) && ! isset($filters['parent_slug']));

        return $filters;
    }
}
