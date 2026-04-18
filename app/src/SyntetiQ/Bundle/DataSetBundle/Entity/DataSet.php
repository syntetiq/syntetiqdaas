<?php

namespace SyntetiQ\Bundle\DataSetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;


#[ORM\Table(name: "syntetiq_data_set")]
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
#[ORM\HasLifecycleCallbacks]
class DataSet implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: "name", type: "string", length: 256, nullable: false)]
    protected ?string $name = null;

    #[ORM\OneToMany(
        mappedBy: "dataSet",
        targetEntity: DataSetItem::class,
        cascade: ["ALL"],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(["id" => "ASC"])]
    protected $items;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'dataset_qa_status', type: Types::STRING, length: 20, options: ['default' => DatasetQaStatus::IDLE])]
    private string $datasetQaStatus = DatasetQaStatus::IDLE;

    #[ORM\Column(name: 'dataset_qa_started_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datasetQaStartedAt = null;

    #[ORM\Column(name: 'dataset_qa_finished_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datasetQaFinishedAt = null;

    #[ORM\Column(name: 'dataset_qa_heartbeat_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datasetQaHeartbeatAt = null;

    #[ORM\Column(name: 'dataset_qa_progress', type: Types::FLOAT, nullable: true)]
    private ?float $datasetQaProgress = null;

    #[ORM\Column(name: 'dataset_qa_progress_message', type: Types::STRING, length: 255, nullable: true)]
    private ?string $datasetQaProgressMessage = null;

    #[ORM\Column(name: 'dataset_qa_error_output', type: Types::TEXT, nullable: true)]
    private ?string $datasetQaErrorOutput = null;

    public function __construct()
    {
        $this->name = null;
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): DataSet
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setItems(ArrayCollection $items): void
    {
        $this->items = $items;
    }

    public function addItem(DataSetItem $item)
    {
        if (!$this->items->contains($item)) {
            $item->setDataSet($this);
            $this->items->add($item);
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getDatasetQaStatus(): string
    {
        return $this->datasetQaStatus;
    }

    public function setDatasetQaStatus(string $datasetQaStatus): self
    {
        $this->datasetQaStatus = $datasetQaStatus;

        return $this;
    }

    public function getDatasetQaStartedAt(): ?\DateTimeInterface
    {
        return $this->datasetQaStartedAt;
    }

    public function setDatasetQaStartedAt(?\DateTimeInterface $datasetQaStartedAt): self
    {
        $this->datasetQaStartedAt = $datasetQaStartedAt;

        return $this;
    }

    public function getDatasetQaFinishedAt(): ?\DateTimeInterface
    {
        return $this->datasetQaFinishedAt;
    }

    public function setDatasetQaFinishedAt(?\DateTimeInterface $datasetQaFinishedAt): self
    {
        $this->datasetQaFinishedAt = $datasetQaFinishedAt;

        return $this;
    }

    public function getDatasetQaHeartbeatAt(): ?\DateTimeInterface
    {
        return $this->datasetQaHeartbeatAt;
    }

    public function setDatasetQaHeartbeatAt(?\DateTimeInterface $datasetQaHeartbeatAt): self
    {
        $this->datasetQaHeartbeatAt = $datasetQaHeartbeatAt;

        return $this;
    }

    public function getDatasetQaProgress(): ?float
    {
        return $this->datasetQaProgress;
    }

    public function setDatasetQaProgress(?float $datasetQaProgress): self
    {
        $this->datasetQaProgress = $datasetQaProgress;

        return $this;
    }

    public function getDatasetQaProgressMessage(): ?string
    {
        return $this->datasetQaProgressMessage;
    }

    public function setDatasetQaProgressMessage(?string $datasetQaProgressMessage): self
    {
        $this->datasetQaProgressMessage = $datasetQaProgressMessage;

        return $this;
    }

    public function getDatasetQaErrorOutput(): ?string
    {
        return $this->datasetQaErrorOutput;
    }

    public function setDatasetQaErrorOutput(?string $datasetQaErrorOutput): self
    {
        $this->datasetQaErrorOutput = $datasetQaErrorOutput;

        return $this;
    }

    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
