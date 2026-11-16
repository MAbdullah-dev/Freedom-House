<section class="quiz-view py-4">
    <div class="container">
        <header class="quiz-view__intro">
            <p class="public-kicker">Quiz</p>
            <h1>{{ $quiz->title }}</h1>
            <p>{{ $quiz->description !== '' ? $quiz->description : 'Answer each question, then submit. Your score is calculated when you submit.' }}</p>
        </header>

        <form wire:submit.prevent="submit" class="quiz-view__form">
            @foreach ($quiz->questions as $index => $question)
                <fieldset class="quiz-view__question">
                    <legend>{{ $question->question_text }}</legend>
                    <p class="quiz-view__points">{{ $question->points }} {{ \Illuminate\Support\Str::plural('point', $question->points) }}</p>

                    @foreach ($question->answers as $answer)
                        <div class="form-check">
                            <input type="radio" id="answer-{{ $answer->id }}" class="form-check-input"
                                wire:model="answers.{{ $index }}" value="{{ $answer->id }}">
                            <label for="answer-{{ $answer->id }}" class="form-check-label">
                                {{ $answer->answer_text }}
                            </label>
                        </div>
                    @endforeach
                </fieldset>
            @endforeach

            <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">Submit quiz</span>
                <span wire:loading wire:target="submit">Submitting…</span>
            </button>
        </form>

        @if (session()->has('success'))
            <div class="alert alert-success mt-3" role="status">
                {{ session('success') }}
            </div>
        @endif
    </div>
</section>
