<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Controller\Api;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyntetiQ\Bundle\DataSetBundle\Provider\ImportDataSetProvider;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class CallbackController extends AbstractController
{
    #[Route(path: '/api/omniverse/callback', name: 'syntetiq_omniverse_api_callback', methods: ['POST'])]
    public function callbackAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $error = null;

        if (!isset($data['hash'], $data['fileName'])) {
            $error = 'Invalid request id and hash';
        }

        /** @var  GenerateImagesRequest $generateImagesRequest */
        $generateImagesRequest = $this->container->get(DoctrineHelper::class)->getEntityRepositoryForClass(GenerateImagesRequest::class)
            ->findOneBy(['hash' => $data['hash']]);

        if (!$generateImagesRequest) {
            $error = 'Invalid request hash';
        }

        if ($error) {
            return new JsonResponse(['error' => $error], 400);
        }

        $extraData = [
            'sourceType' => 'omniverse',
            'tag' => $this->buildOmniverseImportTag($generateImagesRequest),
        ];
        $integration = $generateImagesRequest->getIntegration();
        if (!$integration) {
            $integration = $this->container->get(DoctrineHelper::class)
                ->getEntityRepositoryForClass(Channel::class)
                ->findOneBy(['type' => 'omniverse', 'enabled' => true]);
        }
        if ($integration) {
            $extraData['sourceIntegrationId'] = $integration->getId();
        }

        $this->container->get(ImportDataSetProvider::class)->sendMessage(
            $data['fileName'],
            $generateImagesRequest->getDataSet()->getId(),
            $extraData
        );

        $generateImagesRequest->setStatus(GenerateImagesRequest::STATUS_HANDLED);
        $generateImagesRequest->setHandledAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->container->get(DoctrineHelper::class)->getEntityManager(GenerateImagesRequest::class)->flush();

        return new JsonResponse(['message' => 'Processed', 'data' => $data]);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                DoctrineHelper::class,
                TranslatorInterface::class,
                ImportDataSetProvider::class
            ]
        );
    }

    private function buildOmniverseImportTag(GenerateImagesRequest $generateImagesRequest): string
    {
        $hash = $generateImagesRequest->getHash() ?: md5((string) $generateImagesRequest->getId());

        return substr($hash, 0, 8);
    }
}
