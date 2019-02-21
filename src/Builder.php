<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

class Builder
{
    /** @var Entity[] */
    private $entities = [];

    public function __construct()
    {
    }

    public function register(Entity $entity, ?string $database, ?string $table)
    {
        $this->entities[] = $entity;
    }
}