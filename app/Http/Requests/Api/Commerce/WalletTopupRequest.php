<?php

namespace App\Http\Requests\Api\Commerce;

use App\Http\Requests\Api\ApiFormRequest;

class WalletTopupRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:10', 'max:50000'],
        ];
    }
}
