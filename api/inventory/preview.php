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
        'preview' => $service->preview(request_data()),
    ], 'Inventory preview loaded successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to preview inventory identifiers.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to preview the inventory details right now.', 500);
}
