<?php

declare(strict_types=1);

namespace Cycle\Schema\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\TableInheritanceException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class WrongParentKeyColumnException extends TableInheritanceException implements FriendlyExceptionInterface
{
    public function __construct(private Entity $entity, string $outerKey)
    {
        parent::__construct(sprintf(
            'Outer key column `%s` is not found among fields of the `%s` role.',
            $outerKey,
            (string)($this->entity->getRole() ?? $this->entity->getClass())
        ));
    }

    public function getName(): string
    {
        return 'Outer key column is not found among parent entity fields.';
    }

    public function getSolution(): ?string
    {
        $fields = implode('`, `', $this->entity->getFields()->getNames());

        return sprintf(
            'You have to specify one of the defined fields of the `%s` role: `%s`',
            (string)($this->entity->getRole() ?? $this->entity->getClass()),
            $fields
        );
    }
}
