<?php

declare(strict_types=1);

namespace Cycle\Schema\Exception\FieldException;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\FieldException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class EmbeddedPrimaryKeyException extends FieldException implements FriendlyExceptionInterface
{
    public function __construct(Entity $embed, string $fieldName)
    {
        parent::__construct("Entity `{$embed->getRole()}` has conflicted field `{$fieldName}`.");
    }

    public function getName(): string
    {
        return 'Embedded entity primary key collision';
    }

    public function getSolution(): ?string
    {
        return "The primary key of the composite entity must be projected onto the embedded entity.\n"
            . "However, the embedded entity already has a field with the same name.\n\n"
            . "Possible solutions:\n"
            . "- If the conflicting field applies only to an embedded entity, then rename it.\n"
            . '- If you want to receive the primary key value of a composite entity in this field,'
            . ' then remove its definition from the column list in the schema.';
    }
}
