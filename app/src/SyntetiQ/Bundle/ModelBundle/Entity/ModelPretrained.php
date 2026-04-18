<?php

namespace SyntetiQ\Bundle\ModelBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use Symfony\Component\Validator\Constraints as Assert;
use SyntetiQ\Bundle\ModelBundle\Entity\Repository\ModelPretrainedRepository;

#[ORM\Entity(repositoryClass: ModelPretrainedRepository::class)]
#[ORM\Table(name: 'syntetiq_model_pretrained')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'syntetiq_model_model_index',
    routeView: 'syntetiq_model_model_view',
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
class ModelPretrained implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Model::class)]
    #[ORM\JoinColumn(name: 'model_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Model $model = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[ORM\Column(name: 'original_filename', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $originalFilename = '';

    #[ORM\Column(name: 'engine', type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $engine = '';

    #[ORM\Column(name: 'engine_model', type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $engineModel = '';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function setEngine(string $engine): void
    {
        $this->engine = $engine;
    }

    public function getEngineModel(): string
    {
        return $this->engineModel;
    }

    public function setEngineModel(string $engineModel): void
    {
        $this->engineModel = $engineModel;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    #[ORM\PrePersist]
    public function beforeSave(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }

    public function getDisplayLabel(): string
    {
        if ($this->name !== '' && $this->name !== $this->originalFilename) {
            return sprintf('%s (%s)', $this->name, $this->originalFilename);
        }

        return $this->originalFilename !== '' ? $this->originalFilename : $this->name;
    }
}
