<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;
use SyntetiQ\Bundle\OmniverseBundle\Form\Type\GenerateImagesRequestType;

class RequestController extends AbstractController
{
    private const DEFAULT_IMAGE_SIZE = 640;

    #[Route(path: '/', name: 'syntetiq_generate_images_request_index')]
    #[Template('@SyntetiQOmniverse/Request/index.html.twig')]
    #[AclAncestor("syntetiq_generate_images_request_view")]
    public function indexAction()
    {
        return ['gridName' => 'syntetiq-generate-images-request-grid'];
    }

    #[Route(path: '/create/{dataSetId}', name: 'syntetiq_generate_images_request_create', requirements: ['dataSetId' => '\d+'], defaults: ['dataSetId' => 0])]
    #[Template('@SyntetiQOmniverse/Request/update.html.twig')]
    #[AclAncestor("syntetiq_generate_images_request_view")]
    public function createAction(Request $request, #[MapEntity(class: DataSet::class, id: 'dataSetId')] DataSet $dataSet)
    {
        $entity = new GenerateImagesRequest();
        $entity->setDataSet($dataSet);
        $entity->setIntegration($this->resolveDefaultIntegration());
        $entity->setWidth(self::DEFAULT_IMAGE_SIZE);
        $entity->setHeight(self::DEFAULT_IMAGE_SIZE);

        return $this->update($entity, $request);
    }

    #[Route(path: '/{id}', name: 'syntetiq_generate_images_request_update', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQOmniverse/Request/update.html.twig')]
    #[AclAncestor("syntetiq_generate_images_request_edit")]
    public function updateAction(GenerateImagesRequest $generateImagesRequest, Request $request)
    {
        return $this->update($generateImagesRequest, $request);
    }

    protected function update(GenerateImagesRequest $generateImagesRequest, Request $request): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $generateImagesRequest,
            GenerateImagesRequestType::class,
            $this->container->get(TranslatorInterface::class)
                ->trans('syntetiq.omniverse.controller.generation_images_request.saved.message'),
            $request
        );
    }

    private function resolveDefaultIntegration(): ?Channel
    {
        $repository = $this->container->get(ManagerRegistry::class)->getRepository(Channel::class);

        $channels = $repository->findBy(['type' => 'omniverse', 'enabled' => true], ['name' => 'ASC']);

        return $channels ? reset($channels) : null;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                ManagerRegistry::class,
                TranslatorInterface::class,
            ]
        );
    }
}
