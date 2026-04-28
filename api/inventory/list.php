<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\InventoryService;
use App\Support\Logger;

try {
    $service = new InventoryService();

    Response::success([
        'items' => $service->listItems(request_data()),
        'summary' => $service->dashboardSummary(),
        'filter_options' => $service->getFilterOptions(),
    ], 'Inventory loaded successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to load inventory items.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to load inventory items at the moment.', 500);
}
