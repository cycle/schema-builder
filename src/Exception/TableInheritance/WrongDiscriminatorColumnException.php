<?php

declare(strict_types=1);

namespace Cycle\Schema\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\TableInheritanceException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class WrongDiscriminatorColumnException extends TableInheritanceException implements FriendlyExceptionInterface
{
    public function __construct(private Entity $entity, string $discriminatorColumn)
    {
        parent::__construct(sprintf(
            'Discriminator column `%s` is not found among fields of the `%s` role.',
            $discriminatorColumn,
            (string)($this->entity->getRole() ?? $this->entity->getClass())
        ));
    }

    public function getName(): string
    {
        return 'Discriminator column is not found among the entity fields.';
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
