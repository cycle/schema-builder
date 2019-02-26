<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Definition\Traits;

use Cycle\Schema\Definition\Map\OptionMap;
use Cycle\Schema\Exception\OptionException;

trait OptionsTrait
{
    /** @var OptionMap */
    private $options;

    /**
     * @return OptionMap
     */
    public function getOptions(): OptionMap
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return OptionsTrait
     *
     * @throws OptionException
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $name => $option) {
            $this->options->set($name, $option);
        }

        return $this;
    }
}