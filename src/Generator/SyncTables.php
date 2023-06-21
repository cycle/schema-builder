<?php

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Schema\Exception\SyncException;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Cycle\Database\Schema\Reflector;

/**
 * Sync table schemas with database.
 */
final class SyncTables implements GeneratorInterface
{
    // Readonly tables must be included form the sync with database
    public const READONLY_SCHEMA = 'readonlySchema';

    /**
     * @param Registry $registry
     *
     * @throws SyncException
     *
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {
        foreach ($this->getRegistryDbList($registry) as $dbName) {
            $reflector = new Reflector();

            foreach ($registry as $regEntity) {
                if (
                    !$registry->hasTable($regEntity)
                    || $registry->getDatabase($regEntity) !== $dbName
                    || $regEntity->getOptions()->has(self::READONLY_SCHEMA)
                ) {
                    continue;
                }

                $reflector->addTable($registry->getTableSchema($regEntity));
            }

            try {
                $reflector->run();
            } catch (\Throwable $e) {
                throw new SyncException($e->getMessage(), (int) $e->getCode(), $e);
            }
        }

        return $registry;
    }

    /**
     * @param Registry $registry
     *
     * @return array
     */
    private function getRegistryDbList(Registry $registry): array
    {
        $databases = [];
        foreach ($registry as $regEntity) {
            if (!$registry->hasTable($regEntity)) {
                continue;
            }
            $dbName = $registry->getDatabase($regEntity);
            if (in_array($dbName, $databases, true)) {
                continue;
            }

            $databases[] = $dbName;
        }

        return $databases;
    }
}
