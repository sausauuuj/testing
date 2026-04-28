<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\OfficerService;
use App\Support\Logger;

try {
    $service = new OfficerService();
    $division = (string) ($_GET['division'] ?? '');

    $officers = $service->filterByDivision($division);

    Response::success([
        'officers' => $officers,
    ], 'Officers loaded successfully.');
    
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to filter officers.', [
        'division' => $division ?? 'none',
        'exception' => $exception->getMessage(),
    ]);
    Response::error('Unable to load officers at the moment.', 500);
}
?>

