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

    $assetId = (int) (request_data()['id'] ?? 0);

    if ($assetId <= 0) {
        Response::error('A valid asset id is required.', 422, ['id' => 'Asset id is required.']);
    }

    $service = new AssetService();
    $service->delete($assetId);

    Response::success([], 'Asset deleted successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to delete asset.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to delete the asset right now.', 500);
}
