<?php

namespace SyntetiQ\Bundle\ModelBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelPretrained;

class ModelPretrainedUploadController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private array $dataEngines
    ) {
    }

    #[Route(
        path: '/model/{id}/upload-pretrained',
        name: 'syntetiq_model_model_upload_pretrained',
        requirements: ['id' => '\d+'],
        options: ['expose' => true]
    )]
    #[AclAncestor('syntetiq_model_model_edit')]
    public function uploadAction(Request $request, Model $model): JsonResponse
    {
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid pretrained model file.'], 400);
        }

        $engine = trim((string) $request->request->get('engine', ''));
        $engineModel = trim((string) $request->request->get('engineModel', ''));
        $name = trim((string) $request->request->get('name', ''));

        if (!$this->isValidEngineSelection($engine, $engineModel)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid engine or model type.'], 400);
        }

        $originalFilename = (string) $uploadedFile->getClientOriginalName();
        if (!$this->isAllowedExtension($engine, $originalFilename)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid pretrained model file extension for the selected engine.'], 400);
        }

        $fileEntity = new File();
        $fileEntity->setFile($uploadedFile);

        $pretrained = new ModelPretrained();
        $pretrained->setModel($model);
        $pretrained->setName($name !== '' ? $name : $originalFilename);
        $pretrained->setOriginalFilename($originalFilename);
        $pretrained->setEngine($engine);
        $pretrained->setEngineModel($engineModel);
        $pretrained->setFile($fileEntity);

        $em = $this->doctrine->getManagerForClass(ModelPretrained::class);
        $em->persist($fileEntity);
        $em->persist($pretrained);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'id' => $pretrained->getId(),
            'label' => $pretrained->getDisplayLabel(),
        ]);
    }

    #[Route(
        path: '/model/pretrained/{id}/delete',
        name: 'syntetiq_model_model_pretrained_delete',
        requirements: ['id' => '\d+'],
        methods: ['DELETE']
    )]
    #[AclAncestor('syntetiq_model_model_edit')]
    #[CsrfProtection]
    public function deleteAction(ModelPretrained $pretrained): JsonResponse
    {
        $em = $this->doctrine->getManagerForClass(ModelPretrained::class);
        $em->remove($pretrained);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    private function isValidEngineSelection(string $engine, string $engineModel): bool
    {
        if (!isset($this->dataEngines[$engine])) {
            return false;
        }

        return in_array($engineModel, $this->dataEngines[$engine]['models'] ?? [], true);
    }

    private function isAllowedExtension(string $engine, string $originalFilename): bool
    {
        $extension = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));

        return match ($engine) {
            'ultralytics', 'test' => $extension === 'pt',
            'pytorch_ssd_jetson' => $extension === 'pth',
            default => in_array($extension, ['pt', 'pth'], true),
        };
    }
}
