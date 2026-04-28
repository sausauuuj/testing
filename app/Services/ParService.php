<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use PDO;

final class ParService
{
    public const DOCUMENT_TYPES = ['PAR', 'ICS'];

    private PDO $db;
    private OfficerService $officerService;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::connection();
        $this->officerService = new OfficerService($this->db);
    }

    public function getOrCreate(
        int $officerId,
        string $parDate,
        string $documentType = 'PAR',
        ?string $remarks = null,
        ?PDO $connection = null
    ): array {
        $db = $connection ?? $this->db;
        $documentType = $this->normalizeDocumentType($documentType);

        if ($this->officerService->findById($officerId) === null) {
            throw new ValidationException('The selected accountable officer does not exist.', [
                'officer_id' => 'Choose a valid accountable officer.',
            ]);
        }

        $existing = $this->findByOfficerAndDate($officerId, $parDate, $documentType, $db);

        if ($existing !== null) {
            $existing['created'] = false;
            return $existing;
        }

        $parNumber = $this->generateDocumentNumber($db, $parDate, $documentType);

        $statement = $db->prepare(
            'INSERT INTO par (par_number, accountable_officer_id, par_date, document_type, remarks)
             VALUES (:par_number, :accountable_officer_id, :par_date, :document_type, :remarks)'
        );
        $statement->execute([
            'par_number' => $parNumber,
            'accountable_officer_id' => $officerId,
            'par_date' => $parDate,
            'document_type' => $documentType,
            'remarks' => $remarks,
        ]);

        $created = $this->findById((int) $db->lastInsertId(), $db);
        $created['created'] = true;

        return $created;
    }

    public function cleanupIfUnused(int $parId, ?PDO $connection = null): void
    {
        $db = $connection ?? $this->db;

        $usageStatement = $db->prepare('SELECT COUNT(*) FROM assets WHERE par_id = :par_id');
        $usageStatement->execute(['par_id' => $parId]);

        if ((int) $usageStatement->fetchColumn() > 0) {
            return;
        }

        $deleteStatement = $db->prepare('DELETE FROM par WHERE par_id = :par_id');
        $deleteStatement->execute(['par_id' => $parId]);
    }

    private function findByOfficerAndDate(int $officerId, string $parDate, string $documentType, PDO $db): ?array
    {
        $statement = $db->prepare(
            'SELECT
                p.par_id,
                p.par_number,
                p.par_date,
                p.document_type,
                p.remarks,
                ao.name AS officer_name,
                d.code AS division,
                d.label AS division_label
             FROM par p
             INNER JOIN accountable_officers ao ON ao.officer_id = p.accountable_officer_id
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE p.accountable_officer_id = :accountable_officer_id
               AND p.par_date = :par_date
               AND p.document_type = :document_type
             LIMIT 1'
        );
        $statement->execute([
            'accountable_officer_id' => $officerId,
            'par_date' => $parDate,
            'document_type' => $documentType,
        ]);

        $par = $statement->fetch();

        return $par ?: null;
    }

    private function findById(int $parId, PDO $db): array
    {
        $statement = $db->prepare(
            'SELECT
                p.par_id,
                p.par_number,
                p.par_date,
                p.document_type,
                p.remarks,
                ao.name AS officer_name,
                d.code AS division,
                d.label AS division_label
             FROM par p
             INNER JOIN accountable_officers ao ON ao.officer_id = p.accountable_officer_id
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE p.par_id = :par_id
             LIMIT 1'
        );
        $statement->execute(['par_id' => $parId]);

        return $statement->fetch() ?: [];
    }

    private function generateDocumentNumber(PDO $db, string $parDate, string $documentType): string
    {
        $year = date('Y', strtotime($parDate));
        $prefix = $documentType . '-' . $year;

        $statement = $db->prepare(
            'SELECT par_number
             FROM par
             WHERE document_type = :document_type
               AND par_number LIKE :prefix
             ORDER BY par_number DESC
             LIMIT 1'
        );
        $statement->execute([
            'document_type' => $documentType,
            'prefix' => $prefix . '-%',
        ]);

        $lastNumber = (string) $statement->fetchColumn();
        $sequence = 1;

        if ($lastNumber !== '') {
            $parts = explode('-', $lastNumber);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%03d', $prefix, $sequence);
    }

    private function normalizeDocumentType(string $documentType): string
    {
        $normalized = strtoupper(trim($documentType));

        if (!in_array($normalized, self::DOCUMENT_TYPES, true)) {
            throw new ValidationException('Choose a valid document type.', [
                'document_type' => 'Choose a valid document type.',
            ]);
        }

        return $normalized;
    }
}
