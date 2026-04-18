<?php

namespace SyntetiQ\Bundle\ModelBundle\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class TestScriptController extends AbstractController
{
    #[Route(path: '/test-script', name: 'syntetiq_model_test_script_index')]
    #[Template('@SyntetiQModel/TestScript/index.html.twig')]
    public function indexAction(): array
    {
        return [];
    }
}
