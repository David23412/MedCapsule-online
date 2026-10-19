<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Example Learner', 'email' => 'learner@example.com',
            'number' => '01012345678', 'university' => 'Cairo University',
            'password' => 'ExamplePassword123!', 'password_confirmation' => 'ExamplePassword123!', 'terms' => '1',
        ], $overrides);
    }

    #[DataProvider('rememberValues')]
    public function test_login_respects_the_remember_me_choice(?string $remember, bool $expected): void
    {
        $user = User::factory()->student()->create(['password' => 'ExamplePassword123!']);
        $payload = ['email' => $user->email, 'password' => 'ExamplePassword123!'];
        if ($remember !== null) $payload['remember'] = $remember;
        $response = $this->post('/login', $payload)->assertRedirect(route('courses.index'));
        $this->assertAuthenticatedAs($user);
        $cookie = Auth::guard()->getRecallerName();
        $expected ? $response->assertCookie($cookie) : $response->assertCookieMissing($cookie);
    }

    public static function rememberValues(): array
    {
        return [[null, false], ['0', false], ['1', true]];
    }

    public function test_failed_login_keeps_an_unchecked_remember_choice_and_never_flashes_password(): void
    {
        $this->from('/login')->post('/login', ['email' => 'missing@example.com', 'password' => 'WrongPassword123!'])
            ->assertRedirect('/login')->assertSessionHasErrors('email')
            ->assertSessionHas('_old_input.remember', false)->assertSessionMissing('_old_input.password');
        $html = $this->get('/login')->assertOk()->getContent();
        preg_match('/<input[^>]*id="remember"[^>]*>/', $html, $match);
        $this->assertStringNotContainsString('checked', $match[0]);
        $this->assertStringContainsString('id="email-error"', $html);
    }

    #[DataProvider('phoneFormats')]
    public function test_registration_accepts_supported_phone_formats_without_losing_digits(string $phone): void
    {
        $this->post('/register', $this->registration(['number' => $phone]))->assertRedirect(route('courses.index'));
        $this->assertDatabaseHas('users', ['email' => 'learner@example.com', 'number' => '01012345678']);
        $this->assertAuthenticated();
    }

    public static function phoneFormats(): array
    {
        return [['+20 10 1234 5678'], ['0020 10 1234 5678'], ['٠١٠١٢٣٤٥٦٧٨'], ['۰۱۰۱۲۳۴۵۶۷۸']];
    }

    public function test_registration_setup_failure_rolls_back_the_account_and_allows_retry(): void
    {
        $this->mock(SessionSecurityService::class)->shouldReceive('handleNewLogin')->once()
            ->andThrow(new \RuntimeException('Synthetic setup failure'));
        $this->from('/register')->post('/register', $this->registration())
            ->assertRedirect('/register')->assertSessionHasErrors('error')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'learner@example.com']);
        $this->get('/register')->assertOk()->assertSee('Registration failed. Please try again.');

        $this->app->instance(SessionSecurityService::class, new SessionSecurityService());
        $this->post('/register', $this->registration())->assertRedirect(route('courses.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_missing_confirmation_and_terms_errors_are_visible_with_safe_old_input(): void
    {
        $payload = $this->registration(['password_confirmation' => '', 'terms' => '']);
        $this->from('/register')->post('/register', $payload)->assertSessionHasErrors(['password_confirmation', 'terms']);
        $this->get('/register')->assertOk()->assertSee('id="password_confirmation-error"', false)
            ->assertSee('id="terms-error"', false)->assertSee('value="learner@example.com"', false)
            ->assertDontSee('value="ExamplePassword123!"', false);
    }

    public function test_normalized_phone_uniqueness_and_malformed_inputs_are_validated_safely(): void
    {
        User::factory()->create(['number' => '01012345678']);
        $this->post('/register', $this->registration(['number' => '٠١٠١٢٣٤٥٦٧٨']))->assertSessionHasErrors('number');
        $this->postJson('/register', ['name' => [], 'email' => [], 'number' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'number']);
        $this->postJson('/login', ['email' => [], 'password' => 'ExamplePassword123!'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_throttle_uses_the_same_email_normalization_as_authentication(): void
    {
        $limiter = RateLimiter::limiter('login');
        $plain = $limiter(Request::create('/login', 'POST', ['email' => 'learner@example.com']));
        $padded = $limiter(Request::create('/login', 'POST', ['email' => ' LEARNER@EXAMPLE.COM ']));
        $this->assertSame($plain[0]->key, $padded[0]->key);
        $this->assertSame($plain[1]->key, $padded[1]->key);
    }

    public function test_login_displays_session_failure_messages(): void
    {
        $this->withSession(['error' => 'Please verify your identity and try again.'])
            ->get('/login')->assertOk()->assertSee('Please verify your identity and try again.');
    }

    public function test_logout_forms_submit_to_the_current_origin(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/courses')
            ->assertOk()->assertSee('action="/logout"', false);
    }
}
