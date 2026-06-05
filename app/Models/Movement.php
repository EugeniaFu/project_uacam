<?php

namespace App\Models;

use App\Observers\MovementObserver;
use App\Models\Product;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy([MovementObserver::class])]
class Movement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'details',

        'product_id',
        'inventory_type',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'alta_observaciones',
        'baja_motivo',
        'baja_observaciones',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
