<?php

namespace SyntetiQ\Bundle\ModelBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class ProcessRunner
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {}

    public function run(ModelBuild $modelBuild): void
    {
        $modelBuild->initialize();
        $em = $this->doctrine->getManagerForClass(ModelBuild::class);
        $em->persist($modelBuild);
        $em->flush();
    }
}
