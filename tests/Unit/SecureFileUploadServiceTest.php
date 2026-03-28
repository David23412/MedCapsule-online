<?php

namespace Tests\Unit;

use App\Services\SecureFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureFileUploadServiceTest extends TestCase
{
    public function test_private_uploads_do_not_return_public_urls(): void
    {
        Storage::fake('secure_uploads');

        $service = app(SecureFileUploadService::class);
        $file = UploadedFile::fake()->image('avatar.png');

        $result = $service->processSecureUpload($file, 'images', 'profile-pictures');

        $this->assertNull($result['public_url']);
        Storage::disk('secure_uploads')->assertExists($result['storage_path']);
    }

    public function test_public_disk_uploads_can_return_public_urls(): void
    {
        Storage::fake('public');

        $service = app(SecureFileUploadService::class);
        $file = UploadedFile::fake()->image('course.png');

        $result = $service->processSecureUpload($file, 'images', 'course-images', 'public');

        $this->assertNotNull($result['public_url']);
        Storage::disk('public')->assertExists($result['storage_path']);
    }
}
