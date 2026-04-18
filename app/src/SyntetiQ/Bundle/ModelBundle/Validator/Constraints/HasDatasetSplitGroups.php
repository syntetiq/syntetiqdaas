<?php

namespace SyntetiQ\Bundle\ModelBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class HasDatasetSplitGroups extends Constraint
{
    public string $message = 'syntetiq.modelbuild.validation.dataset_split_groups';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
