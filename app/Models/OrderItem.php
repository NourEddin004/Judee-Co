<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A copy of the product as it was ordered, so deleting the product later changes nothing (REQ-DB-07). */
class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'product_color_id', 'product_name', 'color_name', 'size', 'unit_price', 'quantity'];

    protected $casts = ['unit_price' => 'decimal:2', 'quantity' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** The product it was copied from, while that product is still on the site. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The color it was bought in, for its photo, while that color is still on the site. */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    /** Unit price times quantity, exact (REQ-DB-08). */
    public function lineTotal(): Money
    {
        return Money::of($this->unit_price)->times($this->quantity);
    }
}
