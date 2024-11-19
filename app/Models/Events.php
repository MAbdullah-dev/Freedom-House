<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Events extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [
    'user_id', 'title', 'description', 'capacity', 'event_type',
    'teams_link', 'onsite_address', 'payment_status', 'price', 'start_date', 'end_date',
    'max_students'
];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function recordedLectures()
    {
        return $this->hasMany(Lecture::class);
    }

}
