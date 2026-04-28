<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use PDO;

final class OfficerService
{
    public const DIVISIONS = [
        'ORD',
        'FAD',
        'PDIPBD',
        'PFPD',
        'PMED',
        'DRD',
        'COA',
    ];

    public const DIVISION_LABELS = [
        'ORD' => 'ORD (Office of the Regional Director)',
        'FAD' => 'FAD (Finance and Administrative Division)',
        'PDIPBD' => 'PDIPBD (Project Development, Investment Programming, and Budgeting Division)',
        'PFPD' => 'PFPD (Policy Formulation and Planning Division)',
        'PMED' => 'PMED (Project Monitoring and Evaluation Division)',
        'DRD' => 'DRD (Development Research Division)',
        'COA' => 'COA (Commission on Audit)',
    ];

    private PDO $db;
    private LookupService $lookupService;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::connection();
        $this->lookupService = new LookupService($this->db);
    }

    public function listAll(): array
    {
        return $this->listFiltered();
    }

    public function listFiltered(array $filters = []): array
    {
        $name = trim((string) ($filters['name'] ?? ''));
        $division = trim((string) ($filters['division'] ?? ''));
        $where = [];
        $params = [];

        if ($name !== '') {
            $where[] = 'ao.name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        if ($division !== '') {
            $this->assertDivision($division);
            $where[] = 'd.code = :division';
            $params['division'] = $division;
        }

        $statement = $this->db->prepare(
            'SELECT
                ao.officer_id,
                ao.officer_code,
                ao.name,
                ao.position,
                ao.unit,
                d.code AS division,
                d.label AS division_label,
                ao.created_at,
                ao.updated_at
             FROM accountable_officers ao
             INNER JOIN divisions d ON d.division_id = ao.division_id' .
             ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where)) .
             ' ORDER BY ao.created_at DESC, ao.officer_id DESC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function filterByDivision(string $division): array
    {
        $division = trim($division);

        if ($division === '') {
            throw new ValidationException('Division is required.', [
                'division' => 'Choose a division from the list.',
            ]);
        }

        $this->assertDivision($division);

        return $this->listFiltered([
            'division' => $division,
        ]);
    }

    public function findById(int $officerId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                ao.officer_id,
                ao.officer_code,
                ao.name,
                ao.position,
                ao.unit,
                d.code AS division,
                d.label AS division_label,
                ao.created_at,
                ao.updated_at
             FROM accountable_officers ao
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE ao.officer_id = :officer_id
             LIMIT 1'
        );
        $statement->execute(['officer_id' => $officerId]);

        $officer = $statement->fetch();

        return $officer ?: null;
    }

    public function findByName(string $name): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                ao.officer_id,
                ao.officer_code,
                ao.name,
                ao.position,
                ao.unit,
                d.code AS division,
                d.label AS division_label,
                ao.created_at,
                ao.updated_at
             FROM accountable_officers ao
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE ao.name = :name
             LIMIT 1'
        );
        $statement->execute([
            'name' => trim($name),
        ]);

        $officer = $statement->fetch();

        return $officer ?: null;
    }

    public function findByNameAndDivision(string $name, string $division): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                ao.officer_id,
                ao.officer_code,
                ao.name,
                ao.position,
                ao.unit,
                d.code AS division,
                d.label AS division_label,
                ao.created_at,
                ao.updated_at
             FROM accountable_officers ao
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE ao.name = :name
               AND d.code = :division
             LIMIT 1'
        );
        $statement->execute([
            'name' => trim($name),
            'division' => trim($division),
        ]);

        $officer = $statement->fetch();

        return $officer ?: null;
    }

    public function findOrCreate(string $name, string $division, string $position = '', string $unit = ''): array
    {
        $existing = $this->findByNameAndDivision($name, $division);

        if ($existing !== null) {
            return $this->updateProfileIfNeeded($existing, $position, $unit);
        }

        return $this->create([
            'name' => $name,
            'position' => $position,
            'unit' => $unit,
            'division' => $division,
        ], false);
    }

    public function create(array $payload, bool $requireProfile = true): array
    {
        [$data, $errors] = $this->validateOfficerPayload($payload, $requireProfile);

        if ($errors !== []) {
            throw new ValidationException('Please complete the officer form.', $errors);
        }

        $existing = $this->findByNameAndDivision($data['name'], $data['division']);

        if ($existing !== null) {
            return $this->updateProfileIfNeeded($existing, $data['position'], $data['unit']);
        }

        $divisionId = $this->lookupService->findDivisionIdByCode($data['division']);

        if ($divisionId === null) {
            throw new ValidationException('Choose a division from the list.', [
                'division' => 'Choose a division from the list.',
            ]);
        }

        $statement = $this->db->prepare(
            'INSERT INTO accountable_officers (officer_code, name, position, unit, division_id)
             VALUES (:officer_code, :name, :position, :unit, :division_id)'
        );
        $statement->execute([
            'officer_code' => $this->nextOfficerCode($data['division']),
            'name' => $data['name'],
            'position' => $data['position'],
            'unit' => $data['unit'],
            'division_id' => $divisionId,
        ]);

        return $this->findById((int) $this->db->lastInsertId()) ?? [];
    }

    public function update(int $officerId, array $payload): array
    {
        $existing = $this->findById($officerId);

        if ($existing === null) {
            throw new ValidationException('The selected officer could not be found.', [
                'officer_id' => 'Choose a valid accountable officer.',
            ]);
        }

        [$data, $errors] = $this->validateOfficerPayload($payload, true);

        if ($errors !== []) {
            throw new ValidationException('Please complete the officer form.', $errors);
        }

        $duplicate = $this->findByNameAndDivision($data['name'], $data['division']);

        if ($duplicate !== null && (int) $duplicate['officer_id'] !== $officerId) {
            throw new ValidationException('An officer with the same name is already registered in that division.', [
                'name' => 'Use a unique officer name for the selected division.',
                'division' => 'Use a unique officer name for the selected division.',
            ]);
        }

        $divisionId = $this->lookupService->findDivisionIdByCode($data['division']);

        if ($divisionId === null) {
            throw new ValidationException('Choose a division from the list.', [
                'division' => 'Choose a division from the list.',
            ]);
        }

        if (!$this->hasOfficerChanges($existing, $data)) {
            throw new ValidationException('No changes were made to this officer.', [
                'officer_id' => 'Update at least one field before saving.',
            ]);
        }

        $officerCode = (string) ($existing['officer_code'] ?? '');
        if (trim((string) ($existing['division'] ?? '')) !== $data['division'] || trim($officerCode) === '') {
            $officerCode = $this->nextOfficerCode($data['division'], $officerId);
        }

        $statement = $this->db->prepare(
            'UPDATE accountable_officers
             SET officer_code = :officer_code,
                 name = :name,
                 position = :position,
                 unit = :unit,
                 division_id = :division_id
             WHERE officer_id = :officer_id'
        );
        $statement->execute([
            'officer_code' => $officerCode,
            'name' => $data['name'],
            'position' => $data['position'],
            'unit' => $data['unit'],
            'division_id' => $divisionId,
            'officer_id' => $officerId,
        ]);

        return $this->findById($officerId) ?? [];
    }

    public function delete(int $officerId): void
    {
        $existing = $this->findById($officerId);

        if ($existing === null) {
            throw new ValidationException('The selected officer could not be found.', [
                'officer_id' => 'Choose a valid accountable officer.',
            ]);
        }

        $usage = $this->usageSummary($officerId);

        if ((int) $usage['par_count'] > 0 || (int) $usage['asset_count'] > 0) {
            throw new ValidationException('This officer cannot be deleted because they are already assigned to saved assets.', [
                'officer_id' => 'Remove or reassign the related assets first before deleting this officer.',
            ]);
        }

        $statement = $this->db->prepare('DELETE FROM accountable_officers WHERE officer_id = :officer_id');
        $statement->execute([
            'officer_id' => $officerId,
        ]);
    }

    public function usageSummary(int $officerId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                COUNT(DISTINCT p.par_id) AS par_count,
                COUNT(a.id) AS asset_count
             FROM accountable_officers ao
             LEFT JOIN par p ON p.accountable_officer_id = ao.officer_id
             LEFT JOIN assets a ON a.par_id = p.par_id
             WHERE ao.officer_id = :officer_id'
        );
        $statement->execute([
            'officer_id' => $officerId,
        ]);

        $usage = $statement->fetch() ?: [
            'par_count' => 0,
            'asset_count' => 0,
        ];

        return [
            'par_count' => (int) ($usage['par_count'] ?? 0),
            'asset_count' => (int) ($usage['asset_count'] ?? 0),
        ];
    }

    public function getDivisionLabels(): array
    {
        $codes = $this->lookupService->divisionCodes();

        if ($codes === []) {
            $codes = self::DIVISIONS;
        }

        $choices = [];

        foreach ($codes as $code) {
            $normalized = trim((string) $code);

            if ($normalized === '') {
                continue;
            }

            $choices[$normalized] = $normalized;
        }

        return $choices;
    }

    public function getEditableDivisionLabels(): array
    {
        $storedCodes = $this->lookupService->divisionCodes();

        if ($storedCodes === []) {
            $storedCodes = self::DIVISIONS;
        }

        $choices = [];

        foreach ($storedCodes as $code) {
            $normalized = trim((string) $code);

            if ($normalized === '' || !in_array($normalized, self::DIVISIONS, true)) {
                continue;
            }

            $choices[$normalized] = $normalized;
        }

        if ($choices === []) {
            foreach (self::DIVISIONS as $code) {
                $choices[$code] = $code;
            }
        }

        return $choices;
    }

    public function getEditableDivisionDescriptions(): array
    {
        $storedLabels = $this->lookupService->divisionLabels();
        $descriptions = [];

        foreach ($this->getEditableDivisionLabels() as $code => $label) {
            $rawLabel = trim((string) ($storedLabels[$code] ?? (self::DIVISION_LABELS[$code] ?? $code)));
            $description = $rawLabel;

            if (preg_match('/^' . preg_quote($code, '/') . '\s*\((.+)\)$/i', $rawLabel, $matches) === 1) {
                $description = trim((string) $matches[1]);
            }

            $descriptions[$code] = $description !== '' ? $description : $code;
        }

        return $descriptions;
    }

    public function getDivisionCodes(): array
    {
        return $this->lookupService->divisionCodes();
    }

    public function isValidDivisionCode(string $division): bool
    {
        $division = trim($division);

        if ($division === '') {
            return false;
        }

        return in_array($division, $this->lookupService->divisionCodes(), true);
    }

    private function updateProfileIfNeeded(array $officer, string $position, string $unit): array
    {
        $position = trim($position);
        $division = trim((string) ($officer['division'] ?? ''));
        $unit = $this->normalizeUnit($division, $unit);

        if ($position === '' && $unit === '') {
            return $officer;
        }

        $currentPosition = trim((string) ($officer['position'] ?? ''));
        $currentUnit = trim((string) ($officer['unit'] ?? ''));

        if ($currentPosition === $position && $currentUnit === $unit) {
            return $officer;
        }

        $statement = $this->db->prepare(
            'UPDATE accountable_officers
             SET position = :position,
                 unit = :unit
             WHERE officer_id = :officer_id'
        );
        $statement->execute([
            'position' => $position !== '' ? $position : $currentPosition,
            'unit' => $unit !== '' ? $unit : $currentUnit,
            'officer_id' => (int) $officer['officer_id'],
        ]);

        return $this->findById((int) $officer['officer_id']) ?? $officer;
    }

    private function hasOfficerChanges(array $existing, array $data): bool
    {
        return trim((string) ($existing['name'] ?? '')) !== $data['name']
            || trim((string) ($existing['position'] ?? '')) !== $data['position']
            || trim((string) ($existing['division'] ?? '')) !== $data['division']
            || $this->normalizeUnit((string) ($existing['division'] ?? ''), (string) ($existing['unit'] ?? '')) !== $data['unit'];
    }

    private function assertDivision(string $division): void
    {
        if (!$this->isValidDivisionCode($division)) {
            throw new ValidationException('Choose a division from the list.', [
                'division' => 'Choose a division from the list.',
            ]);
        }
    }

    private function validateOfficerPayload(array $payload, bool $requireProfile): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $position = trim((string) ($payload['position'] ?? ''));
        $division = trim((string) ($payload['division'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Officer name is required.';
        }

        if ($division === '') {
            $errors['division'] = 'Division is required.';
        } elseif (!$this->isValidDivisionCode($division)) {
            $errors['division'] = 'Choose a division from the list.';
        }

        if ($requireProfile && $position === '') {
            $errors['position'] = 'Position is required.';
        }

        $unit = $this->normalizeUnit($division, (string) ($payload['unit'] ?? ''));

        if ($requireProfile && $this->requiresUnit($division) && $unit === '') {
            $errors['unit'] = 'Unit is required.';
        }

        return [[
            'name' => $name,
            'position' => $position,
            'unit' => $unit,
            'division' => $division,
        ], $errors];
    }

    private function requiresUnit(string $division): bool
    {
        return strtoupper(trim($division)) === 'FAD';
    }

    private function normalizeUnit(string $division, string $unit): string
    {
        $normalizedUnit = trim($unit);

        if (!$this->requiresUnit($division)) {
            return '';
        }

        return $normalizedUnit;
    }

    private function nextOfficerCode(string $division, ?int $excludeOfficerId = null): string
    {
        $statement = $this->db->prepare(
            'SELECT ao.officer_code
             FROM accountable_officers ao
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE d.code = :division' . ($excludeOfficerId !== null ? ' AND ao.officer_id <> :officer_id' : '') . '
             ORDER BY ao.officer_id ASC'
        );

        $params = [
            'division' => $division,
        ];

        if ($excludeOfficerId !== null) {
            $params['officer_id'] = $excludeOfficerId;
        }

        $statement->execute($params);

        $maxSequence = 0;
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $officerCode) {
            if (preg_match('/_(\d+)$/', (string) $officerCode, $matches) !== 1) {
                continue;
            }

            $maxSequence = max($maxSequence, (int) $matches[1]);
        }

        return sprintf('AO_%s_%02d', strtoupper($division), $maxSequence + 1);
    }
}
