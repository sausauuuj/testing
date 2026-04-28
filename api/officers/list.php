<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\OfficerService;
use App\Support\Logger;

try {
    $service = new OfficerService();
    $filters = request_data();

    Response::success([
        'officers' => $service->listFiltered($filters),
    ], 'Officers loaded successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to load officers.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to load officers at the moment.', 500);
}
