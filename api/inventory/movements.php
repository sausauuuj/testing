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
        'movements' => $service->listAllMovements((string) ($_GET['movement_type'] ?? ''), 300),
    ], 'Inventory movements loaded successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to load inventory movements.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to load inventory movements at the moment.', 500);
}
