<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\RequestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([RequestObserver::class])]
class Request extends Model
{
    use HasFactory;
    
    protected $fillable = ['folio', 'type', 'solicitante', 'user_id', 'status', 'return_date'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(RequestItem::class, 'request_id');
    }
}
