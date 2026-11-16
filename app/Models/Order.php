<?php

namespace App\Models;

use App\Domain\Orders\OrderStatus;
use App\Models\Concerns\LogsActivity;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A placed order. Only checkout creates orders; admins change the status and add notes (SRS 3.3, 3.4). */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number', 'link_token', 'customer_name', 'phone', 'email', 'delivery_city_id', 'city_name',
        'area', 'address', 'note', 'status', 'subtotal', 'delivery_fee', 'total', 'payment_method',
        'language', 'placed_at', 'country',
    ];

    protected $attributes = [
        'status' => 'new',
        'payment_method' => 'cash_on_delivery',
        'language' => 'en',
        'email' => null,
        'note' => null,
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'placed_at' => 'datetime',
    ];

    /** The confirmation link token never appears in the activity log or in arrays. */
    protected $hidden = ['link_token'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->latest()->latest('id');
    }

    public function deliveryCity(): BelongsTo
    {
        return $this->belongsTo(DeliveryCity::class);
    }

    /** "street, area, city", as the confirmation page shows it (D.6.7). */
    public function deliveryAddress(): string
    {
        return implode($this->comma(), array_filter([$this->address, $this->area, $this->city_name]));
    }

    /** The comma between parts of the address, in the order's language. */
    public function comma(): string
    {
        return $this->language === 'ar' ? '، ' : ', ';
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function activityLogHidden(): array
    {
        return ['link_token'];
    }
}
