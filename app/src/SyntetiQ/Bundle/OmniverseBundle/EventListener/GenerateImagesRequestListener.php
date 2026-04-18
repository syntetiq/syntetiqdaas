<?php

namespace SyntetiQ\Bundle\OmniverseBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;
use SyntetiQ\Bundle\OmniverseBundle\Provider\OmniverseClient;

class GenerateImagesRequestListener
{
    private OmniverseClient $client;

    public function __construct(OmniverseClient $client)
    {
        $this->client = $client;
    }

    public function postPersist(GenerateImagesRequest $request, LifecycleEventArgs $args): void
    {
        $request->setHash(md5($request->getId()));
        $request->setStatus(GenerateImagesRequest::STATUS_SENT);
        $request->setSentAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $args->getObjectManager()->flush();

        $response = $this->client->sendRequest($request);
        if ($response !== null) {
            $request->setResponse($response);
            $args->getObjectManager()->flush();
        }
    }
}
