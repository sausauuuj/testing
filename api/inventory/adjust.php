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

    $payload = request_data();
    $service = new InventoryService();
    $item = $service->adjustStock((int) ($payload['inventory_item_id'] ?? 0), $payload);

    Response::success([
        'item' => $item,
    ], 'Inventory stock updated successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to adjust inventory stock.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to update the stock right now.', 500);
}
