<?php

namespace SyntetiQ\Bundle\ModelBundle\Service;

use Symfony\Component\Routing\RouterInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;

class DatasetQaReportViewBuilder
{
    private const HEARTBEAT_STALE_SECONDS = 120;
    private const BUILD_STATE_SYNC_DEFINITIONS = [
        ['localPath' => 'dataset_qa.status', 'storagePath' => 'dataset_qa.status'],
        ['localPath' => 'dataset_qa.started_at', 'storagePath' => 'dataset_qa.started_at'],
        ['localPath' => 'dataset_qa.finished_at', 'storagePath' => 'dataset_qa.finished_at'],
        ['localPath' => 'dataset_qa.heartbeat_at', 'storagePath' => 'dataset_qa.heartbeat_at'],
        ['localPath' => 'dataset_qa/progress.json', 'storagePath' => 'progress.json'],
    ];
    private const RUNTIME_FILE_DEFINITIONS = [
        ['path' => 'dataset-qa-init.log', 'label' => 'Init Log', 'description' => 'Dependency/bootstrap stdout from the dataset QA runner.'],
        ['path' => 'dataset-qa-init.err', 'label' => 'Init Errors', 'description' => 'Dependency/bootstrap stderr from the dataset QA runner.'],
        ['path' => 'dataset-qa-run.log', 'label' => 'Run Log', 'description' => 'Main dataset QA script stdout.'],
        ['path' => 'dataset-qa-run.err', 'label' => 'Run Errors', 'description' => 'Main dataset QA script stderr.'],
    ];
    private const SUMMARY_CARD_HELP_KEYS = [
        'total_score' => 'syntetiq.dataset.qa.help.summary.total_score',
        'images' => 'syntetiq.dataset.qa.help.summary.images',
        'objects' => 'syntetiq.dataset.qa.help.summary.objects',
        'classes' => 'syntetiq.dataset.qa.help.summary.classes',
        'annotated' => 'syntetiq.dataset.qa.help.summary.annotated',
        'unannotated' => 'syntetiq.dataset.qa.help.summary.unannotated',
    ];
    private const DEFAULT_SUB_SCORE_HELP_KEY = 'syntetiq.dataset.qa.help.sub_scores.default';
    private const SUB_SCORE_HELP_KEYS = [
        'class_balance' => 'syntetiq.dataset.qa.help.sub_scores.class_balance',
        'bbox_validity' => 'syntetiq.dataset.qa.help.sub_scores.bbox_validity',
        'consistency_proxy' => 'syntetiq.dataset.qa.help.sub_scores.consistency_proxy',
        'duplicates' => 'syntetiq.dataset.qa.help.sub_scores.duplicates',
        'split_health' => 'syntetiq.dataset.qa.help.sub_scores.split_health',
        'size_coverage' => 'syntetiq.dataset.qa.help.sub_scores.size_coverage',
    ];
    private const DEFAULT_SECTION_STATUS_HELP_KEY = 'syntetiq.dataset.qa.help.section_status.default';
    private const SECTION_STATUS_HELP_KEYS = [
        'dataset_info' => 'syntetiq.dataset.qa.help.section_status.dataset_info',
        'class_distribution' => 'syntetiq.dataset.qa.help.section_status.class_distribution',
        'bbox_statistics' => 'syntetiq.dataset.qa.help.section_status.bbox_statistics',
        'suspicious_annotations' => 'syntetiq.dataset.qa.help.section_status.suspicious_annotations',
        'duplicates' => 'syntetiq.dataset.qa.help.section_status.duplicates',
        'split_quality' => 'syntetiq.dataset.qa.help.section_status.split_quality',
        'image_difficulty' => 'syntetiq.dataset.qa.help.section_status.image_difficulty',
        'consistency' => 'syntetiq.dataset.qa.help.section_status.consistency',
        'quality_score' => 'syntetiq.dataset.qa.help.section_status.quality_score',
    ];
    private const ARTIFACT_HELP_KEYS = [
        'artifacts/class_histogram.png' => 'syntetiq.dataset.qa.help.artifacts.class_histogram',
        'artifacts/bbox_area_histogram.png' => 'syntetiq.dataset.qa.help.artifacts.bbox_area_histogram',
        'artifacts/bbox_aspect_ratio_histogram.png' => 'syntetiq.dataset.qa.help.artifacts.bbox_aspect_ratio_histogram',
        'artifacts/objects_per_image_histogram.png' => 'syntetiq.dataset.qa.help.artifacts.objects_per_image',
        'artifacts/size_buckets.png' => 'syntetiq.dataset.qa.help.artifacts.size_buckets',
        'artifacts/bbox_validation_flags.png' => 'syntetiq.dataset.qa.help.artifacts.validation_flags',
        'artifacts/difficulty_distribution.png' => 'syntetiq.dataset.qa.help.artifacts.difficulty',
        'artifacts/uniqueness_distribution.png' => 'syntetiq.dataset.qa.help.artifacts.uniqueness_distribution',
        'artifacts/localization_iou_histogram.png' => 'syntetiq.dataset.qa.help.artifacts.localization_iou',
    ];

