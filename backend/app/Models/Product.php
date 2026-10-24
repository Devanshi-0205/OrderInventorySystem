<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'stock_quantity',
    ];

    protected $appends = ['stock_status'];
    
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Stock status label for API responses. */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity === 0) {
            return 'out_of_stock';
        }
        if ($this->stock_quantity <= 10) {
            return 'low_stock';
        }
        return 'in_stock';
    }
}
