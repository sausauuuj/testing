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
    $result = $service->batchStockOut(request_data());

    Response::success($result, 'Stock out request saved successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to save stock out batch.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to save the stock out request right now.', 500);
}
