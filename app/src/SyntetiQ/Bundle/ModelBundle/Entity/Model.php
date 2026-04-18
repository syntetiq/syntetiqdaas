<?php

namespace SyntetiQ\Bundle\ModelBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use SyntetiQ\Bundle\ModelBundle\Entity\Repository\ModelRepository;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;

#[ORM\Table(name: "syntetiq_model")]
#[Config(
    routeName: "syntetiq_model_model_index",
    routeView: "syntetiq_model_model_view",
    defaultValues: [
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'sq_entities']
    ]
)]
#[ORM\Entity(repositoryClass: ModelRepository::class)]
class Model implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: "string", length: 256, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    protected ?string $description = null;

    #[ORM\ManyToOne(targetEntity: DataSet::class)]
    #[ORM\JoinColumn(name: "data_set_id", referencedColumnName: "id", nullable: false, onDelete: "SET NULL")]
    protected ?DataSet $dataSet = null;

    #[ORM\OneToMany(
        mappedBy: "model",
        targetEntity: ModelBuild::class,
        cascade: ["ALL"],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(["id" => "ASC"])]
    protected $modelBuilds;

    #[ORM\OneToMany(
        mappedBy: 'model',
        targetEntity: ModelPretrained::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => 'DESC'])]
    protected Collection $pretrainedModels;

    public function __construct()
    {
        $this->name = null;
        $this->description = null;
        $this->modelBuilds = new ArrayCollection();
        $this->pretrainedModels = new ArrayCollection();
    }

    public function getModelBuilds()
    {
        return $this->modelBuilds;
    }

    public function setModelBuilds(ArrayCollection $modelBuilds): void
    {
        $this->modelBuilds = $modelBuilds;
    }

    public function addModelBuild(ModelBuild $modelBuild)
    {
        if (!$this->modelBuilds->contains($modelBuild)) {
            $modelBuild->setModel($this);
            $this->modelBuilds->add($modelBuild);
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPretrainedModels(): Collection
    {
        return $this->pretrainedModels;
    }

    public function addPretrainedModel(ModelPretrained $pretrainedModel): self
    {
        if (!$this->pretrainedModels->contains($pretrainedModel)) {
            $pretrainedModel->setModel($this);
            $this->pretrainedModels->add($pretrainedModel);
        }

        return $this;
    }

    public function setName(string $name): Model
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDescription(string $description): Model
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDataSet(): ?DataSet
    {
        return $this->dataSet;
    }

    public function setDataSet(?DataSet $dataSetTest): void
    {
        $this->dataSet = $dataSetTest;
    }
}
