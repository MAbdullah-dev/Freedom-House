<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'video_file',
        'order',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
