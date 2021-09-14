<?php

declare(strict_types=1);

namespace Cycle\Schema;

interface GeneratorInterface
{
    /**
     * Run generator over given registry.
     *
     * @param Registry $registry
     *
     * @return Registry
     */
    public function run(Registry $registry): Registry;
}
