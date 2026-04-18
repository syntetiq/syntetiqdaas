<?php

namespace SyntetiQ\Bundle\ModelBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use SyntetiQ\Bundle\ModelBundle\Tests\Functional\DataFixtures\LoadModelData;

/**
 * @dbIsolationPerTest
 */
class ModelControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadModelData::class
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('syntetiq_model_model_index'));
        $link = $crawler->selectLink('Create Model')->link();
        $this->client->followRedirects();
        $crawler = $this->client->click($link);
        $this->assertContains('Create Model', $crawler->html());
    }

    public function testCreate()
    {
        $this->markTestSkipped('Need to fix');
        $crawler = $this->client->request('GET', $this->getUrl('syntetiq_model_model_create'));
        $buttonCrawlerNode = $crawler->selectLink('Save and Close');
        /** @var Form $form */
        $form = $buttonCrawlerNode->form($this->getData());
        $this->client->submit($form);
        $this->client->followRedirects(true);
        $this->assertContains('Tutorial', $this->client->getResponse()->getContent());
    }

    /**
     * @return array
     */
    protected function getData()
    {
        $typeId = $this->getReference(LoadModelData::REFERENCE_PREFIX . 'Team Play')->getId();
        return [
            'syntetiq_type_model[name]' => 'Tutorial',
            'syntetiq_type_model[description]' => '',
            'syntetiq_type_model[width]' => '3',
            'syntetiq_type_model[height]' => '1',
        ];
    }
}
