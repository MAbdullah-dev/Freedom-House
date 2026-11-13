<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Classe;
use App\Models\Course;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;

class ContentAccess
{
    /**
     * Booking statuses that grant lecture and quiz access.
     * A free course still needs a booking in this status; the course price alone does not.
     */
    public const GRANTED_STATUSES = ['paid', 'free'];

    public function authorizeRecordedLecture(?User $user, mixed $courseId, mixed $lectureId): Lecture
    {
        if (! is_numeric($lectureId)) {
            abort(404);
        }

        $lecture = Lecture::query()->find($lectureId);
        $course = $lecture === null ? null : Course::query()->find($lecture->course_id);

        if ($lecture === null || $course === null) {
            abort(404);
        }

        if (is_numeric($courseId) && (int) $courseId !== (int) $course->id) {
            abort(404);
        }

        $this->authorizeCourseContent($user, $course);

        return $lecture;
    }

    public function authorizeQuiz(?User $user, mixed $quizId): Quiz
    {
        if (! is_numeric($quizId)) {
            abort(404);
        }

        $quiz = Quiz::query()->find($quizId);
        $lecture = $quiz === null ? null : Lecture::query()->find($quiz->lecture_id);
        $course = $lecture === null ? null : Course::query()->find($lecture->course_id);

        if ($quiz === null || $lecture === null || $course === null) {
            abort(404);
        }

        $this->authorizeCourseContent($user, $course);

        return $quiz;
    }

    public function authorizeCourseContent(?User $user, ?Course $course): void
    {
        if ($course === null || ! $course->exists) {
            abort(404);
        }

        if ($user === null) {
            abort(403);
        }

        if ($user->hasRoleName(Role::ADMIN)) {
            return;
        }

        if ($user->hasRoleName(Role::TUTOR) && (int) $course->user_id === (int) $user->id) {
            return;
        }

        $classIds = Classe::query()
            ->where('course_id', $course->id)
            ->select('id');

        $enrolled = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('payment_status', self::GRANTED_STATUSES)
            ->where(function ($query) use ($course, $classIds) {
                $query->where('course_id', $course->id)
                    ->orWhereIn('class_id', $classIds);
            })
            ->exists();

        if (! $enrolled) {
            abort(403);
        }
    }
}
