<?php

namespace SyntetiQ\Bundle\ModelBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AclOwnerFieldFormExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        private readonly ManagerRegistry $doctrine,
    ) {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['ownership_disabled']) {
            return;
        }

        $formConfig = $builder->getFormConfig();
        if (!$formConfig->getCompound()) {
            return;
        }

        $dataClass = $formConfig->getDataClass();
        if (!$dataClass || !$this->supports($dataClass)) {
            return;
        }

        // Run after Oro ownership logic so the default owner can still be assigned on create.
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData'], -255);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit'], -255);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('ownership_disabled', false);
    }

    public function postSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->getParent() || !$form->has('owner')) {
            return;
        }

        $entity = $event->getData();
        $subject = is_object($entity) && method_exists($entity, 'getId') && $entity->getId()
            ? $entity
            : (string) $form->getConfig()->getDataClass();

        if ($this->canAssignOwner($subject)) {
            return;
        }

        $form->remove('owner');
    }

    public function preSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->getParent()) {
            return;
        }

        $data = $event->getData();
        $dataClass = $form->getConfig()->getDataClass();
        if (!$dataClass || !is_array($data) || !array_key_exists('owner', $data) || $this->canAssignOwner($dataClass)) {
            return;
        }

        unset($data['owner']);
        $event->setData($data);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    private function supports(string $className): bool
    {
        if (!str_starts_with($className, 'SyntetiQ\\Bundle\\')) {
            return false;
        }

        if (null === $this->doctrine->getManagerForClass($className)) {
            return false;
        }

        $metadata = $this->ownershipMetadataProvider->getMetadata($className);

        return $metadata->hasOwner() && !$metadata->isOrganizationOwned();
    }

    private function canAssignOwner(object|string $subject): bool
    {
        $className = is_object($subject) ? $subject::class : $subject;
        if (!$this->supports($className)) {
            return true;
        }

        $aclSubject = is_object($subject) ? $subject : 'entity:' . $className;

        return $this->authorizationChecker->isGranted('ASSIGN', $aclSubject);
    }
}
