<?php

namespace SyntetiQ\Bundle\ModelBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use SyntetiQ\Bundle\DataSetBundle\Model\ImageSize;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;
use SyntetiQ\Bundle\ModelBundle\Entity\Repository\ModelBuildRepository;

#[ORM\Entity(repositoryClass:ModelBuildRepository::class)]
#[ORM\Table(name: 'syntetiq_model_build')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: "syntetiq_model_model_index",
    routeView: "syntetiq_model_model_build_view",
    routeCreate: "syntetiq_model_model_build_create",
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
class ModelBuild  implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Model::class, inversedBy: "modelBuilds")]
    #[ORM\JoinColumn(name: 'model_id', referencedColumnName: 'id', nullable: false, onDelete: "CASCADE")]
    protected Model $model;

    #[ORM\Column(name: 'context', type: Types::TEXT, nullable: true)]
    protected ?string $context = null;

    #[ORM\Column(name: 'output', type: Types::TEXT, nullable: true)]
    protected ?string $output = null;

    #[ORM\Column(name: 'error_output', type: Types::TEXT, nullable: true)]

    protected ?string $errorOutput = null;

    #[ORM\Column(name:"result_file", type: Types::TEXT, nullable: true)]
    protected ?string $resultFile = null;

    #[ORM\Column(name: 'env', type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $env = null;

    #[ORM\Column(name: 'epoch', type: Types::INTEGER)]
    #[Assert\NotBlank]
    private ?int $epoch = null;

    #[ORM\Column(name: 'engine', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\NotBlank]
    private ?string $engine = null;

    #[ORM\Column(name: 'engine_model', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\NotBlank]
    private ?string $engineModel = null;

    #[ORM\Column(name: 'image_size', type: Types::STRING, length: 50, options: ['default' => ImageSize::SIZE_640_640])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $imageSize = ImageSize::SIZE_640_640;

    #[ORM\Column(name: 'is_initialized', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $initialized = false;

    #[ORM\Column(name: 'percent_test_items', type: Types::FLOAT, nullable: true)]
    private float $percentTestItems = 0.03;

    #[ORM\Column(name: 'percent_validation_items', type: Types::FLOAT, nullable: true)]
    private float $percentValidationItems = 0.15;

    #[ORM\Column(name: 'percent_train_items', type: Types::FLOAT, nullable: true)]
    private float $percentTrainItems = 0.82;

    #[ORM\Column(name: 'ready_only', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $readyOnly = false;

    #[ORM\Column(name: 'tags', type: Types::JSON, nullable: true)]
    private ?array $tags = [];

    #[ORM\ManyToOne(targetEntity: ModelPretrained::class)]
    #[ORM\JoinColumn(name: 'pretrained_model_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ModelPretrained $pretrainedModel = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.started_at']])]
    protected ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(name: 'finished_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.finished_at']])]
    protected ?\DateTimeInterface $finishedAt = null;

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

    #[ORM\Column(name: 'calculate_dataset_qa', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $calculateDatasetQa = true;

    #[ORM\Column(name: 'deepstream_export', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $deepstreamExport = false;

    public function __construct()
    {
        $this->createdAt = null;
        $this->output = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
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

    public function getDatasetQaErrorOutput(): ?string
    {
        return $this->datasetQaErrorOutput;
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

    public function setDatasetQaErrorOutput(?string $datasetQaErrorOutput): self
    {
        $this->datasetQaErrorOutput = $datasetQaErrorOutput;

        return $this;
    }

    public function isCalculateDatasetQa(): bool
    {
        return $this->calculateDatasetQa;
    }

    public function setCalculateDatasetQa(bool $calculateDatasetQa): self
    {
        $this->calculateDatasetQa = $calculateDatasetQa;

        return $this;
    }

    public function isDeepstreamExport(): bool
    {
        return $this->deepstreamExport;
    }

    public function setDeepstreamExport(bool $deepstreamExport): self
    {
        $this->deepstreamExport = $deepstreamExport;

        return $this;
    }

    /**
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    public function hasModel(): bool
    {
        return isset($this->model);
    }

    /**
     * @param mixed $model
     */
    public function setModel($model): void
    {
        $this->model = $model;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

   public function getErrorOutput(): ?string
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(string $errorOutput): void
    {
        $this->errorOutput = $errorOutput;
    }

    public function getResultFile(): string
    {
        return $this->resultFile;
    }

    public function setResultFile(string $resultFile): void
    {
        $this->resultFile = $resultFile;
    }

    public function getEnv(): ?string
    {
        return $this->env;
    }

    public function setEnv(string $env): self
    {
        $this->env = $env;

        return $this;
    }

    public function getEpoch(): ?int
    {
        return $this->epoch;
    }

    public function setEpoch(int $epoch): self
    {
        $this->epoch = $epoch;

        return $this;
    }

    /**
     * Indicates whether the authorization code has been revoked or not.
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Revokes the authorization code.
     */
    public function initialize()
    {
        $this->initialized = true;
    }

    /**
     * Revokes the authorization code.
     */
    public function setInitialize($value)
    {
        $this->initialized = $value;
    }

    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return mixed
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param mixed $engine
     */
    public function setEngine($engine): void
    {
        $this->engine = $engine;
    }

    /**
     * @return mixed
     */
    public function getEngineModel()
    {
        return $this->engineModel;
    }

    /**
     * @param mixed $engineModel
     */
    public function setEngineModel($engineModel): void
    {
        $this->engineModel = $engineModel;
    }

    public function getImageSize(): string
    {
        return $this->imageSize;
    }

    public function setImageSize(string $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getPercentTestItems(): float
    {
        return $this->percentTestItems;
    }

    public function setPercentTestItems(float $percentTestItems): void
    {
        $this->percentTestItems = $percentTestItems;
    }

    public function getPercentValidationItems(): float
    {
        return $this->percentValidationItems;
    }

    public function setPercentValidationItems(float $percentValidationItems): void
    {
        $this->percentValidationItems = $percentValidationItems;
    }

    public function getPercentTrainItems(): float
    {
        return $this->percentTrainItems;
    }

    public function setPercentTrainItems(float $percentTrainItems): void
    {
        $this->percentTrainItems = $percentTrainItems;
    }

    public function isReadyOnly(): bool
    {
        return $this->readyOnly;
    }

    public function setReadyOnly(bool $readyOnly): void
    {
        $this->readyOnly = $readyOnly;
    }

    public function getTags(): array
    {
        return $this->normalizeTags($this->tags ?? []);
    }

    public function setTags(array $tags): void
    {
        $this->tags = $this->normalizeTags($tags);
    }

    public function getPretrainedModel(): ?ModelPretrained
    {
        return $this->pretrainedModel;
    }

    public function setPretrainedModel(?ModelPretrained $pretrainedModel): void
    {
        $this->pretrainedModel = $pretrainedModel;
    }

    #[Assert\Callback]
    public function validateBaseBuildCompatibility(ExecutionContextInterface $context): void
    {
        if (!$this->hasModel()) {
            return;
        }

        if (null !== $this->pretrainedModel) {
            if (
                null === $this->pretrainedModel->getModel()
                || $this->pretrainedModel->getModel()->getId() !== $this->getModel()->getId()
                || $this->pretrainedModel->getEngine() !== $this->getEngine()
                || $this->pretrainedModel->getEngineModel() !== $this->getEngineModel()
            ) {
                $context->buildViolation('syntetiq.modelbuild.validation.pretrained_model_compatible')
                    ->atPath('pretrainedModel')
                    ->addViolation();
            }
        }
    }

    private function normalizeTags(array $tags): array
    {
        $normalizedTags = [];
        foreach ($tags as $tag) {
            $tag = trim((string) $tag);
            if ($tag === '' || in_array($tag, $normalizedTags, true)) {
                continue;
            }

            $normalizedTags[] = $tag;
        }

        return $normalizedTags;
    }
}
