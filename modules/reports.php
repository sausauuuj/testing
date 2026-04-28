<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/_context.php';

$inventoryReportItems = $inventoryService->listItems([], 500);
$reportOfficers = $officerService->listAll();

?>
<section id="reports" class="app-view" data-view="reports">
    <div class="view-scroll section-stack">
        <div class="section-head">
            <h2 class="section-title">Reports</h2>
        </div>

        <article class="reports-hero-shell">
            <div class="report-type-grid report-type-grid--feature">
                <button type="button" class="report-type-card" data-report-type="SPI">
                    <span class="report-type-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 3.5h6l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V5A1.5 1.5 0 0 1 7.5 3.5H8Z"></path>
                            <path d="M14 3.5V8h4"></path>
                            <path d="M9 11h6"></path>
                            <path d="M9 15h6"></path>
                        </svg>
                    </span>
                    <span class="report-type-card__code">SPI</span>
                    <span class="report-type-card__title">SEMI Property Issued</span>
                    <span class="report-type-card__cta">Generate
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 6 6 6-6 6"></path>
                        </svg>
                    </span>
                </button>
                <button type="button" class="report-type-card" data-report-type="INVENTORY">
                    <span class="report-type-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6.5h16"></path>
                            <path d="M6.5 3.5h11A1.5 1.5 0 0 1 19 5v14a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 19V5a1.5 1.5 0 0 1 1.5-1.5Z"></path>
                            <path d="M8 10h8"></path>
                            <path d="M8 14h8"></path>
                            <path d="M8 18h5"></path>
                        </svg>
                    </span>
                    <span class="report-type-card__code">Inventory</span>
                    <span class="report-type-card__title">Inventory and Supplies Issuance</span>
                    <span class="report-type-card__cta">Generate
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 6 6 6-6 6"></path>
                        </svg>
                    </span>
                </button>
            </div>
        </article>

        <div id="reportWorkflowArea" class="report-workspace hidden">
            <div id="reportSelectionHint" class="toolbar-note">Select SPI or Inventory to begin.</div>

            <article id="parReportPanel" class="workspace-shell report-workspace-panel hidden">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p id="reportPanelEyebrow" class="panel-eyebrow">Document Workflow</p>
                        <h3 id="reportPanelTitle" class="panel-title">PAR Generation</h3>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="form-block report-step">
                        <p class="form-block__label report-step__title">Step 1: Select Division & Officer</p>
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <label class="form-group">
                                <span class="form-label">Division</span>
                                <select id="reportDivision" class="form-input searchable-select">
                                    <option value="">Select division</option>
                                    <?php foreach ($divisions as $code => $label): ?>
                                        <option value="<?= escape_html($code); ?>"><?= escape_html($code); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label id="reportOfficerField" class="form-group hidden">
                                <span class="form-label">Accountable Officer</span>
                                <select id="reportOfficerSelect" class="form-input searchable-select" disabled>
                                    <option value="">Select division</option>
                                </select>
                            </label>
                        </div>
                        <p id="reportOfficerHint" class="mt-3 text-sm text-slate-500">Choose a division to load officers.</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <button id="clearOfficerSelection" type="button" class="action-secondary">Clear Officer</button>
                        </div>
                    </div>

                    <div class="form-block report-step">
                        <p class="form-block__label report-step__title">Step 2: Review Related Data</p>
                        <div class="mt-4 report-preview-card">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                                <span id="relatedDataMeta" class="status-pill">0 matched</span>
                            </div>
                            <div id="relatedDataSummary" class="mt-4 grid gap-3 sm:grid-cols-3"></div>
                            <div class="mt-5 overflow-hidden rounded-[1rem] border border-slate-200 bg-white report-preview-table-shell">
                                <div class="report-preview-table-scroll">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm view-table report-preview-table">
                                        <thead class="bg-slate-50 text-left text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 font-medium">PAR / ICS No.</th>
                                                <th class="px-4 py-3 font-medium">Date</th>
                                                <th class="px-4 py-3 font-medium">Property</th>
                                                <th class="px-4 py-3 font-medium text-right">Type</th>
                                            </tr>
                                        </thead>
                                        <tbody id="relatedDataTableBody" class="divide-y divide-slate-100 bg-white"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-block report-step">
                        <p class="form-block__label report-step__title">Step 3: Generate Report</p>
                        <form id="reportForm" class="mt-4 space-y-4">
                            <input type="hidden" name="report_type" value="PAR">
                            <input type="hidden" id="selectedOfficerId" name="officer_id" value="">
                            <input type="hidden" id="selectedOfficer" name="officer_name" value="">
                            <input type="hidden" id="selectedDivision" name="division" value="">
                            <div class="report-action-bar">
                                <button id="generateDocumentReport" type="submit" class="action-primary">Generate PAR Report</button>
                                <div class="report-action-group">
                                    <button id="printReport" type="button" class="action-secondary action-excel action-excel--icon" aria-label="Print document report" title="Print document report" disabled>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M7 9V4.5h10V9"></path>
                                            <path d="M6.5 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1.5"></path>
                                            <path d="M7 14h10v5.5H7z"></path>
                                        </svg>
                                    </button>
                                    <button id="exportReportCsv" type="button" class="action-secondary action-excel" disabled>Export Excel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </article>

            <article id="inventoryReportPanel" class="workspace-shell report-workspace-panel hidden">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="panel-eyebrow">Inventory Workflow</p>
                        <h3 class="panel-title">Inventory Report Generation</h3>
                    </div>
                </div>

                <form id="inventoryReportForm" class="mt-5 space-y-4">
                    <input type="hidden" name="report_type" value="INVENTORY">

                    <div class="form-block report-step">
                        <p class="form-block__label report-step__title">Step 1: Filter Inventory Records</p>
                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label class="form-group">
                                <span class="form-label">Date From</span>
                                <input type="date" name="date_from" class="form-input">
                            </label>
                            <label class="form-group">
                                <span class="form-label">Date To</span>
                                <input type="date" name="date_to" class="form-input">
                            </label>
                            <label class="form-group">
                                <span class="form-label">Officer</span>
                                <select name="officer_id" class="form-input searchable-select">
                                    <option value="">All officers</option>
                                    <?php foreach ($reportOfficers as $officer): ?>
                                        <option value="<?= escape_html((string) ($officer['officer_id'] ?? '')); ?>">
                                            <?= escape_html((string) ($officer['name'] ?? '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="form-group">
                                <span class="form-label">Item</span>
                                <select name="inventory_item_id" class="form-input searchable-select">
                                    <option value="">All items</option>
                                    <?php foreach ($inventoryReportItems as $inventoryItem): ?>
                                        <option value="<?= escape_html((string) ($inventoryItem['inventory_item_id'] ?? '')); ?>">
                                            <?= escape_html((string) ($inventoryItem['item_name'] ?? '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="form-block report-step">
                        <p class="form-block__label report-step__title">Step 2: Generate Inventory Report</p>
                        <div class="report-action-bar">
                            <button id="generateInventoryReport" type="submit" class="action-primary">Generate Inventory Report</button>
                            <div class="report-action-group">
                                <button id="printInventoryReport" type="button" class="action-secondary action-excel action-excel--icon" aria-label="Print inventory report" title="Print inventory report" disabled>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M7 9V4.5h10V9"></path>
                                        <path d="M6.5 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1.5"></path>
                                        <path d="M7 14h10v5.5H7z"></path>
                                    </svg>
                                </button>
                                <button id="exportInventoryReport" type="button" class="action-secondary action-excel" disabled>Export CSV</button>
                            </div>
                        </div>
                    </div>
                </form>
            </article>

            <article id="reportPreviewPanel" class="view-fill-card print-panel workspace-shell report-workspace-panel">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="panel-eyebrow">Generated Output</p>
                        <h3 class="panel-title">Report preview</h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-3"><span id="reportMeta" class="status-pill">No report</span></div>
                </div>
                <div id="reportContainer" class="mt-6"><div class="report-empty-state">Select SPI or Inventory to begin.</div></div>
            </article>
        </div>
    </div>
</section>
