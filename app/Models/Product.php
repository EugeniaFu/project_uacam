<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
    protected $fillable = ['name','description','category_id','quantity','min_quantity','codigo','tipo'];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($product) {
            if (empty($product->codigo)) {
                $product->codigo = self::generateNextCodigo();
            }
        });
    }
    
    public static function generateNextCodigo()
    {
        $lastProduct = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastProduct ? ($lastProduct->id + 1) : 1;
        return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    public function category(){ return $this->belongsTo(Category::class); }
}
