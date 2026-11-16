<?php

namespace Tests\Feature;

use App\Livewire\RecordedLecture;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lecture;
use App\Models\Role;
use App\Models\User;
use App\Models\VideoActivity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;
use Vimeo\Laravel\Facades\Vimeo;

class CertificateTemplateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_future_certificates_use_the_stellix_template_and_real_fields_only(): void
    {
        Storage::fake('public');
        $learner = $this->makeUser(Role::LEARNER);
        $course = $this->makeCourse($learner);

        $component = new RecordedLecture;
        $path = (new ReflectionMethod(RecordedLecture::class, 'generateCertificate'))
            ->invoke($component, $learner, $course->id);

        $this->assertStringContainsString('certificates/', $path);
        $stored = Storage::disk('public')->get(str_replace('/storage/', '', $path));
        $this->assertStringStartsWith('%PDF', $stored);

        $html = view('certificates.stellix', ['user' => $learner, 'course' => $course])->render();
        $this->assertStringContainsString('Certificate of completion', $html);
        $this->assertStringContainsString($learner->first_name, $html);
        $this->assertStringContainsString($learner->last_name, $html);
        $this->assertStringContainsString($course->title, $html);
        $this->assertStringContainsString('Provisional wordmark', $html);
        $this->assertStringNotContainsString('Freedom House', $html);
        $this->assertStringNotContainsString('Credit Hours', $html);
        $this->assertStringNotContainsString('Certificate ID', $html);
        $this->assertStringNotContainsString('logo-img.webp', $html);
        $this->assertStringContainsString('logo-img.webp', file_get_contents(resource_path('views/certificates/dummy.blade.php')));
    }

    public function test_an_unenrolled_learner_cannot_generate_a_certificate(): void
    {
        Vimeo::shouldReceive('request')->never();
        $learner = $this->makeUser(Role::LEARNER);
        $owner = $this->makeUser(Role::TUTOR);
        $course = $this->makeCourse($owner);
        $lecture = Lecture::query()->create([
            'course_id' => $course->id,
            'title' => 'Closed lecture',
            'description' => 'Hidden',
            'video_file' => '/videos/closed',
            'order' => 1,
        ]);

        Livewire::actingAs($learner)
            ->test(RecordedLecture::class, [
                'courseId' => $course->id,
                'lectureId' => $lecture->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, Certificate::query()->count());
    }

    public function test_a_completed_enrollment_stores_a_certificate_without_replacing_the_old_template(): void
    {
        Storage::fake('public');
        Mail::fake();
        Vimeo::shouldReceive('request')->andReturn([
            'body' => ['uri' => '/videos/12345', 'name' => 'Fixture video'],
        ]);

        $learner = $this->makeUser(Role::LEARNER);
        $course = $this->makeCourse($learner);
        $lecture = Lecture::query()->create([
            'course_id' => $course->id,
            'title' => 'Finished lecture',
            'description' => 'Visible',
            'video_file' => '/videos/finished',
            'order' => 1,
        ]);
        Booking::query()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'payment_status' => 'free',
            'order_number' => 'cert-'.bin2hex(random_bytes(4)),
        ]);
        VideoActivity::query()->create([
            'user_id' => $learner->id,
            'lecture_id' => $lecture->id,
            'watched' => 1,
        ]);

        Livewire::actingAs($learner)
            ->test(RecordedLecture::class, [
                'courseId' => $course->id,
                'lectureId' => $lecture->id,
            ])
            ->call('sendCertificate');

        $certificate = Certificate::query()->where('user_id', $learner->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($certificate);
        $this->assertStringContainsString('certificates/', $certificate->certificate_path);
        $this->assertFileExists(resource_path('views/certificates/dummy.blade.php'));
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['role_name' => $roleName]);

        return User::query()->create([
            'email' => strtolower($roleName).'-cert-'.uniqid().'@example.test',
            'first_name' => 'Certificate',
            'last_name' => $roleName,
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }

    private function makeCourse(User $owner): Course
    {
        return Course::query()->create([
            'user_id' => $owner->id,
            'course_type' => 'recorded',
            'is_paid' => 'free',
            'title' => 'Certificate course '.uniqid(),
            'description' => 'Stored description',
            'thumbnail' => '',
            'video_path' => 'video.mp4',
            'learning_outcomes' => 'Outcomes',
            'target_audience' => 'Audience',
            'requirements' => 'Requirements',
            'is_published' => true,
            'is_drafted' => false,
            'price' => 0,
        ]);
    }
}
