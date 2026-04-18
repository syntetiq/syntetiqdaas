<?php

namespace SyntetiQ\Bundle\DataSetBundle\Controller;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use Oro\Bundle\AttachmentBundle\Entity\File;
use SyntetiQ\Bundle\DataSetBundle\Service\DataSetItemImageManager;

class ItemImageUploadController extends AbstractController
{
    #[Route(
        path: '/{id}',
        name: 'syntetiq_data_set_import_images',
        requirements: ["id" => "\d+"],
        options: ["expose" => true],
    )]
    public function uploadImagesAction(Request $request, DataSet $dataSet): JsonResponse
    {
        $request->getSession()->save();

        /** @var UploadedFile[] $files */
        $files = $request->files->get('files', []);
        $tag = trim((string) $request->request->get('tag', ''));

        $count = 0;
        $items = [];
        $doctrineHelper = $this->container->get(DoctrineHelper::class);
        $dataSetItemImageManager = $this->container->get(DataSetItemImageManager::class);
        $em = $doctrineHelper->getManager();
        foreach ($files as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
                continue;
            }

            $fileEntity = new File();
            $fileEntity->setFile($uploadedFile);

            $item = new DataSetItem();
            $item->setDataSet($dataSet);
            $item->setImage($fileEntity);
            if ($tag !== '') {
                $item->setTag($tag);
            }

            $em->persist($fileEntity);
            $em->persist($item);
            $items[] = $item;
            $count++;
        }

        $em->flush();

        foreach ($items as $item) {
            $dataSetItemImageManager->syncImageSize($item);
            $em->persist($item);
        }

        if ($items) {
            $em->flush();
        }

        return new JsonResponse(['status' => 'success', 'count' => $count]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                DoctrineHelper::class,
                DataSetItemImageManager::class,
            ]
        );
    }
}
