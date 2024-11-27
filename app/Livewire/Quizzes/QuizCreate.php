<?php

namespace App\Livewire\Quizzes;

use App\Models\Classe;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Livewire\Component;

class QuizCreate extends Component
{
    public $title, $class_id, $timer, $description;
    public $questions = [];

    public function mount()
    {

    }

    public function addQuestion()
    {
        $this->questions[] = [
            'question_text' => '',
            'points' => '',
            'answers' => [
                ['answer_text' => '', 'is_correct' => false],
                ['answer_text' => '', 'is_correct' => false],
            ],
        ];
    }

    public function removeQuestion($index)
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function addAnswer($questionIndex)
    {
        $this->questions[$questionIndex]['answers'][] = ['answer_text' => '', 'is_correct' => false];
    }

    public function removeAnswer($questionIndex, $answerIndex)
    {
        unset($this->questions[$questionIndex]['answers'][$answerIndex]);
        $this->questions[$questionIndex]['answers'] = array_values($this->questions[$questionIndex]['answers']);
    }

    public function submit()
    {
        $this->validate([
            'title' => 'required|string',
            'class_id' => 'required|integer|exists:classes,id',
            'timer' => 'nullable|integer',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.points' => 'required|integer',
            'questions.*.answers' => 'required|array|min:2',
            'questions.*.answers.*.answer_text' => 'required|string',
        ]);

        $quiz = Quiz::create([
            'title' => $this->title,
            'class_id' => $this->class_id,
            'timer' => $this->timer,
            'description' => $this->description,
        ]);

        foreach ($this->questions as $question) {
            $newQuestion = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_text' => $question['question_text'],
                'points' => $question['points'],
            ]);

            foreach ($question['answers'] as $answer) {
                QuizAnswer::create([
                    'quiz_question_id' => $newQuestion->id,
                    'answer_text' => $answer['answer_text'],
                    'is_correct' => $answer['is_correct'],
                ]);
            }
        }

        session()->flash('success', 'Quiz created successfully!');
        return redirect()->route('quizzes.index');
    }

    public function render()
    {
        return view('livewire.quizzes.quiz-create')->layout('components.layouts.createCourse');
    }
}
