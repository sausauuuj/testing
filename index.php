<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Services\OfficerService;

$app = app_config('app');
$appCssVersion = is_file(__DIR__ . '/assets/css/app.css') ? (string) filemtime(__DIR__ . '/assets/css/app.css') : (string) time();
$appJsVersion = is_file(__DIR__ . '/assets/js/app.js') ? (string) filemtime(__DIR__ . '/assets/js/app.js') : (string) time();
$todayLabel = date('F j, Y g:i A');
$divisionCodes = (new OfficerService())->getDivisionCodes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape_html($app['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: '#1155A5', surface: '#ECEFF1', ink: '#0f172a' },
                    boxShadow: { panel: '0 10px 26px rgba(15, 23, 42, 0.08)' },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= escape_html($appCssVersion); ?>">
</head>
<body class="bg-surface text-ink antialiased">
    <div id="mobileOverlay" class="screen-only fixed inset-0 z-30 hidden bg-slate-950/45 backdrop-blur-sm lg:hidden"></div>

    <?php require __DIR__ . '/partials/header.php'; ?>

    <div class="app-shell">
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="app-main">
            <div id="globalNotice" class="screen-only hidden rounded-[1rem] border px-4 py-3 text-sm font-medium shadow-lg" role="alert" aria-live="polite"></div>
            <div id="moduleContainer" class="module-host">
                <?php require __DIR__ . '/modules/dashboard.php'; ?>
                <?php require __DIR__ . '/modules/registration.php'; ?>
                <?php require __DIR__ . '/modules/assets.php'; ?>
                <?php require __DIR__ . '/modules/inventory.php'; ?>
                <?php require __DIR__ . '/modules/reports.php'; ?>
            </div>
        </main>
    </div>

    <div id="detailsModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="asset-details-shell w-full max-w-[720px] max-h-[calc(100vh-2rem)] overflow-hidden rounded-[1.2rem] bg-white shadow-2xl">
            <div class="asset-details-head">
                <div>
                    <p class="panel-eyebrow">Asset Details</p>
                    <h3 id="detailsAssetName" class="panel-title">Asset</h3>
                    <p id="detailsAssetMeta" class="asset-details-meta"></p>
                </div>
                <button id="closeDetailsModal" type="button" class="rounded-full border border-slate-200 p-2 text-slate-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg></button>
            </div>
            <div id="detailsContent" class="max-h-[calc(100vh-10rem)] overflow-y-auto space-y-4 pr-1"></div>
            <div class="asset-details-actions flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 p-3">
                <button id="closeDetailsButton" type="button" class="action-secondary hidden">Back</button>
                <button id="detailsUpdateButton" type="button" class="action-primary">Update Asset</button>
                <button id="detailsDeleteButton" type="button" class="action-secondary">Delete Asset</button>
            </div>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="registration-modal-shell">
            <div class="registration-modal__head">
                <div><p class="panel-eyebrow">Asset Update</p><h3 id="editAssetName" class="registration-modal__title">Asset</h3><p id="editAssetMeta" class="registration-modal__copy"></p></div>
                <button id="closeEditModal" type="button" class="rounded-full border border-slate-200 p-2 text-slate-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg></button>
            </div>
            <form id="editAssetForm" class="registration-modal__form">
                <input type="hidden" name="id">
                <input type="hidden" name="update_scope" value="">

                <div class="grid grid-cols-2 gap-4">
                    <label class="form-group"><span class="form-label">Serial Number</span><input type="text" name="property_id" class="form-input" placeholder="Enter serial number" autocomplete="off"><span class="field-error hidden" data-error-for="property_id"></span></label>

                    <label class="form-group col-span-2"><span class="form-label">Property Name</span><input type="text" name="property_name" class="form-input" placeholder="Enter property name"><span class="field-error hidden" data-error-for="property_name"></span></label>

                    <label class="form-group"><span class="form-label">Property Type</span><select name="property_type" class="form-input searchable-select"><option value="">Select type</option></select><span class="field-error hidden" data-error-for="property_type"></span></label>

                    <label class="form-group"><span class="form-label">Unit Cost</span><input type="text" name="unit_cost" inputmode="decimal" class="form-input" placeholder="₱ 0"><span class="field-error hidden" data-error-for="unit_cost"></span></label>

                    <label class="form-group"><span class="form-label">Quantity</span><input type="number" min="1" name="quantity" class="form-input" value="1"><span class="field-error hidden" data-error-for="quantity"></span></label>

                    <label class="form-group"><span class="form-label">Date Acquired</span><input type="date" name="date_acquired" class="form-input"><span class="field-error hidden" data-error-for="date_acquired"></span></label>

                    <label class="form-group"><span class="form-label">Estimated Useful Life</span><input type="text" name="estimated_useful_life" class="form-input" placeholder="e.g. 3 years"><span class="field-error hidden" data-error-for="estimated_useful_life"></span></label>

                    <label class="form-group"><span class="form-label">Division</span><select name="division" class="form-input searchable-select"><option value="">Select division</option></select><span class="field-error hidden" data-error-for="division"></span></label>

                    <label class="form-group"><span class="form-label">Officer</span><select name="officer_id" class="form-input searchable-select"><option value="">Select officer</option></select><span class="field-error hidden" data-error-for="officer_id"></span></label>
                </div>

                <label class="form-group"><span class="form-label">Description</span><textarea name="description" rows="3" class="form-input" placeholder="Enter description"></textarea><span class="field-error hidden" data-error-for="description"></span></label>

                <label class="form-group"><span class="form-label">Current Condition</span><select name="current_condition" class="form-input searchable-select"><option value="">Select condition</option><option value="Good">Good</option><option value="Serviceable">Serviceable</option><option value="Needs Repair">Needs Repair</option><option value="Unserviceable">Unserviceable</option></select><span class="field-error hidden" data-error-for="current_condition"></span></label>

                <label class="form-group"><span class="form-label">Remarks</span><textarea name="remarks" rows="4" class="form-input" placeholder="Add remarks for this asset"></textarea><span class="field-error hidden" data-error-for="remarks"></span></label>

                <div class="flex flex-wrap items-center justify-end gap-3"><button id="cancelEdit" type="button" class="action-secondary">Cancel</button><button type="submit" class="action-primary">Save Changes</button></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.APP_LOOKUPS = <?= json_encode([
            'divisions' => $divisionCodes,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="assets/js/app.js?v=<?= escape_html($appJsVersion); ?>"></script>
</body>
</html>
