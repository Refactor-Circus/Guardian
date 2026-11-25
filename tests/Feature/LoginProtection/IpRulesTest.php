<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\CreateIpRuleAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\DeleteIpRuleAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\CreateIpRuleData;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;

// Criterion 52: IP rules.

beforeEach(fn () => withLoginRoutes());

function ipRule(string $cidr, string $action = 'deny', ?string $expiresAt = null): IpRuleModel
{
    return app(CreateIpRuleAction::class)->execute(CreateIpRuleData::fromValidated(['cidr' => $cidr, 'action' => $action, 'expires_at' => $expiresAt]));
}

it('creates the table with its columns, under its configured name', function (): void {
    expect(Schema::getColumnListing('guardian_ip_rules'))->toEqualCanonicalizing(['id', 'cidr', 'action', 'description', 'expires_at', 'created_by', 'created_at', 'updated_at']);

    config()->set('guardian.tables.ip_rules', 'acme_ip_rules');
    (require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000009_create_guardian_ip_rule_tables.php')->up();

    expect(Schema::hasTable('acme_ip_rules'))->toBeTrue()
        ->and((new IpRuleModel)->getTable())->toBe('acme_ip_rules');
});

it('refuses to roll back a table holding rows, unless forced', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000009_create_guardian_ip_rule_tables.php';
    ipRule('203.0.113.0/24');

    expect(fn () => $migration->down())->toThrow(RollbackRefusedException::class);

    config()->set('guardian.migrations.force_rollback', true);
    $migration->down();

    expect(Schema::hasTable('guardian_ip_rules'))->toBeFalse();
});

it('answers 403 ip_denied when a deny rule matches, in denylist mode by default', function (): void {
    ipRule('203.0.113.0/24');

    fromIp('203.0.113.5')->assertForbidden()->assertJson(['code' => 'ip_denied']);
    fromIp('198.51.100.5')->assertOk();
});

it('lets only allowed addresses through in allowlist mode, and deny wins', function (): void {
    config()->set('guardian.ip_rules.mode', 'allowlist');

    fromIp('10.1.2.3')->assertForbidden();

    ipRule('10.0.0.0/8', 'allow');
    fromIp('10.1.2.3')->assertOk();
    fromIp('192.168.1.1')->assertForbidden();

    ipRule('10.1.0.0/16');
    fromIp('10.1.2.3')->assertForbidden();
    fromIp('10.2.0.1')->assertOk();
});

it('handles IPv6 rules and single addresses', function (): void {
    ipRule('2001:db8::/32');
    ipRule('198.51.100.7');

    fromIp('2001:db8::1')->assertForbidden();
    fromIp('2001:db9::1')->assertOk();
    fromIp('198.51.100.7')->assertForbidden();
    fromIp('198.51.100.8')->assertOk();
});

it('ignores expired rules', function (): void {
    ipRule('203.0.113.0/24', expiresAt: now()->addMinutes(5)->toIso8601String());

    fromIp('203.0.113.5')->assertForbidden();

    $this->travel(6)->minutes();
    fromIp('203.0.113.5')->assertOk();
});

it('caches the rules and refreshes them on every write', function (): void {
    fromIp('203.0.113.5')->assertOk();

    $rule = ipRule('203.0.113.0/24');
    fromIp('203.0.113.5')->assertForbidden();

    app(DeleteIpRuleAction::class)->execute($rule);
    fromIp('203.0.113.5')->assertOk();
});

it('records ip.denied at most once a minute per address', function (): void {
    ipRule('203.0.113.0/24');

    fromIp('203.0.113.5')->assertForbidden();
    fromIp('203.0.113.5')->assertForbidden();
    fromIp('203.0.113.6')->assertForbidden();

    expect(SecurityEventModel::query()->where('type', 'ip.denied')->count())->toBe(2);

    $this->travel(61)->seconds();
    fromIp('203.0.113.5')->assertForbidden();

    expect(SecurityEventModel::query()->where('type', 'ip.denied')->count())->toBe(3)
        ->and(SecurityEventModel::query()->where('type', 'ip.denied')->first()?->ip)->toBe('203.0.113.5');
});
