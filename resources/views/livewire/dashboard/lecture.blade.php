<div class="lecture_upload px-5 py-5">
    <form wire:submit.prevent="submit">
        <!-- Single Course Selection -->
        <div class="mb-3">
            <label for="course_id" class="form-label">Course</label>
            <select wire:model="course_id" class="form-control" id="course_id" required>
                <option value="">Select a course</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                @endforeach
            </select>
        </div>

        @foreach ($lectures as $index => $lecture)
            <div class="lecture-block mb-5 border rounded p-3">
                <h5>Lecture {{ $index + 1 }}</h5>

                <div class="mb-3">
                    <label for="title_{{ $index }}" class="form-label">Title</label>
                    <input type="text" wire:model.defer="lectures.{{ $index }}.title" class="form-control"
                        id="title_{{ $index }}" placeholder="Lecture Title" required>
                </div>

                <div class="mb-3">
                    <label for="description_{{ $index }}" class="form-label">Description</label>
                    <textarea wire:model.defer="lectures.{{ $index }}.description" class="form-control"
                        id="description_{{ $index }}" placeholder="Lecture Description"></textarea>
                </div>

                <div class="mb-3">
                    <label for="video_file_{{ $index }}" class="form-label">Video File</label>
                    <input type="file" wire:model.defer="lectures.{{ $index }}.video_file"
                        class="form-control" id="video_file_{{ $index }}" required>
                    <div wire:loading wire:target="lectures.{{ $index }}.video_file" class="mt-2 text-info">
                        Uploading...
                    </div>
                </div>

                <div class="mb-3">
                    <label for="order_{{ $index }}" class="form-label">Order</label>
                    <input type="number" wire:model.defer="lectures.{{ $index }}.order" class="form-control"
                        id="order_{{ $index }}" placeholder="Sequence Order">
                </div>

                <button type="button" class="btn btn-outline-danger"
                    wire:click.prevent="removeLectureBlock({{ $index }})">Remove Lecture</button>
            </div>
        @endforeach

        <button type="button" class="button-secondary" wire:click.prevent="addLectureBlock">Add More Lectures</button>
        <button type="submit" class="button-primary">Submit Lectures</button>
    </form>
</div>
