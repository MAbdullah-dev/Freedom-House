<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'first_name',
        'last_name',
        'pfp',
        'cover_photo',
        'bio',
        'phone',
        'is_verified',
        'microsoft_id'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function classes()
    {
        return $this->hasManyThrough(Classe::class, Course::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function tutorFiles()
    {
        return $this->hasMany(TutorFile::class);
    }

    public function courseRatings()
    {
        return $this->hasMany(Review::class);
    }

    public function assignmentSubmissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function helpRequests()
    {
        return $this->hasMany(Request::class);
    }

    public function quizSubmissions()
    {
        return $this->hasMany(QuizSubmission::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
