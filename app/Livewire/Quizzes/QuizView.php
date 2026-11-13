<?php

namespace App\Livewire\Quizzes;

use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Services\ContentAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QuizView extends Component
{
    public $quiz;
    public $answers = [];

    public function mount($quizId)
    {
        $this->quiz = $this->guardQuiz($quizId);
    }

    public function submit()
    {
        $this->quiz = $this->guardQuiz($this->quizId());
        $score = 0;

        foreach ($this->quiz->questions as $index => $question) {
            $correctAnswer = $question->answers->where('is_correct', true)->first();
            if (isset($this->answers[$index]) && $this->answers[$index] == $correctAnswer->id) {
                $score += $question->points;
            }
        }

        QuizSubmission::create([
            'quiz_id' => $this->quiz->id,
            'user_id' => auth()->id(), // Assuming the user is logged in
            'score' => $score,
        ]);

        session()->flash('success', "You scored $score points!");
    }

    public function render()
    {
        $this->quiz = $this->guardQuiz($this->quizId());

        return view('livewire.quizzes.quiz-view');
    }

    private function guardQuiz(mixed $quizId): Quiz
    {
        $quiz = app(ContentAccess::class)->authorizeQuiz(Auth::user(), $quizId);
        $quiz->load('questions.answers');
        $quiz->questions->each(function ($question) {
            $question->answers->each->makeHidden('is_correct');
        });

        return $quiz;
    }

    private function quizId(): mixed
    {
        if (is_object($this->quiz)) {
            return $this->quiz->id ?? null;
        }

        if (is_array($this->quiz)) {
            return $this->quiz['id'] ?? null;
        }

        return $this->quiz;
    }
}
