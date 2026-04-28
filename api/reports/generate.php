<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\ReportService;
use App\Support\Logger;

try {
    $service = new ReportService();

    Response::success($service->generate(request_data()), 'Report generated successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to generate report.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to generate the report right now.', 500);
}
