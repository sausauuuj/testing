<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\AssetService;
use App\Support\Logger;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed.', 405);
    }

    $payload = request_data();
    $assetId = (int) ($payload['id'] ?? 0);

    if ($assetId <= 0) {
        Response::error('A valid asset id is required.', 422, ['id' => 'Asset id is required.']);
    }

    $service = new AssetService();
    if (strtolower(trim((string) ($payload['update_scope'] ?? ''))) === 'par') {
        $result = $service->updateParBatch($assetId, $payload);

        Response::success([
            'asset' => $result['assets'][0] ?? null,
            'assets' => $result['assets'],
            'par' => $result['par'],
            'updated_count' => $result['updated_count'],
            'source_par_number' => $result['source_par_number'],
        ], 'PAR assets updated successfully.');
    }

    $asset = $service->update($assetId, $payload);

    Response::success(['asset' => $asset], 'Asset updated successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to update asset.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to update the asset right now.', 500);
}
