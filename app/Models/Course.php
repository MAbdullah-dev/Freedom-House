<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'thumbnail',
        'video_url',
        'learning_outcomes',
        'target_audience',
        'requirements',
        'is_drafted',
        'is_published',
    ];

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'course_instructors');
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'course_tags');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_courses');
    }

    public function ratings()
    {
        return $this->hasMany(CourseRating::class);
    }
}
