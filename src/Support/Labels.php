<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Support;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use RefactorCircus\Keystone\Audit\AuditHooks;
use Stringable;

/**
 * What Guardian's API calls a host's record (a user, a scope) for people to
 * read, with no knowledge of the model (D-053). The first answer wins:
 *
 * 1. a closure registered with `Guardian::label(Model::class, fn ($model) => ...)`;
 * 2. `guardian.labels`: a model class or morph alias mapped to an attribute
 *    name, or to an invokable class taking the model;
 * 3. Keystone's `AuditHooks` label, which packages (Roster) and hosts
 *    already register for their models;
 * 4. the first filled attribute of `name`, `title`, `label`, `display_name`
 *    or `email`.
 *
 * Null when none answers; clients fall back to the id.
 */
final class Labels
{
    /**
     * The attributes tried, in order, when nothing else names a model.
     */
    public const array ATTRIBUTES = ['name', 'title', 'label', 'display_name', 'email'];

    /**
     * @var array<class-string, Closure(Model): mixed>
     */
    private array $labels = [];

    public function __construct(
        private readonly Repository $config,
        private readonly Container $container,
    ) {}

    /**
     * Name models of this class, or a subclass, so.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @param  Closure(TModel): mixed  $label
     */
    public function using(string $class, Closure $label): self
    {
        /** @var Closure(Model): mixed $label */
        $this->labels[$class] = $label;

        return $this;
    }

    public function for(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        foreach ($this->labels as $class => $label) {
            if ($model instanceof $class) {
                return self::text($label($model));
            }
        }

        $configured = $this->configured($model);

        if ($configured !== null) {
            return self::text(is_string($configured) && ! class_exists($configured)
                ? $model->getAttribute($configured)
                : $this->invoke($configured, $model));
        }

        $hooked = self::text($this->container->make(AuditHooks::class)->labelFor($model));

        if ($hooked !== null) {
            return $hooked;
        }

        $attributes = $model->getAttributes();

        foreach (self::ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attributes) && ($text = self::text($model->getAttribute($attribute))) !== null) {
                return $text;
            }
        }

        return null;
    }

    /**
     * The `guardian.labels` entry for the model's class, a parent class or
     * its morph alias.
     */
    private function configured(Model $model): mixed
    {
        $labels = $this->config->get('guardian.labels', []);

        if (! is_array($labels) || $labels === []) {
            return null;
        }

        foreach ($labels as $type => $label) {
            $class = is_string($type) ? (Relation::getMorphedModel($type) ?? $type) : null;

            if ($class !== null && $model instanceof $class && $label !== null) {
                return $label;
            }
        }

        return null;
    }

    private function invoke(mixed $label, Model $model): mixed
    {
        $callable = is_string($label) ? $this->container->make($label) : $label;

        return is_callable($callable) ? $callable($model) : null;
    }

    private static function text(mixed $value): ?string
    {
        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
