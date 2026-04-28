<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

$connection = Database::connection();

$typeCodes = [
    'Computer Software' => 'CS',
    'Fixed Asset' => 'FA',
    'Furniture and Fixtures' => 'FF',
    'ICT Equipment' => 'ICT',
    'Medicine Inventory' => 'MI',
    'Motor Vehicle' => 'MV',
    'Office Equipment' => 'OE',
];

$buildTypeCode = static function (string $propertyType) use ($typeCodes): string {
    if (isset($typeCodes[$propertyType])) {
        return $typeCodes[$propertyType];
    }

    $words = preg_split('/[^A-Z0-9]+/', strtoupper($propertyType), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $acronym = implode('', array_map(
        static fn (string $word): string => substr($word, 0, 1),
        array_slice($words, 0, 3)
    ));

    return $acronym !== '' ? $acronym : 'GEN';
};

$buildSingleWordNameCode = static function (string $word): string {
    $characters = str_split(preg_replace('/[^A-Z0-9]/', '', strtoupper($word)) ?: '');

    if ($characters === []) {
        return 'IT';
    }

    $code = array_shift($characters);

    foreach ($characters as $character) {
        if (!in_array($character, ['A', 'E', 'I', 'O', 'U'], true)) {
            $code .= $character;
            break;
        }
    }

    if (strlen($code) < 2 && isset($characters[0])) {
        $code .= $characters[0];
    }

    return str_pad(substr($code, 0, 2), 2, 'X');
};

$buildNameCode = static function (string $propertyName) use ($buildSingleWordNameCode): string {
    $words = preg_split('/[^A-Z0-9]+/', strtoupper($propertyName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    if (count($words) >= 2) {
        return substr($words[0], 0, 1) . substr($words[1], 0, 1);
    }

    if ($words !== []) {
        return $buildSingleWordNameCode($words[0]);
    }

    return 'IT';
};

$buildPropertyPrefix = static function (string $classification, string $propertyType, string $propertyName) use ($buildTypeCode, $buildNameCode): string {
    $typeCode = $buildTypeCode($propertyType);

    if (strtoupper(trim($classification)) === 'PPE') {
        return $typeCode . '-' . $buildNameCode($propertyName);
    }

    return 'SEMI-' . $typeCode;
};

$assets = $connection->query(
    "SELECT
        a.id,
        a.property_name,
        a.property_type,
        c.code AS classification,
        a.bulk_reference
     FROM assets a
     INNER JOIN classifications c ON c.classification_id = a.classification_id
     ORDER BY a.id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$updateStatement = $connection->prepare(
    'UPDATE assets
     SET property_number = :property_number
     WHERE id = :id'
);

$updatedRows = 0;
$prefixSequences = [];
$semiBatchNumbers = [];

try {
    $connection->beginTransaction();

    foreach ($assets as $asset) {
        $classification = strtoupper(trim((string) ($asset['classification'] ?? '')));
        $prefix = $buildPropertyPrefix(
            $classification,
            (string) ($asset['property_type'] ?? ''),
            (string) ($asset['property_name'] ?? '')
        );

        if ($classification === 'SEMI') {
            $batchReference = trim((string) ($asset['bulk_reference'] ?? ''));
            $batchKey = $prefix . '|' . ($batchReference !== '' ? $batchReference : 'ROW-' . (int) $asset['id']);

            if (!isset($semiBatchNumbers[$batchKey])) {
                $prefixSequences[$prefix] = ((int) ($prefixSequences[$prefix] ?? 0)) + 1;
                $semiBatchNumbers[$batchKey] = sprintf('%s-%03d', $prefix, $prefixSequences[$prefix]);
            }

            $propertyNumber = $semiBatchNumbers[$batchKey];
        } else {
            $prefixSequences[$prefix] = ((int) ($prefixSequences[$prefix] ?? 0)) + 1;
            $propertyNumber = sprintf('%s-%03d', $prefix, $prefixSequences[$prefix]);
        }

        $updateStatement->execute([
            'property_number' => $propertyNumber,
            'id' => (int) $asset['id'],
        ]);

        $updatedRows++;
        echo sprintf("Asset #%d => %s\n", (int) $asset['id'], $propertyNumber);
    }

    $connection->commit();
    echo sprintf("Updated %d asset row(s).\n", $updatedRows);
} catch (Throwable $throwable) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    fwrite(STDERR, 'Backfill failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
