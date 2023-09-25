<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, HasUUID,SoftDeletes;

    protected $guarded = [];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeManager($query)
    {
        return $query->where('author_id', auth()->user()->id);
    }
    
    public function getInitialsAttribute()
    {
        $name = explode(" ",$this->name);
        if(count($name) >= 2){
            return strtoupper(Str::substr($name[0], 0, 1)) . "" . strtoupper(Str::substr($name[1], 0, 1));
        }else{
            return strtoupper(Str::substr($name[0], 0, 1)) ;
        }
    }

    public function departments()
    {
       return $this->hasMany(Department::class);
    }
    public function services()
    {
       return $this->hasMany(Service::class);
    }
    public function employees()
    {
       return $this->hasMany(User::class);
    }
    public static function search($query)
    {
        return empty($query) ? static::query() :
            static::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%');
            });
    }
}
