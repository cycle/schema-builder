<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator;

use Cycle\ORM\Relation;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\BuilderException;
use Cycle\Schema\Generator\Relation\OptionMapper;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Cycle\Schema\RelationInterface;

/**
 * Generate relations based on their schematic definitions.
 */
class RelationGenerator implements GeneratorInterface
{
    // default option mapping
    protected const OPTION_MAP = [
        'innerKey' => Relation::INNER_KEY,
    ];

    /** @var OptionMapper */
    private $optionMapper;

    /** @var RelationInterface[] */
    private $relations = [];

    /**
     * @param array $relations
     * @param array $optionMap
     */
    public function __construct(array $relations, array $optionMap = [])
    {
        $this->optionMapper = new OptionMapper($optionMap ?? self::OPTION_MAP);

        foreach ($relations as $id => $relation) {
            if (!$relation instanceof RelationInterface) {
                throw new \InvalidArgumentException(sprintf(
                    "Invalid relation type, RelationInterface excepted, `%s` given",
                    is_object($relation) ? get_class($relation) : gettype($relation)
                ));
            }

            $this->relations[$id] = $relation;
        }
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     */
    public function compute(Registry $registry, Entity $entity)
    {
        foreach ($entity->getRelations() as $name => $r) {
            if (!isset($this->relations[$r->getType()])) {
                throw new BuilderException("Undefined relation type `{$r->getType()}`");
            }

            $schema = $this->relations[$r->getType()]->withContext(
                $entity->getRole(),
                $r->getTarget(),
                $this->optionMapper->map($r->getOptions())
            );

            $registry->registerRelation($entity, $name, $schema);
        }
    }
}