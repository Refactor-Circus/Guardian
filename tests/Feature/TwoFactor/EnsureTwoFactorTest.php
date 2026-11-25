<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Middleware\EnsureTwoFactor;

// Criterion 33: the guardian.two-factor middleware.

beforeEach(function (): void {
    $this->travelTo(now()->startOfMinute()->addSeconds(10));
    withSessions();
    Route::middleware(['auth', 'guardian.two-factor'])->get('test-stateless', fn (): string => 'stateless');
    Route::middleware('guardian.two-factor')->get('test-public', fn (): string => 'public');
});

/**
 * Bind a requirement for everyone.
 *
 * @param  list<string>  $methods
 */
function requireTwoFactor(?CarbonImmutable $deadline = null, array $methods = Requirement::ALL_METHODS): void
{
    app()->instance(TwoFactorRequirement::class, new class($deadline, $methods) implements TwoFactorRequirement
    {
        /**
         * @param  list<string>  $methods
         */
        public function __construct(private readonly ?CarbonImmutable $deadline, private readonly array $methods) {}

        public function for(Authenticatable $user): Requirement
        {
            return new Requirement(true, $this->methods, $this->deadline);
        }
    });
}

it('registers the guardian.two-factor alias', function (): void {
    expect(app('router')->getMiddleware()['guardian.two-factor'] ?? null)->toBe(EnsureTwoFactor::class);
});

it('lets guests and users nobody requires it of through', function (): void {
    $this->get('test-public')->assertOk();

    signIn(user());
    $this->get('test-protected')->assertOk();
});

it('refuses an unenrolled user once the requirement is due', function (): void {
    config()->set('guardian.two_factor.required', true);
    signIn(user());

    $this->getJson('test-protected')->assertForbidden()->assertJsonPath('code', 'two_factor_enrollment_required');
});

it('lets an unenrolled user through before the deadline, with the deadline in a header', function (): void {
    $deadline = CarbonImmutable::now()->addDays(3);
    requireTwoFactor($deadline);
    signIn(user());

    $this->get('test-protected')->assertOk()->assertHeader(EnsureTwoFactor::DEADLINE_HEADER, $deadline->toIso8601String());

    $this->travelTo($deadline->addSecond());
    $this->getJson('test-protected')->assertForbidden()->assertJsonPath('code', 'two_factor_enrollment_required');
});

it('counts only the allowed methods towards enrolment', function (): void {
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    enrolOneTimeCode($ada, $channel);
    requireTwoFactor(methods: ['totp', 'passkey']);
    signIn($ada);
    $this->postJson(route('guardian.two-factor.challenge.send'), ['method' => 'email'])->assertNoContent();
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'email', 'code' => $channel->lastCode()])->assertNoContent();

    $this->getJson('test-protected')->assertForbidden()->assertJsonPath('code', 'two_factor_enrollment_required');
});

it('refuses a pending session with the methods that answer the challenge, and passes once confirmed', function (): void {
    $ada = user();
    [, $secret] = enrolTotp($ada);
    signIn($ada);

    $this->getJson('test-protected')
        ->assertForbidden()
        ->assertJsonPath('code', 'two_factor_challenge_required')
        ->assertJsonPath('methods', ['totp', 'recovery']);

    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp', 'code' => totpCode($secret)])->assertNoContent();

    $this->get('test-protected')->assertOk();
});

it('lets a stateless request from an enrolled user through (D-012), but not an unenrolled one past the deadline', function (): void {
    $ada = user();
    enrolTotp($ada);

    $this->actingAs($ada)->get('test-stateless')->assertOk();

    config()->set('guardian.two_factor.required', true);
    $this->actingAs(user())->getJson('test-stateless')->assertForbidden()->assertJsonPath('code', 'two_factor_enrollment_required');
});

it('never blocks Guardian\'s own two-factor routes', function (): void {
    foreach (Route::getRoutes() as $route) {
        if (str_starts_with((string) $route->getName(), 'guardian.two-factor.')) {
            $route->middleware('guardian.two-factor');
        }
    }

    // Required and not enrolled: enrolment and status stay open.
    config()->set('guardian.two_factor.required', true);
    signIn(user());
    $this->getJson(route('guardian.two-factor.show'))->assertOk();
    $secret = $this->postJson(route('guardian.two-factor.totp.store'))->assertCreated()->json('data.secret');
    $this->postJson(route('guardian.two-factor.totp.confirm'), ['code' => totpCode($secret)])->assertOk();

    // Pending: the challenge stays open.
    $ada = user();
    [, $adaSecret] = enrolTotp($ada);
    signIn($ada);
    $this->getJson(route('guardian.two-factor.show'))->assertOk();
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp', 'code' => totpCode($adaSecret)])->assertNoContent();
});
