<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Events extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'capacity',
        'class_type',
        'teams_link',
        'onsite_address',
        'recorded_video_url',
        'is_paid',
        'price',
        'class_date',
        'class_time',
        'booking_start_date',
        'booking_end_date'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function recordedLectures()
    {
        return $this->hasMany(Lecture::class);
    }
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
