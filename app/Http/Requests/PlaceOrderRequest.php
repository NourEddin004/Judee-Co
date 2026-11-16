<?php

namespace App\Http\Requests;

use App\Domain\Catalog\BagProducts;
use App\Domain\Checkout\Cart;
use App\Domain\Checkout\CartLine;
use App\Domain\Checkout\CheckoutData;
use App\Domain\Delivery\DeliveryList;
use App\Models\DeliveryCity;
use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The checkout form, checked on the server before anything is saved (REQ-CHK-07 to 09, REQ-DB-12).
 * Only these fields are read; anything else sent is ignored (REQ-SEC-25, REQ-SEC-35).
 */
class PlaceOrderRequest extends FormRequest
{
    /** The picker's value for "My area isn't listed" (REQ-DMO-65). */
    public const OTHER_AREA = '__other';

    /** The destination picker's value for Jordan; a Gulf country sends its delivery key (REQ-CHK-26). */
    public const JORDAN = 'jordan';

    private const MAX_LINES = 50;

    private ?Cart $cart = null;

    /** A form without a destination is a Jordan order, as before SRS 1.4. */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('destination')) {
            $this->merge(['destination' => self::JORDAN]);
        }
    }

    public function rules(): array
    {
        $countries = array_column(config('store.phone_countries'), 'code');

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', Rule::in($countries)],
            'phone' => ['required', 'string', 'max:32', $this->validPhone(...)],
            'email' => ['nullable', 'string', 'max:191', 'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/'],
            // Jordan, or one of the Gulf countries switched on under Delivery (REQ-CHK-26).
            'destination' => ['required', 'string', Rule::in([self::JORDAN, ...app(DeliveryList::class)->gulfCountries()->pluck('key')->all()])],
            // Jordan: city and area from the lists (REQ-CHK-27).
            'city' => ['exclude_unless:destination,'.self::JORDAN, 'required', 'string', 'max:32', fn (string $attribute, mixed $value, Closure $fail) => $this->city() ? null : $fail(__('store.checkout.invalid_city'))],
            'area' => ['exclude_unless:destination,'.self::JORDAN, 'required', 'string', 'max:120', $this->listedArea(...)],
            'area_other' => ['exclude_unless:destination,'.self::JORDAN, 'nullable', 'required_if:area,'.self::OTHER_AREA, 'string', 'max:120'],
            // A Gulf country: the customer types the city and area, 2 to 80 characters each (REQ-CHK-28).
            'gulf_city' => ['exclude_if:destination,'.self::JORDAN, 'required', 'string', 'min:2', 'max:80'],
            'gulf_area' => ['exclude_if:destination,'.self::JORDAN, 'required', 'string', 'min:2', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*.id' => ['required', 'integer', 'min:1'],
            'lines.*.size' => ['nullable', 'string', 'max:16'],
            'lines.*.qty' => ['required', 'integer', 'min:1', 'max:'.config('store.max_quantity')],
            // Anti-spam: a field people never see, so only bots fill it in (REQ-SEC-06).
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        $required = fn (string $label) => __('store.checkout.required', ['field' => mb_strtolower(__("store.checkout.{$label}"))]);

        return [
            'full_name.*' => $required('name'),
            'phone.required' => $required('phone'),
            'phone.*' => Phone::invalidMessage($this->country()),
            'country.*' => Phone::invalidMessage($this->country()),
            'email.*' => __('store.checkout.invalid_email'),
            'city.*' => __('store.checkout.invalid_city'),
            'area.*' => __('store.checkout.invalid_area'),
            'area_other.*' => __('store.checkout.invalid_area'),
            'destination.*' => __('store.checkout.invalid_destination'),
            'gulf_city.*' => __('store.checkout.invalid_gulf_city'),
            'gulf_area.*' => __('store.checkout.invalid_gulf_area'),
            'address.*' => $required('address'),
            'note.*' => __('store.checkout.note_too_long'),
            'lines.*' => __('store.checkout.bag_changed'),
            'lines.*.*.*' => __('store.checkout.bag_changed'),
            'website.*' => __('store.checkout.failed'),
        ];
    }

    /** Every piece in the bag must still be on the site, in a size it comes in. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isEmpty() && $this->cart() === null) {
                $validator->errors()->add('lines', __('store.checkout.bag_changed'));
            }
        }];
    }

    public function toData(): CheckoutData
    {
        $values = $this->validated();

        return new CheckoutData(
            customerName: $values['full_name'],
            phone: Phone::international($values['phone'], $this->country()),
            email: $values['email'] ?? null,
            city: $this->city(),
            area: $this->isJordan()
                ? ($values['area'] === self::OTHER_AREA ? $values['area_other'] : $values['area'])
                : trim($values['gulf_area']),
            address: $values['address'],
            note: $values['note'] ?? null,
            cart: $this->cart(),
            language: app()->getLocale(),
            typedCity: $this->isJordan() ? null : trim($values['gulf_city']),
        );
    }

    /** The phone country picked at checkout (Appendix D.8.3). */
    private function country(): array
    {
        return Phone::country($this->input('country'));
    }

    /** Exactly as many digits as the country uses, not counting a 0 at the start (REQ-CHK-22, REQ-DMO-56, 57). */
    private function validPhone(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Phone::isValid($value, $this->country())) {
            $fail(Phone::invalidMessage($this->country()));
        }
    }

    /** An area of the chosen city, in either language, or "My area isn't listed". */
    private function listedArea(string $attribute, mixed $value, Closure $fail): void
    {
        $city = $this->city();

        if ($value === self::OTHER_AREA || $city === null) {
            return;
        }

        $names = $city->areas->flatMap(fn ($area) => [$area->name_en, $area->name_ar])->filter()->all();

        if (! in_array($value, $names, true)) {
            $fail(__('store.checkout.invalid_area'));
        }
    }

    private function isJordan(): bool
    {
        return $this->input('destination', self::JORDAN) === self::JORDAN;
    }

    /** The delivery row: the chosen Jordan city, or the chosen Gulf country. */
    private function city(): ?DeliveryCity
    {
        $key = $this->isJordan() ? $this->input('city') : $this->input('destination');
        $row = is_string($key) ? app(DeliveryList::class)->find($key) : null;

        return $row !== null && $row->isJordan() === $this->isJordan() ? $row : null;
    }

    /**
     * The bag joined with the product colours as they are now. Lines for the same colour and size
     * are added together, up to the most of one piece (REQ-DMO-27, REQ-DMO-29). Null when a colour
     * or its product is gone from the site or the size is not one the product comes in.
     */
    public function cart(): ?Cart
    {
        if ($this->cart !== null) {
            return $this->cart;
        }

        $lines = collect($this->input('lines', []))->filter(fn ($line) => is_array($line));
        $colors = app(BagProducts::class)->find($lines->pluck('id')->filter(fn ($id) => is_int($id))->all());
        $merged = [];

        foreach ($lines as $line) {
            $color = $colors->get($line['id'] ?? null);
            $label = isset($line['size']) && is_string($line['size']) && $line['size'] !== '' ? $line['size'] : null;
            $size = $label === null ? null : $color?->product->sizes->firstWhere('label', $label);

            if ($color === null || ($label !== null && $size === null)) {
                return null;
            }

            $key = $color->id.'|'.$label;
            $quantity = ($merged[$key]?->quantity ?? 0) + (int) ($line['qty'] ?? 0);
            $merged[$key] = new CartLine($color, $size, min(config('store.max_quantity'), $quantity));
        }

        return $this->cart = new Cart(array_values($merged));
    }
}
