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
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\RelationSchema;
use Cycle\Schema\RelationInterface;

/**
 * Generate relations based on their schematic definitions.
 */
final class GenerateRelations implements GeneratorInterface
{
    // aliases between option names and their internal IDs
    public const OPTION_MAP = [
        'cascade'         => Relation::CASCADE,
        'nullable'        => Relation::NULLABLE,
        'innerKey'        => Relation::INNER_KEY,
        'outerKey'        => Relation::OUTER_KEY,
        'morphKey'        => Relation::MORPH_KEY,
        'thought'         => Relation::THOUGHT_ENTITY,
        'thoughInnerKey'  => Relation::THOUGHT_INNER_KEY,
        'thoughOuterKey'  => Relation::THOUGHT_OUTER_KEY,
        'thoughConstrain' => Relation::THOUGHT_CONSTRAIN,
        'thoughtWhere'    => Relation::THOUGHT_WHERE,
        'constrain'       => Relation::CONSTRAIN,
        'where'           => Relation::WHERE,
        'fkCreate'        => RelationSchema::FK_CREATE,
        'fkAction'        => RelationSchema::FK_ACTION,
        'indexCreate'     => RelationSchema::INDEX_CREATE,
        'bindInterface'   => RelationSchema::BIND_INTERFACE
    ];

    /** @var OptionSchema */
    private $options;

    /** @var RelationInterface[] */
    private $relations = [];

    /**
     * @param array             $relations
     * @param OptionSchema|null $optionSchema
     */
    public function __construct(array $relations, OptionSchema $optionSchema = null)
    {
        $this->options = $optionSchema ?? new OptionSchema(self::OPTION_MAP);

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
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            $this->register($registry, $entity);
        }

        foreach ($registry as $entity) {
            $this->inverse($registry, $entity);
        }

        return $registry;
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function register(Registry $registry, Entity $entity)
    {
        foreach ($entity->getRelations() as $name => $r) {
            if (!isset($this->relations[$r->getType()])) {
                throw new BuilderException("Undefined relation type `{$r->getType()}`");
            }

            $schema = $this->relations[$r->getType()]->withContext(
                $name,
                $entity->getRole(),
                $r->getTarget(),
                $this->options->withOptions($r->getOptions())
            );

            // compute relation values (field names, related entities and etc)
            $schema->compute($registry);

            $registry->registerRelation($entity, $name, $schema);
        }
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function inverse(Registry $registry, Entity $entity)
    {

    }
}