<?php

namespace SyntetiQ\Bundle\ModelBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Form\Type\ModelBuildType;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;
use SyntetiQ\Bundle\ModelBundle\Model\ModelBuildConstants;
use SyntetiQ\Bundle\ModelBundle\Provider\ModelBuildArtifactSyncMessageSender;
use SyntetiQ\Bundle\ModelBundle\Service\BuildRuntimeLogViewBuilder;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaStorageManager;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaReportViewBuilder;
use Gaufrette\StreamMode;

class ModelBuildController extends AbstractController
{
    public function __construct(
        private UpdateHandlerFacade $formUpdateHandler,
        private TranslatorInterface $translator,
        private FileManager $fileManager,
        private ManagerRegistry $doctrine,
        private BuildRuntimeLogViewBuilder $buildRuntimeLogViewBuilder,
        private DatasetQaReportViewBuilder $datasetQaReportViewBuilder,
        private DatasetQaStorageManager $datasetQaStorageManager,
        private ModelBuildArtifactSyncMessageSender $modelBuildArtifactSyncMessageSender
    ) {}

    #[Route(path: '/view/{id}', name: 'syntetiq_model_model_build_view', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQModel/ModelBuild/view.html.twig')]
    #[AclAncestor("syntetiq_model_model_build_view")]
    public function viewAction(ModelBuild $modelBuild)
    {
        $this->modelBuildArtifactSyncMessageSender->send($modelBuild);

        return [
            'entity' => $modelBuild,
            'tensorboardRunName' => ModelBuildConstants::getTensorBoardRunName($modelBuild),
            'tensorboardUrl' => ModelBuildConstants::getTensorBoardUrl($modelBuild),
            'buildLogs' => $this->buildRuntimeLogViewBuilder->buildForModelBuild($modelBuild),
            'datasetQa' => $this->datasetQaReportViewBuilder->buildForModelBuild($modelBuild),
        ];
    }

    #[Route('/view/{id}/dataset-qa/file', name: 'syntetiq_model_model_build_dataset_qa_file', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[AclAncestor('syntetiq_model_model_build_view')]
    public function datasetQaFileAction(Request $request, ModelBuild $modelBuild): StreamedResponse
    {
        $relativePath = trim((string) $request->query->get('path', ''));
        if ($relativePath === '') {
            throw new NotFoundHttpException();
        }

        $stream = $this->datasetQaStorageManager->getStream(DatasetQaPaths::getBuildStorageDir($modelBuild), $relativePath);
        if (null === $stream) {
            throw new NotFoundHttpException();
        }

        return $this->createStoredFileResponse($stream, $relativePath, $request->query->getBoolean('download'));
    }

    #[Route('/view/{id}/runtime-file', name: 'syntetiq_model_model_build_runtime_file', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[AclAncestor('syntetiq_model_model_build_view')]
    public function runtimeFileAction(Request $request, ModelBuild $modelBuild): StreamedResponse
    {
        $relativePath = trim((string) $request->query->get('path', ''));
        if ($relativePath === '') {
            throw new NotFoundHttpException();
        }

        $stream = $this->datasetQaStorageManager->getStream(DatasetQaPaths::getBuildLogsStorageDir($modelBuild), $relativePath);
        if (null === $stream) {
            throw new NotFoundHttpException();
        }

        return $this->createStoredFileResponse($stream, $relativePath, $request->query->getBoolean('download'));
    }

    /**
     * @param ModelBuild $modelBuild
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(ModelBuild $modelBuild, Request $request)
    {
        return $this->formUpdateHandler->update(
            $modelBuild,
            ModelBuildType::class,
            $this->translator->trans('syntetiq.controller.model_build.saved.message'),
            $request
        );
    }

    #[Route(path: '/delete/{id}', name: 'syntetiq_model_model_build_delete', requirements: ["id" => "\d+"], methods: ['DELETE'])]
    #[AclAncestor("syntetiq_model_model_build_create")]
    #[CsrfProtection]
    public function deleteAction(ModelBuild $modelBuild)
    {
        $em = $this->doctrine->getManager();
        $em->remove($modelBuild);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    #[Route(path: '/{modelId}/create', name: 'syntetiq_model_model_build_create', requirements: ['modelId' => '\d+'])]
    #[Template('@SyntetiQModel/ModelBuild/edit.html.twig')]
    #[AclAncestor("syntetiq_model_model_build_create")]
    public function createAction(Request $request, #[MapEntity(id: 'modelId')] Model $model)
    {
        $modelBuild = new ModelBuild();
        $modelBuild->setModel($model);

        return $this->update($modelBuild, $request);
    }

    private function createStoredFileResponse(\Gaufrette\Stream $stream, string $relativePath, bool $download): StreamedResponse
    {
        $extension = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));
        $inlineExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'txt', 'log', 'err', 'json', 'csv'];
        $disposition = ($download || !in_array($extension, $inlineExtensions, true))
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;
        $contentType = match ($extension) {
            'log', 'err', 'txt' => 'text/plain; charset=UTF-8',
            'json' => 'application/json',
            'csv' => 'text/csv; charset=UTF-8',
            default => MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? 'application/octet-stream',
        };

        $response = new StreamedResponse(static function () use ($stream) {
            $stream->open(new StreamMode('rb'));
            while (!$stream->eof()) {
                echo $stream->read(8192);
            }
        });
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition($disposition, basename($relativePath)));

        return $response;
    }
}
