<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class LookupService
{
    public const DEFAULT_CLASSIFICATIONS = [
        ['code' => 'PPE', 'label' => 'PPE', 'sort_order' => 1],
        ['code' => 'SEMI', 'label' => 'Semi-Expendable', 'sort_order' => 2],
    ];

    public const DEFAULT_FUNDING_SOURCES = [
        ['name' => 'DEPDev', 'sort_order' => 1],
        ['name' => 'RDC', 'sort_order' => 2],
    ];

    public const DEFAULT_DIVISIONS = [
        ['code' => 'ORD', 'label' => 'ORD (Office of the Regional Director)', 'sort_order' => 1],
        ['code' => 'FAD', 'label' => 'FAD (Finance and Administrative Division)', 'sort_order' => 2],
        ['code' => 'PDIPBD', 'label' => 'PDIPBD (Project Development, Investment Programming, and Budgeting Division)', 'sort_order' => 3],
        ['code' => 'PFPD', 'label' => 'PFPD (Policy Formulation and Planning Division)', 'sort_order' => 4],
        ['code' => 'PMED', 'label' => 'PMED (Project Monitoring and Evaluation Division)', 'sort_order' => 5],
        ['code' => 'DRD', 'label' => 'DRD (Development Research Division)', 'sort_order' => 6],
        ['code' => 'COA', 'label' => 'COA (Commission on Audit)', 'sort_order' => 7],
    ];

    private PDO $db;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::connection();
    }

    public function listClassifications(): array
    {
        $statement = $this->db->query(
            'SELECT classification_id, code, label, sort_order
             FROM classifications
             ORDER BY sort_order ASC, code ASC'
        );

        return $statement->fetchAll();
    }

    public function classificationCodes(): array
    {
        $rows = $this->listClassifications();

        if ($rows === []) {
            return array_column(self::DEFAULT_CLASSIFICATIONS, 'code');
        }

        return array_map(static fn (array $row): string => (string) $row['code'], $rows);
    }

    public function findClassificationIdByCode(string $code): ?int
    {
        $statement = $this->db->prepare(
            'SELECT classification_id
             FROM classifications
             WHERE UPPER(code) = UPPER(:code)
             LIMIT 1'
        );
        $statement->execute([
            'code' => trim($code),
        ]);

        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function listFundingSources(): array
    {
        $statement = $this->db->query(
            'SELECT funding_source_id, name, sort_order
             FROM funding_sources
             ORDER BY sort_order ASC, name ASC'
        );

        return $statement->fetchAll();
    }

    public function fundingSourceNames(): array
    {
        $rows = $this->listFundingSources();

        if ($rows === []) {
            return array_column(self::DEFAULT_FUNDING_SOURCES, 'name');
        }

        return array_map(static fn (array $row): string => (string) $row['name'], $rows);
    }

    public function findFundingSourceIdByName(string $name): ?int
    {
        $name = $this->normalizeFundingSourceName($name);
        $statement = $this->db->prepare(
            'SELECT funding_source_id
             FROM funding_sources
             WHERE name = :name
             LIMIT 1'
        );
        $statement->execute([
            'name' => $name,
        ]);

        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function listDivisions(): array
    {
        $statement = $this->db->query(
            'SELECT division_id, code, label, sort_order
             FROM divisions
             ORDER BY sort_order ASC, code ASC'
        );

        return $statement->fetchAll();
    }

    public function divisionCodes(): array
    {
        $rows = $this->listDivisions();

        if ($rows === []) {
            return array_column(self::DEFAULT_DIVISIONS, 'code');
        }

        return array_map(static fn (array $row): string => (string) $row['code'], $rows);
    }

    public function divisionLabels(): array
    {
        $rows = $this->listDivisions();

        if ($rows === []) {
            $labels = [];

            foreach (self::DEFAULT_DIVISIONS as $division) {
                $labels[$division['code']] = $division['label'];
            }

            return $labels;
        }

        $labels = [];

        foreach ($rows as $row) {
            $labels[(string) $row['code']] = (string) $row['label'];
        }

        return $labels;
    }

    public function findDivisionIdByCode(string $code): ?int
    {
        $statement = $this->db->prepare(
            'SELECT division_id
             FROM divisions
             WHERE code = :code
             LIMIT 1'
        );
        $statement->execute([
            'code' => trim($code),
        ]);

        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function normalizeFundingSourceName(string $name): string
    {
        $normalized = trim($name);

        return match ($normalized) {
            'DEPDev' => 'DEPDev',
            'DEPDev IX' => 'DEPDev',
            'RDC (Regional Development Council)' => 'RDC',
            default => $normalized,
        };
    }
}
