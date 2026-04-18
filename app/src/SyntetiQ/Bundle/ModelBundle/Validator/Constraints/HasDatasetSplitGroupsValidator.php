<?php

namespace SyntetiQ\Bundle\ModelBundle\Validator\Constraints;

use SyntetiQ\Bundle\DataSetBundle\Model\Group;
use SyntetiQ\Bundle\ModelBundle\Dataset\ModelBuildDatasetItemProvider;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class HasDatasetSplitGroupsValidator extends ConstraintValidator
{
    private \ReflectionProperty $modelProperty;

    public function __construct(
        private ModelBuildDatasetItemProvider $datasetItemProvider
    )
    {
        $this->modelProperty = new \ReflectionProperty(ModelBuild::class, 'model');
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasDatasetSplitGroups) {
            throw new UnexpectedTypeException($constraint, HasDatasetSplitGroups::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ModelBuild) {
            throw new UnexpectedTypeException($value, ModelBuild::class);
        }

        if (!$this->modelProperty->isInitialized($value)) {
            return;
        }

        $counts = [
            Group::TRAIN => 0,
            Group::VAL => 0,
            Group::TEST => 0,
        ];

        foreach ($this->datasetItemProvider->getItems($value, true) as $item) {
            $group = $item->getGroup();
            if (isset($counts[$group])) {
                $counts[$group]++;
            }
        }

        $missingGroups = [];
        foreach ($counts as $group => $count) {
            if ($count === 0) {
                $missingGroups[] = $group;
            }
        }

        if ($missingGroups === []) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ missing_groups }}', implode(', ', $missingGroups))
            ->setParameter(
                '{{ selection }}',
                ($value->isReadyOnly() || $value->getTags() !== []) ? ' matching the selected filters' : ''
            )
            ->addViolation();
    }
}
