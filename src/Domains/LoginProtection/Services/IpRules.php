<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Cache;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Whether an address may in (criterion 52): a matching deny rule refuses
 * it; in `allowlist` mode an address no allow rule matches is refused too,
 * and deny wins. Expired rules are ignored. The rules are cached in the
 * default store and forgotten whenever one is saved or deleted.
 */
final class IpRules
{
    public const string CACHE_KEY = 'guardian.ip_rules';

    public function __construct(private readonly Repository $config) {}

    public function denies(string $ip): bool
    {
        $now = now()->getTimestamp();
        $allowed = false;

        foreach ($this->rules() as $rule) {
            if ($rule['expires_at'] !== null && $rule['expires_at'] <= $now) {
                continue;
            }

            if (! IpUtils::checkIp($ip, $rule['cidr'])) {
                continue;
            }

            if ($rule['action'] === IpRuleModel::DENY) {
                return true;
            }

            $allowed = true;
        }

        return $this->config->get('guardian.ip_rules.mode', 'denylist') === 'allowlist' && ! $allowed;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<array{cidr: string, action: string, expires_at: int|null}>
     */
    private function rules(): array
    {
        /** @var list<array{cidr: string, action: string, expires_at: int|null}> */
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => IpRuleModel::query()->get()
            ->map(fn (IpRuleModel $rule): array => ['cidr' => $rule->cidr, 'action' => $rule->action, 'expires_at' => $rule->expires_at?->getTimestamp()])
            ->values()
            ->all());
    }
}
