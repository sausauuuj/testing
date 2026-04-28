<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Response;
use App\Core\ValidationException;
use App\Services\AssetService;
use App\Support\Logger;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed.', 405);
    }

    $service = new AssetService();
    $result = $service->addBulk(request_data());

    Response::success($result, 'Asset batch created successfully.');
} catch (ValidationException $exception) {
    Response::error($exception->getMessage(), 422, $exception->errors());
} catch (Throwable $exception) {
    Logger::error('Unable to add assets.', [
        'exception' => $exception->getMessage(),
    ]);

    Response::error('Unable to save the asset batch right now.', 500);
}
