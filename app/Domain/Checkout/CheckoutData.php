<?php

namespace App\Domain\Checkout;

use App\Models\DeliveryCity;

/** What the customer sent at checkout, already checked field by field (REQ-CHK-07 to REQ-CHK-09). */
final class CheckoutData
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $phone,
        public readonly ?string $email,
        public readonly DeliveryCity $city,
        public readonly string $area,
        public readonly string $address,
        public readonly ?string $note,
        public readonly Cart $cart,
        public readonly string $language,
        // For a Gulf country the customer types the city (REQ-CHK-28); null for Jordan, where the
        // delivery row is the city.
        public readonly ?string $typedCity = null,
    ) {}

    /** The city as the order keeps it: "Irbid", or "Riyadh, Saudi Arabia" for the Gulf. */
    public function cityName(): string
    {
        $row = $this->city->t('name', $this->language);

        return $this->typedCity === null ? $row : $this->typedCity.($this->language === 'ar' ? '، ' : ', ').$row;
    }
}