    public function __construct(
        private RouterInterface $router,
        private DatasetQaStorageManager $datasetQaStorageManager
    ) {}

    public static function getRuntimeRelativePaths(): array
    {
        return array_map(
            static fn (array $definition): string => $definition['path'],
            self::RUNTIME_FILE_DEFINITIONS
        );
    }

    public static function getBuildStateSyncDefinitions(): array
    {
        return self::BUILD_STATE_SYNC_DEFINITIONS;
    }

    public function buildForModelBuild(ModelBuild $modelBuild): array
    {
        $storageDir = DatasetQaPaths::getBuildStorageDir($modelBuild);
        $status = $this->readBuildStoredStatus($storageDir)
            ?? $modelBuild->getDatasetQaStatus();
        $startedAt = $this->readBuildStoredDate($storageDir, 'dataset_qa.started_at')
            ?? $modelBuild->getDatasetQaStartedAt();
        $finishedAt = $this->readBuildStoredDate($storageDir, 'dataset_qa.finished_at')
            ?? $modelBuild->getDatasetQaFinishedAt();
        $heartbeatAt = $this->readBuildStoredDate($storageDir, 'dataset_qa.heartbeat_at')
            ?? $modelBuild->getDatasetQaHeartbeatAt();
        $progress = $modelBuild->getDatasetQaProgress();
        $progressMessage = $modelBuild->getDatasetQaProgressMessage();
        $progressPayload = $this->readStoredJson($storageDir, 'progress.json');
        if (is_array($progressPayload)) {
            if (array_key_exists('progress', $progressPayload) && $progressPayload['progress'] !== null) {
                $progress = max(0.0, min(1.0, (float) $progressPayload['progress']));
            }

            $payloadMessage = trim((string) ($progressPayload['message'] ?? ''));
            if ($payloadMessage !== '') {
                $progressMessage = substr($payloadMessage, 0, 255);
            }
        }

        if ($status === DatasetQaStatus::SUCCEEDED) {
            $progress = 1.0;
            $progressMessage = 'Dataset QA report is ready.';
        } elseif ($status === DatasetQaStatus::FAILED && !trim((string) $progressMessage)) {
            $progressMessage = 'Dataset QA failed.';
        }

        return $this->buildViewData(
            $status,
            $startedAt,
            $finishedAt,
            $heartbeatAt,
            $progress,
            $progressMessage,
            $modelBuild->getDatasetQaErrorOutput(),
            $storageDir,
            'syntetiq_model_model_build_dataset_qa_file',
            ['id' => $modelBuild->getId()]
        );
    }

    public function buildForDataSet(DataSet $dataSet): array
    {
        return $this->buildViewData(
            $dataSet->getDatasetQaStatus(),
            $dataSet->getDatasetQaStartedAt(),
            $dataSet->getDatasetQaFinishedAt(),
            $dataSet->getDatasetQaHeartbeatAt(),
            $dataSet->getDatasetQaProgress(),
            $dataSet->getDatasetQaProgressMessage(),
            $dataSet->getDatasetQaErrorOutput(),
            DatasetQaPaths::getDataSetStorageDir($dataSet),
            'syntetiq_model_data_set_dataset_qa_file',
            ['id' => $dataSet->getId()]
        );
    }

