<?php

declare(strict_types=1);

namespace Cycle\Schema\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\TableInheritanceException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class DiscriminatorColumnNotPresentException extends TableInheritanceException implements FriendlyExceptionInterface
{
    public function __construct(private Entity $entity)
    {
        parent::__construct($this->getName());
    }

    public function getName(): string
    {
        return sprintf(
            'Discriminator column for the `%s` role should be defined.', $this->entity->getRole()
        );
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
