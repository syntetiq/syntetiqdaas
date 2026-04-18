<?php

namespace SyntetiQ\Bundle\ModelBundle\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaReportViewBuilder;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaStorageManager;

class DatasetQaReportViewBuilderTest extends TestCase
{
    /** @var RouterInterface|MockObject */
    private $router;

    /** @var DatasetQaStorageManager|MockObject */
    private $storageManager;

    private DatasetQaReportViewBuilder $builder;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->storageManager = $this->createMock(DatasetQaStorageManager::class);

        $this->builder = new DatasetQaReportViewBuilder(
            $this->router,
            $this->storageManager
        );
    }

    public function testBuildForDataSetIncludesTooltipHelpForVisibleBlocks(): void
    {
        $report = [
            'dataset_info' => [
                'total_images' => 120,
                'annotated_images' => 100,
                'unannotated_images' => 20,
                'total_objects' => 420,
                'class_count' => 5,
            ],
            'quality_score' => [
                'total_score' => 78.5,
                'sub_scores' => [
                    'class_balance' => 66.5,
                    'consistency_proxy' => 72.0,
                    'annotation_noise' => 41.25,
                ],
                'notes' => [
                    'consistency_proxy' => 'Uses same-class high-IoU overlaps and class-specific size/aspect outliers.',
                ],
            ],
            'section_statuses' => [
                'duplicates' => [
                    'status' => 'succeeded',
                    'message' => '',
                ],
                'custom_stage' => [
                    'status' => 'skipped',
                    'message' => 'Custom stage did not run.',
                ],
            ],
            'artifacts' => [
                [
                    'label' => 'Class Histogram',
                    'path' => 'artifacts/class_histogram.png',
                    'description' => 'Object count by class.',
                ],
            ],
        ];

        $dataSet = $this->createMock(DataSet::class);
        $dataSet->method('getId')->willReturn(42);
        $dataSet->method('getDatasetQaStatus')->willReturn(DatasetQaStatus::SUCCEEDED);
        $dataSet->method('getDatasetQaStartedAt')->willReturn(null);
        $dataSet->method('getDatasetQaFinishedAt')->willReturn(null);
        $dataSet->method('getDatasetQaHeartbeatAt')->willReturn(null);
        $dataSet->method('getDatasetQaProgress')->willReturn(null);
        $dataSet->method('getDatasetQaProgressMessage')->willReturn(null);
        $dataSet->method('getDatasetQaErrorOutput')->willReturn(null);

        $this->storageManager->expects($this->exactly(4))
            ->method('hasFile')
            ->willReturn(false);

        $this->storageManager->expects($this->once())
            ->method('getFileContent')
            ->with($this->isType('string'), 'report.json')
            ->willReturn(json_encode($report));

        $this->router->expects($this->exactly(3))
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $params): string {
                return sprintf(
                    '/dataset/%d/dataset-qa/%s?download=%d',
                    $params['id'],
                    $params['path'],
                    $params['download']
                );
            });

        $view = $this->builder->buildForDataSet($dataSet);

        self::assertSame('/dataset/42/dataset-qa/report.json?download=1', $view['reportUrl']);

        $summaryCards = [];
        foreach ($view['summaryCards'] as $item) {
            $summaryCards[$item['label']] = $item;
        }

        self::assertSame(
            'syntetiq.dataset.qa.help.summary.total_score',
            $summaryCards['Total Score']['help']['translationKey']
        );
        self::assertSame(
            'syntetiq.dataset.qa.help.summary.unannotated',
            $summaryCards['Unannotated']['help']['translationKey']
        );

        $subScores = [];
        foreach ($view['subScores'] as $item) {
            $subScores[$item['label']] = $item;
        }

        self::assertSame(
            'syntetiq.dataset.qa.help.sub_scores.class_balance',
            $subScores['Class Balance']['help']['translationKey']
        );
        self::assertSame(
            'Uses same-class high-IoU overlaps and class-specific size/aspect outliers.',
            $subScores['Consistency Proxy']['help']['note']
        );
        self::assertSame(
            'syntetiq.dataset.qa.help.sub_scores.default',
            $subScores['Annotation Noise']['help']['translationKey']
        );

        $sectionStatuses = [];
        foreach ($view['sectionStatuses'] as $item) {
            $sectionStatuses[$item['name']] = $item;
        }

        self::assertSame(
            'syntetiq.dataset.qa.help.section_status.duplicates',
            $sectionStatuses['Duplicates']['help']['translationKey']
        );
        self::assertSame(
            'succeeded',
            $sectionStatuses['Duplicates']['help']['status']
        );
        self::assertSame(
            'syntetiq.dataset.qa.help.section_status.default',
            $sectionStatuses['Custom Stage']['help']['translationKey']
        );
        self::assertSame('skipped', $sectionStatuses['Custom Stage']['help']['status']);

        self::assertSame(
            'syntetiq.dataset.qa.help.artifacts.class_histogram',
            $view['artifacts'][0]['help']['translationKey']
        );
    }
}
