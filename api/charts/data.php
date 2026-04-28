<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\ChartService;
use App\Support\Logger;

try {
    $service = new ChartService();

    Response::success($service->getChartData(request_data()), 'Chart data loaded successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to load chart data.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to load chart data at the moment.', 500);
}
