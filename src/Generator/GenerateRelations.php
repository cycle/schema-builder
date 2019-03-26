<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\Relation;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\InversableInterface;
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
        'though'          => Relation::THOUGH_ENTITY,
        'thoughInnerKey'  => Relation::THOUGH_INNER_KEY,
        'thoughOuterKey'  => Relation::THOUGH_OUTER_KEY,
        'thoughConstrain' => Relation::THOUGH_CONSTRAIN,
        'thoughWhere'     => Relation::THOUGH_WHERE,
        'constrain'       => Relation::CONSTRAIN,
        'where'           => Relation::WHERE,
        'fkCreate'        => RelationSchema::FK_CREATE,
        'fkAction'        => RelationSchema::FK_ACTION,
        'indexCreate'     => RelationSchema::INDEX_CREATE,
        'morphKeyLength'  => RelationSchema::MORPH_KEY_LENGTH
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
            $schema = $this->initRelation($r->getType())->withContext(
                $name,
                $entity->getRole(),
                $r->getTarget(),
                $this->options->withOptions($r->getOptions())
            );

            // compute relation values (field names, related entities and etc)
            try {
                $schema->compute($registry);
            } catch (RelationException $e) {
                throw new SchemaException(
                    "Unable to compute relation `{$entity->getRole()}`.`{$name}`",
                    $e->getCode(),
                    $e
                );
            }

            $registry->registerRelation($entity, $name, $schema);
        }
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function inverse(Registry $registry, Entity $entity)
    {
        foreach ($entity->getRelations() as $name => $r) {
            if (!$r->isInversed()) {
                continue;
            }

            $schema = $registry->getRelation($entity, $name);
            if (!$schema instanceof InversableInterface) {
                throw new SchemaException("Unable to inverse relation of type " . get_class($schema));
            }

            if (!isset($this->relations[$r->getInverseType()])) {
                throw new RegistryException("Undefined relation type `{$r->getType()}`");
            }

            try {
                $inversed = $schema->inverseRelation(
                    $this->initRelation($r->getInverseType()),
                    $r->getInverseName()
                );

                $registry->registerRelation(
                    $registry->getEntity($r->getTarget()),
                    $r->getInverseName(),
                    $inversed
                );
            } catch (RelationException $e) {
                throw new SchemaException(
                    "Unable to inverse relation `{$entity->getRole()}`.`{$name}`",
                    $e->getCode(),
                    $e
                );
            }
        }
    }

    /**
     * @param string $type
     * @return RelationInterface
     */
    protected function initRelation(string $type): RelationInterface
    {
        if (!isset($this->relations[$type])) {
            throw new RegistryException("Undefined relation type `{$type}`");
        }

        return $this->relations[$type];
    }
}