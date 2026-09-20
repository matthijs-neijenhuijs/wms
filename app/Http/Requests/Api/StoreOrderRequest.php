<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Warehouse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->attributes->get('warehouse');

        return [
            'custom_order_id' => ['nullable', 'string', 'max:255'],
            'order_status_id' => [
                'required',
                'integer',
                Rule::exists('order_statuses', 'id')->where('warehouse_id', $warehouse->id),
            ],
            'discount' => ['nullable', 'numeric'],
            'comments' => ['nullable', 'string'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'telephone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],

            'client' => ['required', 'array'],
            'client.email' => ['required', 'email', 'max:255'],
            'client.company' => ['nullable', 'string', 'max:255'],
            'client.vat_number' => ['nullable', 'string', 'max:255'],
            'client.coc_number' => ['nullable', 'string', 'max:255'],
            'client.debtor_number' => ['nullable', 'string', 'max:255'],
            'client.iban_number' => ['nullable', 'string', 'max:255'],
            'client.comments' => ['nullable', 'string'],

            'delivery_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_zipcode' => ['required', 'string', 'max:255'],
            'delivery_region' => ['nullable', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:255'],
            'delivery_country' => ['required', 'string', 'max:255'],

            'invoice_name' => ['nullable', 'string', 'max:255'],
            'invoice_address' => ['nullable', 'string', 'max:255'],
            'invoice_zipcode' => ['nullable', 'string', 'max:255'],
            'invoice_region' => ['nullable', 'string', 'max:255'],
            'invoice_city' => ['nullable', 'string', 'max:255'],
            'invoice_country' => ['nullable', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('warehouse_id', $warehouse->id),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
