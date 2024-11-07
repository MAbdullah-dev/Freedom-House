<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'file_path',
    ];

    /**
     * Get the user who created the assignment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The classes that this assignment belongs to.
     */
    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'assignment_class')->withTimestamps();
    }

    /**
     * Get the submissions for the assignment.
     */
    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
