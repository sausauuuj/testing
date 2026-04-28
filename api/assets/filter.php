<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\AssetService;
use App\Support\Logger;

try {
    $service = new AssetService();
    $filters = request_data();

    Response::success([
        'assets' => $service->listFiltered($filters),
        'metrics' => $service->dashboardMetrics($filters),
        'filter_options' => $service->getFilterOptions(),
    ], 'Assets loaded successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to load filtered assets.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to load assets at the moment.', 500);
}
