<?php

namespace SyntetiQ\Bundle\ModelBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Form\Type\ModelType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;

class ModelController extends AbstractController
{
    public function __construct(
        private UpdateHandlerFacade $formUpdateHandler,
        private TranslatorInterface $translator,
        private array $dataEngines
    ) {
    }

    #[Route(path: '/', name: 'syntetiq_model_model_index')]
    #[Template('@SyntetiQModel/Model/index.html.twig')]
    #[AclAncestor("syntetiq_model_model_view")]
    public function indexAction()
    {
        return ['gridName' => 'syntetiq-model-model-grid'];
    }

    #[Route(path: '/view/{id}', name: 'syntetiq_model_model_view', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQModel/Model/view.html.twig')]
    #[AclAncestor("syntetiq_model_model_view")]
    public function viewAction(Model $model)
    {
        return [
            'entity' => $model,
            'engines' => $this->dataEngines,
        ];
    }

    #[Route(path: '/{id}/edit', name: 'syntetiq_model_model_edit', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQModel/Model/edit.html.twig')]
    #[AclAncestor("syntetiq_model_model_edit")]
    public function editAction(Request $request, Model $model)
    {
        return $this->update($model, $request);
    }

    #[Route(path: '/create', name: 'syntetiq_model_model_create')]
    #[Template('@SyntetiQModel/Model/edit.html.twig')]
    #[AclAncestor("syntetiq_model_model_create")]
    public function createAction(Request $request)
    {
        return $this->update(new Model(), $request);
    }

    /**
     * @param Model $model
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(Model $model, Request $request)
    {
        return $this->formUpdateHandler->update(
            $model,
            ModelType::class,
            $this->translator->trans('syntetiq.controller.model.saved.message'),
            $request
        );
    }
}
