<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'first_name',
        'last_name',
        'password',
        'pfp',
        'cover_photo',
        'bio',
        'phone',
        'microsoft_id',
        'microsoft_account',
        'verification_token',
        'email_verified_at',
        'password_reset_token',
        'password_token_created_at',
        'role_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'roles_users')->select('roles.id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function classes()
    {
        return $this->hasManyThrough(Classe::class, Course::class);
    }

    public function events()
    {
        return $this->hasMany(Events::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function tutor()
    {
        return $this->hasOne(Tutor::class);
    }

    public function tutorFiles()
    {
        return $this->hasMany(TutorFile::class);
    }

    public function courseRatings()
    {
        return $this->hasMany(CourseRating::class);
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

    public function wishlist()
    {
        return $this->belongsToMany(Course::class, 'wishlists', 'user_id', 'course_id');
    }
    public function videoActivities()
    {
        return $this->hasMany(VideoActivity::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function courseProgress()
    {
        return $this->hasMany(CourseProgress::class);
    }
}
