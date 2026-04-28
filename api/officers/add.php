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

    $service = new OfficerService();
    $officer = $service->create(request_data());

    Response::success([
        'officer' => $officer,
    ], 'Officer added successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to create officer.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to save the officer right now.', 500);
}
