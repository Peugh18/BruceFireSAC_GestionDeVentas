<?php

namespace App\Http\Requests;

class UpdateCatalogItemRequest extends StoreCatalogItemRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('catalogItem'));
    }
}
