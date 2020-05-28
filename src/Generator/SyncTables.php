<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Schema\Exception\SyncException;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Spiral\Database\Schema\Reflector;

/**
 * Sync table schemas with database.
 */
final class SyncTables implements GeneratorInterface
{
    // Readonly tables must be included form the sync with database
    public const READONLY_SCHEMA = 'readonlySchema';

    /**
     * @param Registry $registry
     * @return Registry
     *
     * @throws SyncException
     */
    public function run(Registry $registry): Registry
    {
        $databases = [];
        foreach ($registry as $regEntity) {
            if ($registry->hasTable($regEntity) && !$regEntity->getOptions()->has(SyncTables::READONLY_SCHEMA)) {
                $databases[$registry->getDatabase($regEntity)][] = $registry->getTableSchema($regEntity);
            }
        }

        foreach ($databases as $database => $tables) {
            $reflector = new Reflector();

            foreach ($tables as $table) {
                $reflector->addTable($table);
            }

            try {
                $reflector->run();
            } catch (\Throwable $e) {
                throw new SyncException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $registry;
    }
}
