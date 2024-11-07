<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'thumbnail', 'description', 'timeline', 'category_id',
        'is_published', 'context', 'tags', 'requirement_instruction'
    ];

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'course_instructor');
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function ratings()
    {
        return $this->hasMany(CourseRating::class);
    }
}
