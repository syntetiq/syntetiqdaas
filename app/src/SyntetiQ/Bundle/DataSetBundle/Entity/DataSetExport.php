<?php

namespace SyntetiQ\Bundle\DataSetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

#[ORM\Table(name: "syntetiq_data_set_export")]
#[Config(
    routeName: "syntetiq_model_data_set_index",
    routeView: "syntetiq_model_data_set_view",
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
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks()]
class DataSetExport implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: "data_set_id", type: Types::INTEGER, nullable: true)]
    protected ?int $dataSetId = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(name: 'finished_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $finishedAt = null;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param \DateTimeInterface $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTimeInterface $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $startedAt
     * @return $this
     */
    public function setStartedAt(\DateTimeInterface $startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param \DateTimeInterface $finishedAt
     * @return $this
     */
    public function setFinishedAt(\DateTimeInterface $finishedAt)
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    public function getDataSetId(): int
    {
        return $this->dataSetId;
    }

    public function setDataSetId(int $dataSetId): void
    {
        $this->dataSetId = $dataSetId;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
