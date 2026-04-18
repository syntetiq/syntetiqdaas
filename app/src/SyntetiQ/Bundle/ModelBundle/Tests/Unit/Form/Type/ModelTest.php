<?php

namespace SyntetiQ\Bundle\ModelBundle\Tests\Unit\Form\Type;

use PHPUnit\Framework\TestCase;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;

class ModelTest extends TestCase
{
    /** @var Model */
    protected $type;

    protected function setUp(): void
    {
        $this->type = new Model();
    }

    protected function tearDown(): void
    {
        unset($this->type);
    }

    public function testFields()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $model = \SyntetiQ\Bundle\ModelBundle\Entity\Model('SyntetiQ\Bundle\ModelBundle\Entity\Model')
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->any())->method('getId')->willReturn(null);
        $builder->expects($this->any())->method('add')->willReturn($builder);

        $modelType = \SyntetiQ\Bundle\ModelBundle\Entity\Model('SyntetiQ\Bundle\ModelBundle\Entity\ModelType')->getMock();
        $modelType->expects($this->any())->method('getId')->willReturn(1);

        $parent = \SyntetiQ\Bundle\ModelBundle\Entity\Model('SyntetiQ\Bundle\ModelBundle\Entity\Model')->getMock();
        $parent->expects($this->any())->method('getId')->willReturn(1);

        $satelliteOf = \SyntetiQ\Bundle\ModelBundle\Entity\Model(Model::class)->getMock();
        $satelliteOf->expects($this->any())->method('getId')->willReturn(1);

        $options = [
            'modelType' => $modelType,
        ];
        $this->type->buildForm($builder, $options);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testHasName()
    {
        $this->assertEquals('syntetiq_model_model', $this->type->getName());
    }
}
