<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\OfficerService;
use App\Support\Logger;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed.', 405);
    }

    $payload = request_data();
    $officerId = (int) ($payload['officer_id'] ?? $payload['id'] ?? 0);

    if ($officerId <= 0) {
        Response::error('A valid officer id is required.', 422, ['officer_id' => 'Officer id is required.']);
    }

    $service = new OfficerService();
    $service->delete($officerId);

    Response::success([], 'Officer deleted successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to delete officer.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to delete the officer right now.', 500);
}
