<?php

namespace SyntetiQ\Bundle\ModelBundle\Dataset;

use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class Test implements SplitInterface
{
    public function __construct()
    {
    }

    public function isApplicable(string $type): bool
    {
        return $type === 'test';
    }

    public function split(ModelBuild $modelBuild): array
    {
        return [];
    }
}
