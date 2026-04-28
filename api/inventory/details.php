<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\InventoryService;
use App\Support\Logger;

try {
    $service = new InventoryService();
    $itemId = (int) ($_GET['inventory_item_id'] ?? $_GET['id'] ?? 0);

    Response::success($service->details($itemId), 'Inventory details loaded successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to load inventory details.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to load inventory details at the moment.', 500);
}
