<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\MovementObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([MovementObserver::class])]
class Movement extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'action', 'details'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
