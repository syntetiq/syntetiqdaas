<?php

namespace SyntetiQ\Bundle\DataSetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: "syntetiq_data_set_item_tag")]
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
class DataSetItemTag implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DataSetItem::class, inversedBy: 'itemTags')]
    #[ORM\JoinColumn(name: 'data_set_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?DataSetItem $dataSetItem = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataSetItem(): ?DataSetItem
    {
        return $this->dataSetItem;
    }

    public function setDataSetItem(?DataSetItem $dataSetItem): void
    {
        $this->dataSetItem = $dataSetItem;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
