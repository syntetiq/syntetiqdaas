<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;
use SyntetiQ\Bundle\OmniverseBundle\Entity\Value\Vector3;

class GenerateImagesRequestTest extends TestCase
{
    public function testDefaultValues()
    {
        $request = new GenerateImagesRequest();

        $this->assertEquals(100, $request->getFrames());
        $this->assertEquals(6, $request->getFocalLength());
        $this->assertEquals('cube', $request->getLabelName());
        $this->assertEquals('/Isaac/Environments/Simple_Warehouse/warehouse.usd', $request->getScene());
        $this->assertEquals(new Vector3(2, 4.22024, 2.60473), $request->getCameraPos());
        $this->assertEquals(new Vector3(2.5, 1.6, 2.3), $request->getCameraPosEnd());
        $this->assertEquals(new Vector3(76, 0, 100), $request->getCameraRotation());
        $this->assertEquals(new Vector3(0.8, 3.0, 0.2), $request->getCubeTranslate());
        $this->assertEquals(new Vector3(0.2, 0.2, 0.2), $request->getCubeScale());
        $this->assertEquals(1.0, $request->getCubeSize());
        $this->assertEquals('data/tmp', $request->getTmpRoot());
        $this->assertEquals(75, $request->getJpegQuality());
        $this->assertEquals(GenerateImagesRequest::STATUS_NEW, $request->getStatus());
        $this->assertTrue($request->isSpawnCube());
        $this->assertTrue($request->isConvertImagesToJpeg());
        $this->assertTrue($request->isCleanupAfterZip());
        $this->assertSame([], $request->getIncludeLabels());
        $this->assertNull($request->getIncludeLabelsText());
    }

    public function testSettersAndGetters()
    {
        $request = new GenerateImagesRequest();

        $request->setFrames(100);
        $this->assertEquals(100, $request->getFrames());

        $request->setWidth(1920);
        $this->assertEquals(1920, $request->getWidth());

        $request->setHeight(1080);
        $this->assertEquals(1080, $request->getHeight());

        $request->setFocalLength(50);
        $this->assertEquals(50, $request->getFocalLength());

        $request->setLabelName('test_label');
        $this->assertEquals('test_label', $request->getLabelName());

        $vector = new Vector3(1, 2, 3);
        $request->setCameraPos($vector);
        $this->assertEquals($vector, $request->getCameraPos());

        $request->setSpawnCube(true);
        $this->assertTrue($request->isSpawnCube());

        $request->setCubeSize(1.5);
        $this->assertEquals(1.5, $request->getCubeSize());

        $request->setTmpRoot('/tmp/test');
        $this->assertEquals('/tmp/test', $request->getTmpRoot());

        $request->setConvertImagesToJpeg(true);
        $this->assertTrue($request->isConvertImagesToJpeg());

        $request->setJpegQuality(90);
        $this->assertEquals(90, $request->getJpegQuality());

        $request->setCleanupAfterZip(true);
        $this->assertTrue($request->isCleanupAfterZip());

        $request->setIncludeLabelsText("car\nbus, truck");
        $this->assertSame(['car', 'bus', 'truck'], $request->getIncludeLabels());
        $this->assertSame("car\nbus\ntruck", $request->getIncludeLabelsText());

        $request->setStatus(GenerateImagesRequest::STATUS_SENT);
        $this->assertEquals(GenerateImagesRequest::STATUS_SENT, $request->getStatus());

        $request->setHash('test_hash');
        $this->assertEquals('test_hash', $request->getHash());

        $date = new \DateTime();
        $request->setSentAt($date);
        $this->assertSame($date, $request->getSentAt());

        $request->setHandledAt($date);
        $this->assertSame($date, $request->getHandledAt());

        $integration = $this->createMock(Channel::class);
        $request->setIntegration($integration);
        $this->assertSame($integration, $request->getIntegration());
    }

    public function testPrePersist()
    {
        $request = new GenerateImagesRequest();
        $request->prePersist();

        $this->assertNotNull($request->getCreatedAt());
        $this->assertNotNull($request->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $request = new GenerateImagesRequest();
        $request->preUpdate();

        $this->assertNotNull($request->getUpdatedAt());
    }
}
