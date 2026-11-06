<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TryonRun extends Model {
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime'];
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
}
