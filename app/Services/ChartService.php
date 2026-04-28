<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ChartService
{
    private PDO $db;
    private AssetService $assetService;
    private InventoryService $inventoryService;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::connection();
        $this->assetService = new AssetService($this->db);
        $this->inventoryService = new InventoryService($this->db);
    }

    public function getChartData(array $filters = []): array
    {
        $mode = strtolower(trim((string) ($filters['dashboard_filter'] ?? 'overview')));
        [$scopedFilters, $config] = $this->resolveModeConfig($filters, $mode);

        return [
            'metrics' => $this->assetService->dashboardMetrics($scopedFilters),
            'mode' => $config['label'],
            'pie' => $this->aggregateByField($scopedFilters, $config['pie_field'], false),
            'bar' => $this->aggregateByField($scopedFilters, $config['bar_field'], true),
            'inventory' => $this->inventoryService->dashboardSummary(),
        ];
    }

    private function resolveModeConfig(array $filters, string $mode): array
    {
        $normalized = $this->assetService->normalizeFilters($filters);
        $year = (int) ($filters['year'] ?? (int) date('Y'));
        $month = (int) ($filters['month'] ?? (int) date('n'));

        if ($year < 2020) {
            $year = (int) date('Y');
        }

        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $config = [
            'label' => 'Overview',
            'pie_field' => 'classification',
            'bar_field' => 'division',
        ];

        if ($mode === 'overview' || $mode === '') {
            return [$normalized, $config];
        }

        if ($mode === 'yearly') {
            $config['label'] = 'Yearly';
            $normalized['date_from'] = sprintf('%04d-01-01', $year);
            $normalized['date_to'] = sprintf('%04d-12-31', $year);
            return [$normalized, $config];
        }

        if ($mode === 'by-funding') {
            $config['label'] = 'By Funding';
            $config['pie_field'] = 'funding_source';
            $config['bar_field'] = 'funding_source';
            return [$normalized, $config];
        }

        if ($mode === 'by-classification') {
            $config['label'] = 'By Classification';
            $config['pie_field'] = 'classification';
            $config['bar_field'] = 'classification';
            return [$normalized, $config];
        }

        if ($mode === 'by-division') {
            $config['label'] = 'By Division';
            $config['pie_field'] = 'division';
            $config['bar_field'] = 'division';
            return [$normalized, $config];
        }

        $config['label'] = 'Monthly';
        $normalized['date_from'] = sprintf('%04d-%02d-01', $year, $month);
        $normalized['date_to'] = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month)));

        return [$normalized, $config];
    }

    private function aggregateByField(array $filters, string $field, bool $sumCosts): array
    {
        $allowedFields = ['property_type', 'funding_source', 'classification', 'division'];

        if (!in_array($field, $allowedFields, true)) {
            $field = 'property_type';
        }

        [$whereClause, $params] = $this->assetService->buildWhereClause($filters);
        $valueExpression = $sumCosts ? 'COALESCE(SUM(a.unit_cost), 0)' : 'COUNT(*)';
        $fieldExpression = match ($field) {
            'classification' => 'UPPER(c.code)',
            'funding_source' => 'fs.name',
            'division' => 'd.label',
            default => 'a.' . $field,
        };

        $statement = $this->db->prepare(
            sprintf(
                "SELECT COALESCE(NULLIF(TRIM(%1\$s), ''), 'Unspecified') AS label, %2\$s AS total
                 FROM assets a
                 INNER JOIN par p ON p.par_id = a.par_id
                 INNER JOIN accountable_officers ao ON ao.officer_id = p.accountable_officer_id
                 INNER JOIN divisions d ON d.division_id = ao.division_id
                 INNER JOIN funding_sources fs ON fs.funding_source_id = a.funding_source_id
                 INNER JOIN classifications c ON c.classification_id = a.classification_id%3\$s
                 GROUP BY %1\$s
                 ORDER BY total DESC, label ASC",
                $fieldExpression,
                $valueExpression,
                $whereClause
            )
        );
        $statement->execute($params);
        $rows = $statement->fetchAll();

        return [
            'labels' => array_map(static fn (array $row): string => (string) $row['label'], $rows),
            'values' => array_map(
                static fn (array $row) => $sumCosts ? (float) $row['total'] : (int) $row['total'],
                $rows
            ),
        ];
    }
}
