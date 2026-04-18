<?php

namespace SyntetiQ\Bundle\DataSetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: "syntetiq_data_set_item")]
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
#[ORM\HasLifecycleCallbacks]
class DataSetItem implements ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UpdatedAtAwareTrait;
    use UserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(
        targetEntity: DataSet::class,
        inversedBy: "items"
    )]
    #[ORM\JoinColumn(name: "data_set_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    protected ?DataSet $dataSet = null;

    #[ORM\OneToMany(
        mappedBy: 'dataSetItem',
        targetEntity: ItemObjectConfiguration::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    protected ?Collection $objectConfiguration;

    #[ORM\OneToMany(
        mappedBy: 'dataSetItem',
        targetEntity: DataSetItemTag::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    private ?Collection $itemTags;

    #[ORM\Column(name: "img_width", type: Types::INTEGER, nullable: false)]
    protected int $imgWidth = 640;

    #[ORM\Column(name: "img_height", type: Types::INTEGER, nullable: false)]
    protected int $imgHeight = 640;

    #[ORM\Column(name: "source_type", type: "string", length: 32, options: ['default' => 'manual'])]
    private string $sourceType = 'manual';

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: "source_integration_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private $sourceIntegration;

    #[ORM\Column(name: "external_id", type: "string", length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(name: "item_group", type: Types::STRING, length: 20, nullable: true)]
    protected ?string $group = null;

    #[ORM\Column(name: 'tag', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $legacyTag = null;

    #[ORM\Column(name: 'ready', type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $ready = false;

    #[ORM\Column(name: 'import_id', type: Types::INTEGER, nullable: true)]
    private ?int $importId = null;

    public function __construct()
    {
        $this->objectConfiguration = new ArrayCollection();
        $this->itemTags = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!$this->getUpdatedAt()) {
            $this->touch();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDataSet()
    {
        return $this->dataSet;
    }

    /**
     * @param mixed $dataSet
     */
    public function setDataSet($dataSet): void
    {
        $this->dataSet = $dataSet;
    }

    public function getObjectConfiguration()
    {
        return $this->objectConfiguration;
    }

    public function setObjectConfiguration($items): void
    {
        $this->objectConfiguration = $items;
    }

    public function addObjectConfiguration(ItemObjectConfiguration $item)
    {
        if (!$this->objectConfiguration->contains($item)) {
            $item->setDataSetItem($this);
            $this->objectConfiguration->add($item);
        }

        return $this;
    }

    /**
     * @param DataSetItem $item
     *
     * @return $this
     */
    public function removeObjectConfiguration(ItemObjectConfiguration $item)
    {
        if ($item->getId() === null) {
            if ($this->objectConfiguration->contains($item)) {
                $this->objectConfiguration->removeElement($item);
            }

            return $this;
        }

        foreach ($this->objectConfiguration as $objectConfiguration) {
            if ($item->getId() === $objectConfiguration->getId()) {
                $this->objectConfiguration->removeElement($objectConfiguration);
            }
        }

        return $this;
    }

    public function clearObjectConfiguration(): void
    {
        foreach ($this->objectConfiguration?->toArray() ?? [] as $objectConfiguration) {
            if (!$objectConfiguration instanceof ItemObjectConfiguration) {
                continue;
            }

            $this->removeObjectConfiguration($objectConfiguration);
        }
    }

    public function hasObjectConfigurations(): bool
    {
        return (bool) ($this->objectConfiguration?->count() ?? 0);
    }

    /**
     * @return mixed
     */
    public function getImgWidth()
    {
        return $this->imgWidth;
    }

    /**
     * @param mixed $imgWidth
     */
    public function setImgWidth($imgWidth): void
    {
        $this->imgWidth = $imgWidth;
    }

    /**
     * @return mixed
     */
    public function getImgHeight()
    {
        return $this->imgHeight;
    }

    /**
     * @param mixed $imgHeight
     */
    public function setImgHeight($imgHeight): void
    {
        $this->imgHeight = $imgHeight;
    }

    /**
     * @return mixed
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getSourceIntegration()
    {
        return $this->sourceIntegration;
    }

    public function setSourceIntegration($sourceIntegration): void
    {
        $this->sourceIntegration = $sourceIntegration;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getTag(): ?string
    {
        $tags = $this->getTags();

        return $tags[0] ?? null;
    }

    public function setTag(?string $tag): void
    {
        $tag = trim((string) $tag);
        $this->setTags($tag === '' ? [] : [$tag]);
    }

    public function addTag(string $tag): bool
    {
        $normalizedTag = $this->normalizeTags([$tag])[0] ?? null;
        if ($normalizedTag === null) {
            return false;
        }

        $tags = $this->getTags();
        if (in_array($normalizedTag, $tags, true)) {
            return false;
        }

        $tags[] = $normalizedTag;
        $this->setTags($tags);

        return true;
    }

    public function removeTag(string $tag): bool
    {
        $normalizedTag = $this->normalizeTags([$tag])[0] ?? null;
        if ($normalizedTag === null) {
            return false;
        }

        $tags = $this->getTags();
        if (!in_array($normalizedTag, $tags, true)) {
            return false;
        }

        $this->setTags(array_values(array_filter(
            $tags,
            static fn (string $existingTag): bool => $existingTag !== $normalizedTag
        )));

        return true;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        $tags = [];
        foreach ($this->itemTags ?? [] as $itemTag) {
            if (!$itemTag instanceof DataSetItemTag) {
                continue;
            }

            $tag = trim($itemTag->getName());
            if ($tag === '' || in_array($tag, $tags, true)) {
                continue;
            }

            $tags[] = $tag;
        }

        if ($tags === [] && $this->legacyTag !== null) {
            $legacyTag = trim($this->legacyTag);
            if ($legacyTag !== '') {
                $tags[] = $legacyTag;
            }
        }

        return $tags;
    }

    public function setTags(array $tags): void
    {
        $normalizedTags = $this->normalizeTags($tags);
        $existingTags = [];

        foreach (($this->itemTags ?? new ArrayCollection())->toArray() as $itemTag) {
            if (!$itemTag instanceof DataSetItemTag) {
                continue;
            }

            $name = trim($itemTag->getName());
            if (!in_array($name, $normalizedTags, true)) {
                $this->itemTags?->removeElement($itemTag);
                $itemTag->setDataSetItem(null);
                continue;
            }

            $existingTags[] = $name;
        }

        foreach ($normalizedTags as $tag) {
            if (in_array($tag, $existingTags, true)) {
                continue;
            }

            $itemTag = new DataSetItemTag();
            $itemTag->setName($tag);
            $itemTag->setDataSetItem($this);
            $this->itemTags?->add($itemTag);
        }

        $this->legacyTag = $normalizedTags[0] ?? null;
    }

    public function getTagsDisplay(): ?string
    {
        $tags = $this->getTags();

        return $tags === [] ? null : implode(', ', $tags);
    }

    public function getItemTags(): Collection
    {
        return $this->itemTags ?? new ArrayCollection();
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function setReady(bool $ready): void
    {
        $this->ready = $ready;
    }

    public function getImportId(): ?int
    {
        return $this->importId;
    }

    public function setImportId(?int $importId): void
    {
        $this->importId = $importId;
    }

    public function touch(): void
    {
        $this->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @return string[]
     */
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
