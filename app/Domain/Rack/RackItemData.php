<?php

namespace App\Domain\Rack;

use Illuminate\Http\UploadedFile;

/** What the owner enters when adding a garment to the rack. */
final readonly class RackItemData
{
    public function __construct(
        public UploadedFile|string $image,
        public string $nameEn,
        public ?string $nameAr = null,
        public ?int $productId = null,
        public ?int $productColorId = null,
        public ?string $altEn = null,
        public ?string $altAr = null,
        public bool $isVisible = true,
        public float $sizeAdjust = 1.0,
    ) {}

    public static function fromForm(array $data): self
    {
        return new self(
            image: $data['image_path'],
            nameEn: $data['name_en'],
            nameAr: $data['name_ar'] ?? null,
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            productColorId: isset($data['product_color_id']) ? (int) $data['product_color_id'] : null,
            altEn: $data['alt_en'] ?? null,
            altAr: $data['alt_ar'] ?? null,
            isVisible: (bool) ($data['is_visible'] ?? true),
            sizeAdjust: (float) ($data['size_adjust'] ?? 1.0),
        );
    }
}
