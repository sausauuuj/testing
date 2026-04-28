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
    $item = $service->update((int) ($payload['inventory_item_id'] ?? 0), $payload);

    Response::success([
        'item' => $item,
    ], 'Inventory item updated successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to update inventory item.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to update the inventory item right now.', 500);
}
