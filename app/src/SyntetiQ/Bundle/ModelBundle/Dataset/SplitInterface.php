<?php

namespace SyntetiQ\Bundle\ModelBundle\Dataset;

use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

interface SplitInterface
{
    public function split(ModelBuild $modelBuild): array;
    public function isApplicable(string $type): bool;
}
