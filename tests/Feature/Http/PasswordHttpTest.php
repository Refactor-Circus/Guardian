<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Password/helpers.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordCheckedActionEvent;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordCheckingActionEvent;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordPolicyShowingActionEvent;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordPolicyShownActionEvent;
use RefactorCircus\Guardian\Domains\Password\Mcp\Tools\ShowPasswordPolicyTool;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criterion 43: the policy and check endpoints, and the MCP tool.

beforeEach(fn () => fakeBreaches(['password1234']));

it('answers the active rules for UIs, to anyone', function (): void {
    config()->set('guardian.passwords.symbols', true);

    $this->getJson(route('guardian.password-policy.show'))->assertOk()->assertExactJson(['data' => [
        'min_length' => 12,
        'max_length' => 128,
        'mixed_case' => false,
        'numbers' => false,
        'symbols' => true,
        'letters' => false,
        'uncompromised' => true,
        'uncompromised_threshold' => 0,
        'history' => 5,
        'max_age_days' => null,
    ]]);
});

it('checks a password: 200 with valid and the errors', function (): void {
    $this->postJson(route('guardian.passwords.check'), ['password' => 'a fine long passphrase'])->assertOk()->assertExactJson(['valid' => true, 'errors' => []]);

    $errors = $this->postJson(route('guardian.passwords.check'), ['password' => 'password1234'])->assertOk()->assertJsonPath('valid', false)->json('errors');
    expect($errors)->not->toBe([]);

    $this->postJson(route('guardian.passwords.check'))->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('includes the history for a signed-in caller', function (): void {
    $ada = user(['password' => 'my old passphrase']);

    $this->postJson(route('guardian.passwords.check'), ['password' => 'my old passphrase'])->assertJsonPath('valid', true);
    $this->actingAs($ada)->postJson(route('guardian.passwords.check'), ['password' => 'my old passphrase'])->assertJsonPath('valid', false)->assertJsonPath('errors.0', __('guardian::guardian.password_recently_used'));
});

it('is rate-limited', function (): void {
    config()->set('guardian.passwords.check_per_minute', 3);

    foreach (range(1, 3) as $attempt) {
        $this->postJson(route('guardian.passwords.check'), ['password' => 'a fine long passphrase'])->assertOk();
    }

    $this->postJson(route('guardian.passwords.check'), ['password' => 'a fine long passphrase'])->assertTooManyRequests()->assertHeader('Retry-After')->assertJson(['code' => 'too_many_attempts']);
});

it('never logs or records the password, nor puts it in an event', function (): void {
    Log::spy();
    Event::fake([PasswordCheckingActionEvent::class, PasswordCheckedActionEvent::class]);

    $this->postJson(route('guardian.passwords.check'), ['password' => 'secret passphrase xyz'])->assertOk();

    Log::shouldNotHaveReceived('info');
    Event::assertDispatched(PasswordCheckingActionEvent::class, fn (PasswordCheckingActionEvent $event): bool => ! str_contains(serialize($event), 'secret passphrase xyz'));
    Event::assertDispatched(PasswordCheckedActionEvent::class, fn (PasswordCheckedActionEvent $event): bool => ! str_contains(serialize($event), 'secret passphrase xyz'));
    expect((string) json_encode(SecurityEventModel::query()->get()->toArray()))->not->toContain('secret passphrase xyz');
});

it('offers the show tool over MCP, answering like HTTP, and no check tool', function (): void {
    Event::fake([PasswordPolicyShowingActionEvent::class, PasswordPolicyShownActionEvent::class]);
    $this->actingAs(user());

    expect(GuardianServer::TOOLS)->toContain(ShowPasswordPolicyTool::class)
        ->and(array_filter(GuardianServer::TOOLS, fn (string $tool): bool => str_contains($tool, 'CheckPassword')))->toBe([]);

    mcpTool(ShowPasswordPolicyTool::class)->assertOk()->assertStructuredContent($this->getJson(route('guardian.password-policy.show'))->json());

    Event::assertDispatched(PasswordPolicyShowingActionEvent::class);
    Event::assertDispatched(PasswordPolicyShownActionEvent::class);
});
