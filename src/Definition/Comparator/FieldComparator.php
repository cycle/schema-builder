<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Comparator;

use Cycle\Schema\Definition\Field;
use Exception;
use InvalidArgumentException;

final class FieldComparator
{
    private $columnName;
    /** @var Field[] */
    private $fields = [];

    public function addField(string $key, Field $field): self
    {
        if ($this->columnName === null) {
            $this->columnName = $field->getColumn();
        }
        if ($this->columnName !== $field->getColumn()) {
            throw new InvalidArgumentException('The field comparator only accepts fields with the same column name.');
        }
        $this->fields[$key] = $field;
        return $this;
    }

    public function compare(): void
    {
        if (count($this->fields) <= 1) {
            return;
        }
        // Check options
        if (!$this->compareOptions() || !$this->compareProperties()) {
            throw new Exception(
                "Different definitions are specified for the `$this->columnName` column:"
                . "\n\n{$this->generateErrorText()}"
            );
        }
    }

    private function generateErrorText(): string
    {
        $lines = [];
        foreach ($this->fields as $key => $field) {
            $primary = $field->isPrimary() ? ' primary' : '';
            $line = sprintf("%s:\n  type=%s%s", $key, $field->getType(), $primary);
            // Print options
            foreach ($field->getOptions() as $optionName => $optionValue) {
                $line .= " {$optionName}=" . var_export($optionValue, true);
            }
            $lines[] = $line;
        }
        return implode("\n\n", $lines);
    }

    private function compareProperties(): bool
    {
        $tuples = array_map(static function (Field $field): array {
            return [
                $field->getType(),
                // $field->isPrimary(), // should not compared
            ];
        }, $this->fields);

        // Compare options content
        $prototype = array_shift($tuples);
        foreach ($tuples as $tuple) {
            if (count(array_diff_assoc($prototype, $tuple)) > 0) {
                return false;
            }
        }
        return true;
    }

    private function compareOptions(): bool
    {
        // Collect fields options
        $optionsSet = array_map(static function (Field $field): array {
            return iterator_to_array($field->getOptions());
        }, $this->fields);

        // Compare options cont
        $countResult = array_count_values(array_map('count', $optionsSet));
        if (count($countResult) !== 1) {
            return false;
        }

        // Compare options content
        $prototype = array_shift($optionsSet);
        foreach ($optionsSet as $options) {
            if (count(array_diff_assoc($prototype, $options)) > 0) {
                return false;
            }
        }

        return true;
    }
}
