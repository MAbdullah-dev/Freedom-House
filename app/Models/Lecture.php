<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    use HasFactory;

    protected $fillable = ['class_id', 'vimeo_url'];

    public function class()
    {
        return $this->belongsTo(Classe::class);
    }
}
