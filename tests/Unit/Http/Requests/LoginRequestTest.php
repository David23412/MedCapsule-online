<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_remember_me_checkbox_value_is_normalized_for_validation(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => ' Student@Example.COM ',
            'password' => 'secret',
            'remember' => 'on',
        ]);

        $this->prepareForValidation($request);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertSame('student@example.com', $request->input('email'));
        $this->assertTrue($request->input('remember'));
    }

    public function test_login_validation_does_not_reject_existing_short_passwords_before_authentication(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'student@example.com',
            'password' => 'secret',
        ]);

        $this->prepareForValidation($request);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    private function prepareForValidation(LoginRequest $request): void
    {
        $method = new ReflectionMethod(LoginRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);
    }
}
