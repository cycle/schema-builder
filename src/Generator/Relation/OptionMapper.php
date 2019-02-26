<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator\Relation;

use Cycle\Schema\Exception\OptionException;

final class OptionMapper
{
    /** @var array */
    private $map = [];

    /**
     * @param array $map
     */
    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @param iterable $options
     * @return array
     *
     * @throws OptionException
     */
    public function map(iterable $options): array
    {
        $result = [];
        foreach ($options as $name => $value) {
            if (!isset($this->map[$name])) {
                throw new OptionException("Undefined relation option `$name`");
            }

            $result[$this->map[$name]] = $value;
        }

        return $result;
    }
}