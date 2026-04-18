<?php

namespace SyntetiQ\Bundle\DataSetBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetExport;
use SyntetiQ\Bundle\DataSetBundle\Provider\ExportDataSetProvider;

class DataSetExportController extends AbstractController
{
    #[Route(path: '/delete/{id}', name: 'syntetiq_model_data_set_export_delete', requirements: ["id" => "\d+"], methods: ['DELETE'])]
    #[Acl(
        id: "syntetiq_model_data_set_export_delete",
        type: "entity",
        class: DataSetExport::class,
        permission: "DELETE"
    )]
    #[CsrfProtection]
    public function deleteAction(DataSetExport $dataSetExport)
    {
        $em = $this->container->get(ManagerRegistry::class)->getManager();
        $em->remove($dataSetExport);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    #[Route(path: '/queue/{id}', name: 'syntetiq_model_data_set_export_queue', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[Acl(
        id: "syntetiq_model_data_set_export_view",
        type: "entity",
        class: DataSetExport::class,
        permission: "VIEW"
    )]
    public function queueAction(Request $request, DataSet $dataSet): JsonResponse
    {
        $request->getSession()->save();

        $exportTags = $request->request->all('exportTags');
        if (!is_array($exportTags)) {
            $exportTags = [];
        }

        $this->container->get(ExportDataSetProvider::class)->sendMessage($dataSet, $exportTags);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Dataset export has been queued.',
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ExportDataSetProvider::class,
                ManagerRegistry::class,
            ]
        );
    }
}
