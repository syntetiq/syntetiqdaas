<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClientFactory;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;
use SyntetiQ\Bundle\OmniverseBundle\Entity\OmniverseSettings;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OmniverseClient
{
    private ManagerRegistry $doctrine;
    private LoggerInterface $logger;
    private GuzzleRestClientFactory $clientFactory;
    private RouterInterface $router;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        GuzzleRestClientFactory $clientFactory,
        RouterInterface $router
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->clientFactory = $clientFactory;
        $this->router = $router;
    }

    public function sendRequest(GenerateImagesRequest $request): ?string
    {
        $channel = $request->getIntegration() ?: $this->getActiveChannel();
        if (!$channel) {
            $this->logger->warning('No active Omniverse integration found.');
            return null;
        }

        /** @var OmniverseSettings $transport */
        $transport = $channel->getTransport();
        $targetUrl = $transport->getTargetUrl();

        if (!$targetUrl) {
            $this->logger->warning('Target URL not configured for Omniverse integration.');
            return null;
        }

        $payload = $this->buildPayload($request, $transport);

        try {
            $client = $this->clientFactory->createRestClient($targetUrl, []);
            $response = $client->post('', $payload);

            $responseBody = $response->getBodyAsString();
            $this->logger->info('Omniverse request sent successfully.', [
                'channelId' => $channel->getId(),
                'status' => $response->getStatusCode(),
                'body' => $responseBody
            ]);
            return $responseBody;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send Omniverse request.', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return null;
        }
    }

    private function getActiveChannel(): ?Channel
    {
        return $this->doctrine->getRepository(Channel::class)->findOneBy([
            'type' => 'omniverse',
            'enabled' => true
        ]);
    }

    private function buildPayload(GenerateImagesRequest $request, OmniverseSettings $settings): array
    {
        return [
            "usd_path" => $request->getScene(),
            "frames" => $request->getFrames(),
            "width" => $request->getWidth(),
            "height" => $request->getHeight(),
            "focal_length" => $request->getFocalLength(),
            "label_name" => $request->getLabelName(),
            "camera_pos" => [
                $request->getCameraPos()->getX(),
                $request->getCameraPos()->getY(),
                $request->getCameraPos()->getZ()
            ],
            "camera_pos_end" => [
                $request->getCameraPosEnd()->getX(),
                $request->getCameraPosEnd()->getY(),
                $request->getCameraPosEnd()->getZ()
            ],
            "camera_rotation" => [
                $request->getCameraRotation()->getX(),
                $request->getCameraRotation()->getY(),
                $request->getCameraRotation()->getZ()
            ],
            "spawn_cube" => $request->isSpawnCube(),
            "cube_translate" => [
                $request->getCubeTranslate()->getX(),
                $request->getCubeTranslate()->getY(),
                $request->getCubeTranslate()->getZ()
            ],
            "cube_scale" => [
                $request->getCubeScale()->getX(),
                $request->getCubeScale()->getY(),
                $request->getCubeScale()->getZ()
            ],
            "cube_size" => $request->getCubeSize(),
            "tmp_root" => $request->getTmpRoot(),
            "convert_images_to_jpeg" => $request->isConvertImagesToJpeg(),
            "jpeg_quality" => $request->getJpegQuality(),
            "cleanup_after_zip" => $request->isCleanupAfterZip(),
            "include_labels" => $request->getIncludeLabels(),
            "callback_url" => $settings->getCallbackUrl() ?? null,
            'hash_request' => md5($request->getId()),
            "request_id" => $request->getId()
        ];
    }
}
