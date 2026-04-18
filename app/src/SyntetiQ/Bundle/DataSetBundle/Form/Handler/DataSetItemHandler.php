<?php

namespace SyntetiQ\Bundle\DataSetBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use SyntetiQ\Bundle\DataSetBundle\Service\DataSetItemImageManager;

class DataSetItemHandler implements FormHandlerInterface
{
    public function __construct(
        private ObjectManager $manager,
        private DataSetItemImageManager $dataSetItemImageManager,
    ) {}

    public function process($data, FormInterface $form, Request $request): bool
    {
        $form->setData($data);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data->touch();
                $this->manager->persist($data);
                $this->manager->flush();
                $this->dataSetItemImageManager->syncImageSize($data);
                $this->manager->persist($data);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
