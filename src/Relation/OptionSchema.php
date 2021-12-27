<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation;

use Cycle\Schema\Exception\OptionException;

/**
 * Calculate missing option values using template and relation context.
 */
final class OptionSchema
{
    private array $options = [];

    private array $template = [];

    private array $context = [];

    public function __construct(private array $aliases)
    {
    }

    public function __debugInfo(): array
    {
        $result = [];

        foreach ($this->template as $option => $value) {
            $value = $this->get($option);

            $alias = array_search($option, $this->aliases, true);
            $result[$alias] = $value;
        }

        return $result;
    }

    /**
     * Get available options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Create new option set with user provided options.
     */
    public function withOptions(iterable $options): self
    {
        $r = clone $this;

        foreach ($options as $name => $value) {
            if (!array_key_exists($name, $r->aliases) && !array_key_exists($name, $r->template)) {
                throw new OptionException("Undefined relation option `{$name}`");
            }

            $r->options[$name] = $value;
        }

        return $r;
    }

    /**
     * Create new option set with option rendering template. Template expect to allocate
     * relation options only in a integer constants.
     */
    public function withTemplate(array $template): self
    {
        $r = clone $this;
        $r->template = $template;

        return $r;
    }

    /**
     * Create new option set with relation context values (i.e. relation name, target name and etc).
     */
    public function withContext(array $context): self
    {
        $r = clone $this;
        $r->context += $context;

        return $r;
    }

    /**
     * Check if option has been defined.
     */
    public function has(int $option): bool
    {
        return array_key_exists($option, $this->template);
    }

    /**
     * Get calculated option value.
     */
    public function get(int $option): mixed
    {
        if (!$this->has($option)) {
            throw new OptionException("Undefined relation option `{$option}`");
        }

        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        // user defined value
        foreach ($this->aliases as $alias => $targetOption) {
            if ($targetOption === $option && isset($this->options[$alias])) {
                return $this->options[$alias];
            }
        }

        // non template value
        $value = $this->template[$option];
        if (!is_string($value)) {
            return $value;
        }

        $value = $this->calculate($option, $value);

        if (strpos($value, '|') !== false) {
            return array_filter(explode('|', $value));
        }

        return $value;
    }

    /**
     * Calculate option value using templating.
     */
    private function calculate(int $option, string $value): string
    {
        foreach ($this->context as $name => $ctxValue) {
            $ctxValue = is_array($ctxValue) ? implode('|', $ctxValue) . '|' : $ctxValue;
            $value = $this->injectValue($name, $ctxValue, $value);
        }

        foreach ($this->aliases as $name => $targetOption) {
            if ($option !== $targetOption) {
                $value = $this->injectOption($name, $targetOption, $value);
            }
        }

        return $value;
    }

    private function injectOption(string $name, int $option, string $target): string
    {
        if (!str_contains($target, "{{$name}}")) {
            return $target;
        }

        $name = "{{$name}}";
        $replace = $this->get($option);
        if (is_array($replace)) {
            return implode('|', array_map(static function (string $replace) use ($name, $target) {
                return str_replace($name, $replace, $target);
            }, $replace));
        }

        return str_replace($name, $replace, $target);
    }

    private function injectValue(string $name, string $value, string $target): string
    {
        return str_replace("{{$name}}", $value, $target);
    }
}
