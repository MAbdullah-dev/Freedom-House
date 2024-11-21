<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

    class Classe extends Model
    {
        use HasFactory, SoftDeletes;

        protected $fillable = [
        'course_id',
        'user_id',
        'title',
        'description',
        'capacity',
        'visibility',
        'class_type',
        'teams_link',
        'onsite_address',
        'is_paid',
        'price',
        'start_date',
        'end_date'
    ];

        public function course()
        {
            return $this->belongsTo(Course::class);
        }
        public function bookings()
        {
            return $this->hasMany(Booking::class);
        }

        public function recordedLectures()
        {
            return $this->hasMany(Lecture::class);
        }

        public function quizzes()
        {
            return $this->hasMany(Quiz::class);
        }

        public function assignments()
        {
            return $this->hasMany(Assignment::class);
        }
    }