    private function buildViewData(
        string $status,
        ?\DateTimeInterface $startedAt,
        ?\DateTimeInterface $finishedAt,
        ?\DateTimeInterface $heartbeatAt,
        ?float $progress,
        ?string $progressMessage,
        ?string $errorOutput,
        string $reportDir,
        string $fileRoute,
        array $routeParams
    ): array {
        $status = $status ?: DatasetQaStatus::IDLE;
        $runtimeFiles = $this->buildRuntimeFiles($reportDir, $fileRoute, $routeParams);
        if ($status === DatasetQaStatus::QUEUED && !empty($runtimeFiles)) {
            $status = DatasetQaStatus::RUNNING;
        }

        $report = null;
        $reportContent = $this->datasetQaStorageManager->getFileContent($reportDir, 'report.json');
        if (null !== $reportContent) {
            $decoded = json_decode($reportContent, true);
            if (is_array($decoded)) {
                $report = $decoded;
            }
        }
        $canExposeReport = !in_array($status, [DatasetQaStatus::QUEUED, DatasetQaStatus::RUNNING], true);
        $visibleReport = $canExposeReport ? $report : null;
        $heartbeatStale = false;
        if (
            $heartbeatAt instanceof \DateTimeInterface
            && $status === DatasetQaStatus::RUNNING
            && ((new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->getTimestamp() - $heartbeatAt->getTimestamp()) > self::HEARTBEAT_STALE_SECONDS
        ) {
            $heartbeatStale = true;
        }

        return [
            'status' => $status,
            'startedAt' => $startedAt,
            'finishedAt' => $finishedAt,
            'heartbeatAt' => $heartbeatAt,
            'heartbeatStale' => $heartbeatStale,
            'progress' => $progress,
            'progressPercent' => null !== $progress ? max(0, min(100, (int) round($progress * 100))) : null,
            'progressMessage' => trim((string) $progressMessage) ?: null,
            'errorOutput' => trim((string) $errorOutput) ?: null,
            'hasReport' => null !== $visibleReport,
            'reportUrl' => $visibleReport ? $this->generateFileUrl($fileRoute, $routeParams, 'report.json', true) : null,
            'runtimeFiles' => $runtimeFiles,
            'summaryCards' => $this->buildSummaryCards($visibleReport),
            'subScores' => $this->buildSubScores($visibleReport),
            'sectionStatuses' => $this->buildSectionStatuses($visibleReport),
            'artifacts' => $this->buildFileEntries($visibleReport['artifacts'] ?? [], $fileRoute, $routeParams, self::ARTIFACT_HELP_KEYS),
            'reviewExports' => $this->buildFileEntries($visibleReport['review_exports_files'] ?? [], $fileRoute, $routeParams),
            'notes' => $this->buildNotes($visibleReport),
        ];
    }

    private function buildRuntimeFiles(string $reportDir, string $fileRoute, array $routeParams): array
    {
        $result = [];
        foreach (self::RUNTIME_FILE_DEFINITIONS as $definition) {
            $path = $definition['path'];
            if (!$this->datasetQaStorageManager->hasFile($reportDir, $path)) {
                continue;
            }

            $result[] = [
                'label' => $definition['label'],
                'description' => $definition['description'],
                'path' => $path,
                'url' => $this->generateFileUrl($fileRoute, $routeParams, $path, false),
                'downloadUrl' => $this->generateFileUrl($fileRoute, $routeParams, $path, true),
            ];
        }

        return $result;
    }

    private function readBuildStoredStatus(string $storageDir): ?string
    {
        $status = $this->readStoredFile($storageDir, 'dataset_qa.status');
        if ($status === null || !in_array($status, DatasetQaStatus::values(), true)) {
            return null;
        }

        return $status;
    }

    private function readBuildStoredDate(string $storageDir, string $fileName): ?\DateTimeInterface
    {
        $value = $this->readStoredFile($storageDir, $fileName);
        if ($value === null) {
            return null;
        }

        return $this->parseDate($value);
    }

    private function readStoredJson(string $storageDir, string $fileName): ?array
    {
        $content = $this->readStoredFile($storageDir, $fileName);
        if ($content === null) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function readStoredFile(string $storageDir, string $fileName): ?string
    {
        $content = $this->datasetQaStorageManager->getFileContent($storageDir, $fileName);
        if ($content === null) {
            return null;
        }

        $content = trim((string) $content);

        return $content === '' ? null : $content;
    }

    private function parseDate(string $value): ?\DateTimeInterface
    {
        try {
            return new \DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildSummaryCards(?array $report): array
    {
        if (!$report) {
            return [];
        }

        $datasetInfo = $report['dataset_info'] ?? [];
        $qualityScore = $report['quality_score'] ?? [];

        return array_values(array_filter([
            $this->createCard('total_score', 'Total Score', $qualityScore['total_score'] ?? null),
            $this->createCard('images', 'Images', $datasetInfo['total_images'] ?? null),
            $this->createCard('objects', 'Objects', $datasetInfo['total_objects'] ?? null),
            $this->createCard('classes', 'Classes', $datasetInfo['class_count'] ?? null),
            $this->createCard('annotated', 'Annotated', $datasetInfo['annotated_images'] ?? null),
            $this->createCard('unannotated', 'Unannotated', $datasetInfo['unannotated_images'] ?? null),
        ]));
    }

    private function createCard(string $helpKey, string $label, mixed $value): ?array
    {
        if (null === $value || $value === '') {
            return null;
        }

        return [
            'label' => $label,
            'value' => $this->formatDisplayValue($value),
            'help' => $this->buildHelp(self::SUMMARY_CARD_HELP_KEYS[$helpKey] ?? null),
        ];
    }

    private function buildSubScores(?array $report): array
    {
        $scores = $report['quality_score']['sub_scores'] ?? [];
        $notes = $report['quality_score']['notes'] ?? [];
        if (!is_array($scores)) {
            return [];
        }

        $result = [];
        foreach ($scores as $key => $value) {
            $key = (string) $key;
            $note = is_array($notes) ? trim((string) ($notes[$key] ?? '')) : '';
            $result[] = [
                'label' => ucwords(str_replace('_', ' ', $key)),
                'value' => is_numeric($value) ? $this->formatDisplayValue($value) : 'N/A',
                'help' => $this->buildHelp(self::SUB_SCORE_HELP_KEYS[$key] ?? self::DEFAULT_SUB_SCORE_HELP_KEY, $note ?: null),
            ];
        }

        return $result;
    }

    private function buildSectionStatuses(?array $report): array
    {
        $statuses = $report['section_statuses'] ?? [];
        if (!is_array($statuses)) {
            return [];
        }

        $result = [];
        foreach ($statuses as $name => $section) {
            if (!is_array($section)) {
                continue;
            }

            $name = (string) $name;
            $status = (string) ($section['status'] ?? 'unknown');
            $result[] = [
                'name' => ucwords(str_replace('_', ' ', $name)),
                'status' => $status,
                'message' => trim((string) ($section['message'] ?? '')) ?: null,
                'help' => $this->buildHelp(self::SECTION_STATUS_HELP_KEYS[$name] ?? self::DEFAULT_SECTION_STATUS_HELP_KEY, null, $status),
            ];
        }

        return $result;
    }

    private function buildFileEntries(array $entries, string $fileRoute, array $routeParams, array $helpKeys = []): array
    {
        $result = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $path = trim((string) ($entry['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $helpKey = $helpKeys[$path] ?? null;

            $result[] = [
                'label' => trim((string) ($entry['label'] ?? basename($path))) ?: basename($path),
                'description' => trim((string) ($entry['description'] ?? '')) ?: null,
                'path' => $path,
                'help' => $this->buildHelp($helpKey),
                'url' => $this->generateFileUrl($fileRoute, $routeParams, $path, false),
                'downloadUrl' => $this->generateFileUrl($fileRoute, $routeParams, $path, true),
            ];
        }

        return $result;
    }

    private function buildNotes(?array $report): array
    {
        $notes = $report['quality_score']['notes'] ?? [];
        if (!is_array($notes)) {
            return [];
        }

        $result = [];
        foreach ($notes as $name => $message) {
            $message = trim((string) $message);
            if ($message === '') {
                continue;
            }

            $result[] = [
                'label' => ucwords(str_replace('_', ' ', (string) $name)),
                'message' => $message,
            ];
        }

        return $result;
    }

    private function buildHelp(?string $translationKey = null, ?string $note = null, ?string $status = null): ?array
    {
        if ($translationKey === null && $note === null && $status === null) {
            return null;
        }

        $help = [];
        if ($translationKey !== null) {
            $help['translationKey'] = $translationKey;
        }

        if ($note !== null) {
            $help['note'] = $note;
        }

        if ($status !== null) {
            $help['status'] = $status;
        }

        return $help;
    }

    private function formatDisplayValue(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value) || is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) $value;
    }

    private function generateFileUrl(string $route, array $routeParams, string $relativePath, bool $download): string
    {
        return $this->router->generate($route, array_merge($routeParams, [
            'path' => $relativePath,
            'download' => $download ? 1 : 0,
        ]));
    }
}
