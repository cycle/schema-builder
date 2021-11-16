<?php

declare(strict_types=1);

namespace Cycle\Schema\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\TableInheritanceException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class WrongDiscriminatorColumnException extends TableInheritanceException implements FriendlyExceptionInterface
{
    public function __construct(private Entity $entity, private string $discriminatorColumn)
    {
        parent::__construct($this->getName());
    }

    public function getName(): string
    {
        return sprintf(
            'Discriminator column `%s` not found among fields of the `%s` role.',
            $this->discriminatorColumn,
            $this->entity->getRole()
        );
    }

    public function getSolution(): ?string
    {
        $fields = implode('`, `', $this->entity->getFields()->getNames());

        return sprintf(
            'You have to specify one of defined field of the `%s` role: `%s`',
            $this->entity->getRole(),
            $fields
        );
    }
}
