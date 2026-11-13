<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Course;
use App\Models\Events;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ResourceAuthorizer
{
    public function authorizeCourse(?User $user, ?Course $course): Course
    {
        return $this->authorizeOwned($user, $course, 'user_id');
    }

    public function authorizeCourseId(?User $user, mixed $courseId): Course
    {
        if (! is_numeric($courseId)) {
            abort(404);
        }

        return $this->authorizeCourse($user, Course::query()->find($courseId));
    }

    public function authorizeParentCourse(?User $user, mixed $courseId): void
    {
        if ($courseId === null || $courseId === '') {
            return;
        }

        $this->authorizeCourseId($user, $courseId);
    }

    public function authorizeClass(?User $user, ?Classe $class): Classe
    {
        $class = $this->authorizeOwned($user, $class, 'user_id');
        $this->authorizeParentCourse($user, $class->course_id);

        return $class;
    }

    public function authorizeEvent(?User $user, ?Events $event): Events
    {
        return $this->authorizeOwned($user, $event, 'user_id');
    }

    public function authorizeLecture(?User $user, ?Lecture $lecture): Lecture
    {
        if ($lecture === null) {
            abort(404);
        }

        $this->authorizeCourse($user, $lecture->course);

        return $lecture;
    }

    public function authorizeQuiz(?User $user, ?Quiz $quiz): Quiz
    {
        if ($quiz === null) {
            abort(404);
        }

        $this->authorizeLecture($user, $quiz->lectures);

        return $quiz;
    }

    /**
     * course_tutors records an assignment. Assignment does not grant management.
     * Only the course creator and an admin may manage the course and its teaching resources.
     */
    public function isAssignedTutor(?User $user, ?Course $course): bool
    {
        if ($user === null || $course === null) {
            return false;
        }

        return DB::table('course_tutors')
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function authorizeOwned(?User $user, ?Model $resource, string $ownerKey): Model
    {
        if ($resource === null) {
            abort(404);
        }

        $ownerId = $resource->getAttribute($ownerKey);

        if (! $this->canManage($user, $ownerId)) {
            abort(403);
        }

        return $resource;
    }

    private function canManage(?User $user, mixed $ownerId): bool
    {
        if ($user === null || ! is_numeric($ownerId)) {
            return false;
        }

        if ($user->hasRoleName(Role::ADMIN)) {
            return true;
        }

        return $user->hasRoleName(Role::TUTOR) && (int) $ownerId === (int) $user->id;
    }
}
