<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\InventoryService;
use App\Support\Logger;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed.', 405);
    }

    $service = new InventoryService();
    $item = $service->add(request_data());

    Response::success([
        'item' => $item,
    ], 'Inventory item saved successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to add inventory item.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to save the inventory item right now.', 500);
}
