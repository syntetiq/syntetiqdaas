<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\OmniverseBundle\Entity\Repository\GenerateImagesRequestRepository;
use SyntetiQ\Bundle\OmniverseBundle\Entity\Value\Vector3;

#[ORM\Entity(repositoryClass: GenerateImagesRequestRepository::class)]
#[ORM\Table(name: "sq_generate_images_request")]
#[Config(
    routeName: "syntetiq_generate_images_request_index",
    routeCreate: "syntetiq_generate_images_request_create",
    defaultValues: [
        'entity' => [
            'icon' => 'fa-shopping-cart'
        ],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'dataaudit' => ['auditable' => true],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'sq_entities']
    ]
)]
#[ORM\HasLifecycleCallbacks]
class GenerateImagesRequest implements
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: "frames", type: Types::INTEGER, nullable: false)]
    public ?int $frames = null;

    #[ORM\Column(name: "width", type: Types::INTEGER, nullable: false)]
    public ?int $width = null;

    #[ORM\Column(name: "height", type: Types::INTEGER, nullable: false)]
    public ?int $height = null;

    #[ORM\Column(name: "focal_length", type: Types::INTEGER, nullable: false)]
    public ?int $focalLength = null;

    #[ORM\Column(name: "label_name", type: Types::STRING, nullable: false)]
    public ?string $labelName = null;

    #[ORM\Column(name: "scene", type: Types::STRING, nullable: false)]
    public ?string $scene = null;

    #[ORM\Embedded(class: Vector3::class, columnPrefix: "camera_pos_")]
    private Vector3 $cameraPos;

    #[ORM\Embedded(class: Vector3::class, columnPrefix: "camera_pos_end_")]
    private Vector3 $cameraPosEnd;

    #[ORM\Embedded(class: Vector3::class, columnPrefix: "camera_rotation_")]
    private Vector3 $cameraRotation;

    #[ORM\Column(name: "spawn_cube", type: Types::BOOLEAN, nullable: false)]
    public bool $spawnCube = false;

    #[ORM\Embedded(class: Vector3::class, columnPrefix: "cube_translate_")]
    private Vector3 $cubeTranslate;

    #[ORM\Embedded(class: Vector3::class, columnPrefix: "cube_scale_")]
    private Vector3 $cubeScale;

    #[ORM\Column(name: "cube_size", type: Types::FLOAT, nullable: false)]
    public ?float $cubeSize = null;

    #[ORM\Column(name: "tmp_root", type: Types::STRING, nullable: false)]
    public ?string $tmpRoot = null;

    #[ORM\Column(name: "convert_images_to_jpeg", type: Types::BOOLEAN, nullable: false)]
    public bool $convertImagesToJpeg = false;

    #[ORM\Column(name: "jpeg_quality", type: Types::INTEGER, nullable: false)]
    public ?int $jpegQuality = null;

    #[ORM\Column(name: "cleanup_after_zip", type: Types::BOOLEAN, nullable: false)]
    public bool $cleanupAfterZip = false;

    #[ORM\Column(name: "status", type: Types::STRING, length: 255, nullable: false, options: ["default" => "new"])]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(name: "hash", type: Types::STRING, length: 255, nullable: true)]
    private ?string $hash = null;

    #[ORM\Column(name: "sent_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(name: "handled_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $handledAt = null;

    #[ORM\Column(name: "response", type: Types::TEXT, nullable: true)]
    private ?string $response = null;

    #[ORM\Column(name: "include_labels_text", type: Types::TEXT, nullable: true)]
    private ?string $includeLabelsText = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'integration_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Channel $integration = null;

    const STATUS_NEW = 'new';
    const STATUS_SENT = 'sent';
    const STATUS_HANDLED = 'handled';

    #[ORM\ManyToOne(targetEntity: DataSet::class)]
    #[ORM\JoinColumn(name: 'data_set_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected $dataSet;

    public function __construct()
    {
        $this->frames = 100;
        $this->focalLength = 6;
        $this->labelName = 'cube';
        $this->scene = '/Isaac/Environments/Simple_Warehouse/warehouse.usd';
        $this->cameraPos       = new Vector3(2, 4.22024, 2.60473);
        $this->cameraPosEnd    = new Vector3(2.5, 1.6, 2.3);
        $this->cameraRotation  = new Vector3(76, 0, 100);
        $this->spawnCube = true;
        $this->cubeTranslate   = new Vector3(0.8, 3.0, 0.2);
        $this->cubeScale       = new Vector3(0.2, 0.2, 0.2);
        $this->cubeSize = 1.0;
        $this->tmpRoot = 'data/tmp';
        $this->convertImagesToJpeg = true;
        $this->jpegQuality = 75;
        $this->cleanupAfterZip = true;
        $this->status          = self::STATUS_NEW;
    }


    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFrames(): ?int
    {
        return $this->frames;
    }

    public function setFrames(int $frames): void
    {
        $this->frames = $frames;
    }

    public function getFocalLength(): ?int
    {
        return $this->focalLength;
    }

    public function setFocalLength(int $focalLength): void
    {
        $this->focalLength = $focalLength;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    public function getLabelName(): ?string
    {
        return $this->labelName;
    }

    public function setLabelName(string $labelName): void
    {
        $this->labelName = $labelName;
    }

    public function getScene(): ?string
    {
        return $this->scene;
    }

    public function setScene(string $scene): void
    {
        $this->scene = $scene;
    }

    public function getCameraPos(): Vector3
    {
        return $this->cameraPos;
    }

    public function setCameraPos(Vector3 $cameraPos): void
    {
        $this->cameraPos = $cameraPos;
    }

    public function getCameraPosEnd(): Vector3
    {
        return $this->cameraPosEnd;
    }

    public function setCameraPosEnd(Vector3 $cameraPosEnd): void
    {
        $this->cameraPosEnd = $cameraPosEnd;
    }

    public function getCameraRotation(): Vector3
    {
        return $this->cameraRotation;
    }

    public function setCameraRotation(Vector3 $cameraRotation): void
    {
        $this->cameraRotation = $cameraRotation;
    }

    public function isSpawnCube(): bool
    {
        return $this->spawnCube;
    }

    public function setSpawnCube(bool $spawnCube): void
    {
        $this->spawnCube = $spawnCube;
    }

    public function getCubeTranslate(): Vector3
    {
        return $this->cubeTranslate;
    }

    public function setCubeTranslate(Vector3 $cubeTranslate): void
    {
        $this->cubeTranslate = $cubeTranslate;
    }

    public function getCubeScale(): Vector3
    {
        return $this->cubeScale;
    }

    public function setCubeScale(Vector3 $cubeScale): void
    {
        $this->cubeScale = $cubeScale;
    }

    public function getCubeSize(): float
    {
        return $this->cubeSize;
    }

    public function setCubeSize(float $cubeSize): void
    {
        $this->cubeSize = $cubeSize;
    }

    public function getTmpRoot(): ?string
    {
        return $this->tmpRoot;
    }

    public function setTmpRoot(string $tmpRoot): void
    {
        $this->tmpRoot = $tmpRoot;
    }

    public function isConvertImagesToJpeg(): bool
    {
        return $this->convertImagesToJpeg;
    }

    public function setConvertImagesToJpeg(bool $convertImagesToJpeg): void
    {
        $this->convertImagesToJpeg = $convertImagesToJpeg;
    }

    public function getJpegQuality(): ?int
    {
        return $this->jpegQuality;
    }

    public function setJpegQuality(int $jpegQuality): void
    {
        $this->jpegQuality = $jpegQuality;
    }

    public function isCleanupAfterZip(): bool
    {
        return $this->cleanupAfterZip;
    }

    public function setCleanupAfterZip(bool $cleanupAfterZip): void
    {
        $this->cleanupAfterZip = $cleanupAfterZip;
    }

    /**
     * @return DataSet|null
     */
    public function getDataSet()
    {
        return $this->dataSet;
    }

    /**
     * @param DataSet|null $dataSet
     * @return $this
     */
    public function setDataSet($dataSet)
    {
        $this->dataSet = $dataSet;

        return $this;
    }

    public function getIncludeLabelsText(): ?string
    {
        return $this->includeLabelsText !== '' ? $this->includeLabelsText : null;
    }

    public function setIncludeLabelsText(?string $includeLabelsText): void
    {
        $labels = self::normalizeIncludeLabels($includeLabelsText);
        $this->includeLabelsText = $labels ? implode(PHP_EOL, $labels) : null;
    }

    public function getIncludeLabels(): array
    {
        return self::normalizeIncludeLabels($this->getIncludeLabelsText());
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getIntegration(): ?Channel
    {
        return $this->integration;
    }

    public function setIntegration(?Channel $integration): void
    {
        $this->integration = $integration;
    }

    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    public function getHandledAt(): ?\DateTimeInterface
    {
        return $this->handledAt;
    }

    public function setHandledAt(?\DateTimeInterface $handledAt): void
    {
        $this->handledAt = $handledAt;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): void
    {
        $this->response = $response;
    }

    private static function normalizeIncludeLabels(array|string|null $labels): array
    {
        if (is_array($labels)) {
            $values = $labels;
        } elseif ($labels === null || $labels === '') {
            return [];
        } else {
            $values = preg_split('/[\r\n,]+/', $labels) ?: [];
        }

        $normalized = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }
}
