<!-- Modal -->
<div class="modal fade" wire:ignore.self id="create-class" tabindex="-1" aria-labelledby="createClassEventLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createClassEventLabel">
                    {{ $type === 'class' ? 'Create Class' : 'Create Event' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="submit" class="form">
                    <!-- Select Type (Class or Event) -->
                    <div class="mb-3">
                        <label for="type" class="form-label">Select Type</label>
                        <select wire:model="type" id="type" class="form-select" wire:change="dispatchTypeChanged">
                            <option value="class">Class</option>
                            <option value="event">Event</option>
                        </select>
                        @error('type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Course Name (Only shown if type is 'class') -->
                    @if ($type === 'class')
                        <div class="mb-3">
                            <label for="course" class="form-label">Course Name</label>
                            <select id="course" wire:model="course_id" class="form-select">
                                <option value="">Select Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="title"
                            class="form-label">{{ $type === 'class' ? 'Class Title' : 'Event Title' }}</label>
                        <input wire:model="title" id="title" class="form-control" type="text"
                            placeholder="Enter Title">
                        @error('title')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description"
                            class="form-label">{{ $type === 'class' ? 'Class Description' : 'Event Description' }}</label>
                        <textarea wire:model="description" id="description" class="form-control" placeholder="Enter Description"></textarea>
                        @error('description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Event/Class Type (Onsite, Virtual, Recorded) -->
                    <div class="mb-3">
                        <label for="classType"
                            class="form-label">{{ $type === 'class' ? 'Class Type' : 'Event Type' }}</label>
                        <select wire:model="class_type" id="classType" class="form-select"
                            wire:change="dispatchClassTypeChanged">
                            <option value="onsite">Onsite</option>
                            <option value="virtual">Virtual</option>
                            <option value="recorded">Recorded</option>
                        </select>
                        @error('class_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Teams Link (Visible only if type is Virtual) -->
                    @if ($class_type === 'virtual')
                        <div class="mb-3">
                            <label for="teamsLink" class="form-label">Teams Link</label>
                            <input wire:model="teams_link" id="teamsLink" class="form-control" type="url"
                                placeholder="Enter Teams Link">
                            @error('teams_link')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <!-- Onsite Address (Visible only if type is Onsite) -->
                    @if ($class_type === 'onsite')
                        <div class="mb-3">
                            <label for="onsiteAddress" class="form-label">Onsite Address</label>
                            <input wire:model="onsite_address" id="onsiteAddress" class="form-control" type="url"
                                placeholder="Enter Onsite Address">
                            @error('onsite_address')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <!-- Recorded Video URL (Visible only if type is Recorded) -->
                    @if ($class_type === 'recorded')
                        <div class="mb-3">
                            <label for="recordedVideoUrl" class="form-label">Recorded Video URL</label>
                            <input wire:model="recorded_video_url" id="recordedVideoUrl" class="form-control"
                                type="url" placeholder="Enter Recorded Video URL">
                            @error('recorded_video_url')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <!-- Paid/Free -->
                    <div class="mb-3">
                        <label for="isPaid"
                            class="form-label">{{ $type === 'class' ? 'Class Payment Status' : 'Event Payment Status' }}</label>
                        <select wire:model="is_paid" id="isPaid" class="form-select"
                            wire:change="dispatchIsPaidChanged">
                            <option value="free">Free</option>
                            <option value="paid">Paid</option>
                        </select>
                        @error('is_paid')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Price (Visible only if Paid) -->
                    @if ($is_paid === 'paid')
                        <div class="mb-3">
                            <label for="price"
                                class="form-label">{{ $type === 'class' ? 'Class Price' : 'Event Price' }}</label>
                            <input wire:model="price" id="price" class="form-control" type="number"
                                placeholder="Enter Price">
                            @error('price')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <!-- Capacity -->
                    <div class="mb-3">
                        <label for="capacity"
                            class="form-label">{{ $type === 'class' ? 'Class Capacity' : 'Event Capacity' }}</label>
                        <input wire:model="capacity" id="capacity" class="form-control" type="number"
                            placeholder="Enter Capacity">
                        @error('capacity')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Start Date -->
                    <div class="mb-3">
                        <label for="startDate"
                            class="form-label">{{ $type === 'class' ? 'Class Start Date' : 'Event Start Date' }}</label>
                        <input wire:model="start_date" id="startDate" class="form-control" type="datetime-local">
                        @error('start_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- End Date -->
                    <div class="mb-3">
                        <label for="endDate"
                            class="form-label">{{ $type === 'class' ? 'Class End Date' : 'Event End Date' }}</label>
                        <input wire:model="end_date" id="endDate" class="form-control" type="datetime-local">
                        @error('end_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit"
                        class="btn btn-primary mt-3">{{ $type === 'class' ? 'Create Class' : 'Create Event' }}</button>
                </form>
            </div>
        </div>
    </div>
</div>


@push('js')
    <script>
        window.addEventListener('closeModal', event => {
            $('#create-class').modal('hide');
        });
    </script>
@endpush
