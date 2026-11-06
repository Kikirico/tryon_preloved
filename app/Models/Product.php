<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model {
    use SoftDeletes;
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function getPhotoUrlAttribute() { return str_starts_with($this->photo, 'demo/') ? asset($this->photo) : asset('storage/'.$this->photo); }
}
