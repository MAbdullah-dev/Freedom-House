<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id', 'user_id', 'file_path', 'grade', 'is_graded',
    ];

    /**
     * Get the assignment that this submission belongs to.
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the user who submitted the assignment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
