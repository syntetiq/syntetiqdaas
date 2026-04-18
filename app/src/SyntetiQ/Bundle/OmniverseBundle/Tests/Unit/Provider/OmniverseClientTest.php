<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClientFactory;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;
use SyntetiQ\Bundle\OmniverseBundle\Entity\OmniverseSettings;
use SyntetiQ\Bundle\OmniverseBundle\Entity\Value\Vector3;
use SyntetiQ\Bundle\OmniverseBundle\Provider\OmniverseClient;

class OmniverseClientTest extends TestCase
{
    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var GuzzleRestClientFactory|MockObject */
    private $clientFactory;

    /** @var RouterInterface|MockObject */
    private $router;

    /** @var OmniverseClient */
    private $client;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->clientFactory = $this->createMock(GuzzleRestClientFactory::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->client = new OmniverseClient(
            $this->doctrine,
            $this->logger,
            $this->clientFactory,
            $this->router
        );
    }

    public function testSendRequestNoActiveChannel()
    {
        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Channel::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('No active Omniverse integration found.');

        $this->client->sendRequest(new GenerateImagesRequest());
    }

    public function testSendRequestNoTargetUrl()
    {
        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(OmniverseSettings::class);

        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Channel::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($channel);

        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $transport->expects($this->once())
            ->method('getTargetUrl')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Target URL not configured for Omniverse integration.');

        $this->client->sendRequest(new GenerateImagesRequest());
    }

    public function testSendRequestSuccess()
    {
        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(OmniverseSettings::class);
        $restClient = $this->createMock(GuzzleRestClient::class);
        $request = $this->createMock(GenerateImagesRequest::class);
        $response = $this->createMock(GuzzleRestResponse::class);

        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $transport->expects($this->once())
            ->method('getTargetUrl')
            ->willReturn('http://example.com');

        $this->clientFactory->expects($this->once())
            ->method('createRestClient')
            ->with('http://example.com', [])
            ->willReturn($restClient);

        // Mock request data
        $request->method('getIntegration')->willReturn($channel);
        $request->method('getIncludeLabels')->willReturn([]);
        $request->method('getCameraPos')->willReturn(new Vector3(0,0,0));
        $request->method('getCameraPosEnd')->willReturn(new Vector3(0,0,0));
        $request->method('getCameraRotation')->willReturn(new Vector3(0,0,0));
        $request->method('getCubeTranslate')->willReturn(new Vector3(0,0,0));
        $request->method('getCubeScale')->willReturn(new Vector3(0,0,0));
        $request->method('getCubeSize')->willReturn(1.0);
        $request->method('getId')->willReturn(1);

        $restClient->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->once())
            ->method('getBodyAsString')
            ->willReturn('success');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Omniverse request sent successfully.');

        $this->client->sendRequest($request);
    }

    public function testSendRequestException()
    {
        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(OmniverseSettings::class);
        $restClient = $this->createMock(GuzzleRestClient::class);
        $request = $this->createMock(GenerateImagesRequest::class);

        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $transport->expects($this->once())
            ->method('getTargetUrl')
            ->willReturn('http://example.com');

        $this->clientFactory->expects($this->once())
            ->method('createRestClient')
            ->with('http://example.com', [])
            ->willReturn($restClient);

        // Mock request data
        $request->method('getIntegration')->willReturn($channel);
        $request->method('getIncludeLabels')->willReturn([]);
        $request->method('getCameraPos')->willReturn(new Vector3(0,0,0));
        $request->method('getCameraPosEnd')->willReturn(new Vector3(0,0,0));
        $request->method('getCameraRotation')->willReturn(new Vector3(0,0,0));
        $request->method('getCubeTranslate')->willReturn(new Vector3(0,0,0));
        $request->method('getCubeScale')->willReturn(new Vector3(0,0,0));
        $request->method('getCubeSize')->willReturn(1.0);
        $request->method('getId')->willReturn(1);

        $restClient->expects($this->once())
            ->method('post')
            ->willThrowException(new \Exception('Network error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to send Omniverse request.');

        $this->client->sendRequest($request);
    }
}
