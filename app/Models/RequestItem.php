<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    use HasFactory;
    
    protected $fillable = ['request_id', 'product_id', 'quantity'];
    
    public function request()
    {
        return $this->belongsTo(Request::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
