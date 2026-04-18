<?php

namespace SyntetiQ\Bundle\ModelBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ModelHandler implements FormHandlerInterface
{
    public function __construct(
        private ObjectManager $manager
    ) {
    }

    public function process($data, FormInterface $form, Request $request): bool
    {
        $form->setData($data);

        if ($request->isMethod('POST')) {
            $form->submit($request->get($form->getName()));
            if ($form->isValid()) {
                $this->manager->persist($data);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
