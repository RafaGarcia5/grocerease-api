<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'path',
        'url',
        'alt'
    ];

    protected $hidden = [
        'id',
        'filename',
        'path',
        'imageable_type',
        'imageable_id',
        'created_at',
        'updated_at',
    ];

    public function imageable() {
        return $this->morphTo();
    }
}
