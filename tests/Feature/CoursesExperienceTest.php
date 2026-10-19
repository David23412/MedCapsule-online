<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CoursesExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_load_course_covers_but_other_uploads_remain_protected(): void
    {
        $directory = 'course-cover-test-' . bin2hex(random_bytes(6));
        $base = storage_path('app/public/course-images/' . $directory);
        File::ensureDirectoryExists($base);
        File::put($base . '/cover.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII='));

        try {
            $this->get('/uploads/course-images/' . $directory . '/cover.png')
                ->assertOk()->assertHeader('Content-Type', 'image/png')
                ->assertHeader('Cache-Control', 'max-age=31536000, public');
            $this->get('/uploads/course-images/' . $directory . '/missing.png')->assertNotFound();
            $this->get('/uploads/course-images/' . $directory . '/../../../../.env')->assertNotFound();
            $this->get('/uploads/questions/sample.png')->assertRedirect(route('login'));
            $this->get('/uploads/profile-pictures/sample.png')->assertRedirect(route('login'));
        } finally {
            File::deleteDirectory($base);
        }
    }

    public function test_course_covers_have_one_source_and_native_lazy_loading(): void
    {
        Course::factory()->count(4)->create(['image' => 'course-images/sample.png']);
        $response = $this->get('/courses')->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $covers = $xpath->query('//img[@class="course-cover"]');
        $this->assertCount(4, $covers);
        foreach ($covers as $index => $cover) {
            $this->assertSame($index < 2 ? 'eager' : 'lazy', $cover->getAttribute('loading'));
            $this->assertStringContainsString('/uploads/course-images/sample.png', $cover->getAttribute('src'));
            $this->assertFalse($cover->parentNode->hasAttribute('data-bg-image'));
            $this->assertFalse($cover->parentNode->hasAttribute('style'));
        }
    }

    public function test_pending_payments_are_fresh_account_scoped_and_use_the_latest_payment(): void
    {
        $student = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $course = Course::factory()->premium()->create();
        $payment = Payment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'pending']);
        $this->actingAs($student)->get('/courses')->assertOk()->assertSee('pendingCourseIds: [' . $course->id . ']', false);
        $payment->update(['status' => 'rejected']);
        $this->get('/courses')->assertOk()->assertSee('pendingCourseIds: []', false);
        $payment->update(['status' => 'pending']);
        Payment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'completed']);
        $this->get('/courses')->assertOk()->assertSee('pendingCourseIds: []', false);
        $this->actingAs($other)->get('/courses')->assertOk()->assertSee('pendingCourseIds: []', false);
        $this->postJson('/api/check-payment-status', ['course_ids' => [$course->id]])->assertOk()
            ->assertJsonPath('completed_courses', [])->assertJsonPath('pending_courses', []);
    }

    public function test_external_cover_urls_are_optimized_once_without_losing_their_identity(): void
    {
        $unsplash = Course::factory()->create(['image' => 'https://images.unsplash.com/photo-sample?w=100&q=90&ixid=keep#cover']);
        $picsum = Course::factory()->create(['image' => 'https://picsum.photos/id/42/200/300?grayscale']);
        $result = app(CourseService::class)->getCoursesForIndex([])['data']['courses'];
        $url = $result->find($unsplash->id)->optimized_image_url;
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('465', $query['w']);
        $this->assertSame('75', $query['q']);
        $this->assertSame('keep', $query['ixid']);
        $this->assertSame('cover', parse_url($url, PHP_URL_FRAGMENT));
        $this->assertSame('https://picsum.photos/id/42/465/670?grayscale', $result->find($picsum->id)->optimized_image_url);
    }
}
