<?php

namespace SyntetiQ\Bundle\DataSetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use SyntetiQ\Bundle\DataSetBundle\Entity\Repository\ItemObjectConfigurationRepository;

#[ORM\Table(name: "syntetiq_data_set_item_obj_config")]
#[Config(defaultValues: [
    'ownership' => [
        'owner_type' => 'USER',
        'owner_field_name' => 'owner',
        'owner_column_name' => 'user_owner_id',
        'organization_field_name' => 'organization',
        'organization_column_name' => 'organization_id'
    ],
    'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'sq_entities']
])]
#[ORM\Entity(repositoryClass: ItemObjectConfigurationRepository::class)]
class ItemObjectConfiguration implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(
        targetEntity: DataSetItem::class,
        inversedBy: "objectConfiguration"
    )]
    #[ORM\JoinColumn(name: "data_set_item_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    protected ?DataSetItem $dataSetItem = null;


    #[ORM\Column(name: "name", type: Types::STRING, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: "truncated", type: Types::BOOLEAN, nullable: false, options: ["default" => false])]
    protected bool $truncated = false;

    #[ORM\Column(name: "min_x", type: Types::INTEGER, nullable: false)]
    protected int $minX;

    #[ORM\Column(name: "max_x", type: Types::INTEGER, nullable: false)]
    protected int $maxX;

    #[ORM\Column(name: "min_y", type: Types::INTEGER, nullable: false)]
    protected int $minY;

    #[ORM\Column(name: "max_y", type: Types::INTEGER, nullable: false)]
    protected int $maxY;

    public function __construct()
    {
        $this->name = null;
        $this->truncated = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): ItemObjectConfiguration
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDataSetItem(): DataSetItem
    {
        return $this->dataSetItem;
    }

    public function setDataSetItem(DataSetItem $dataSetItem): void
    {
        $this->dataSetItem = $dataSetItem;
    }

    public function isTruncated(): bool
    {
        return $this->truncated;
    }

    public function setTruncated(bool $truncated): void
    {
        $this->truncated = $truncated;
    }

    public function getMinX(): int
    {
        return $this->minX;
    }

    public function setMinX(int $minX): void
    {
        $this->minX = $minX;
    }

    public function getMaxX(): int
    {
        return $this->maxX;
    }

    public function setMaxX(int $maxX): void
    {
        $this->maxX = $maxX;
    }

    public function getMinY(): int
    {
        return $this->minY;
    }

    public function setMinY(int $minY): void
    {
        $this->minY = $minY;
    }

    public function getMaxY(): int
    {
        return $this->maxY;
    }

    public function setMaxY(int $maxY): void
    {
        $this->maxY = $maxY;
    }
}
