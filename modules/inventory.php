<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/_context.php';

?>
<section id="inventory" class="app-view" data-view="inventory">
    <div class="view-scroll section-stack">
        <div class="section-head">
            <h2 id="inventorySectionTitle" class="section-title">Inventory</h2>
        </div>

        <div id="inventoryStockInPanel" class="inventory-mode-panel">
            <div class="table-meta-bar table-meta-bar--assets">
      
            </div>

            <div class="view-fill-card workspace-shell assets-directory-card">
                <form id="inventoryFilterForm" class="assets-toolbar assets-toolbar--merged assets-toolbar--expanded inventory-toolbar">
                    <label class="form-group assets-search-field">
                        <span class="form-label">Search Inventory</span>
                        <div class="assets-search-wrap">
                            <input id="inventorySearchFilter" type="text" name="search" class="form-input assets-search-input" placeholder="Type item name, RIS no., or stock no..." autocomplete="off">
                            <svg class="assets-search-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                        </div>
                    </label>
                    <label class="form-group assets-search-field assets-search-field--narrow">
                        <span class="form-label">Request Form</span>
                        <select name="request_type" class="form-input assets-search-input">
                            <option value="">All request forms</option>
                            <?php foreach ($inventoryRequestTypes as $type): ?>
                                <option value="<?= escape_html($type); ?>"><?= escape_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-group assets-search-field assets-search-field--narrow">
                        <span class="form-label">Category</span>
                        <select name="category" class="form-input assets-search-input">
                            <option value="">All categories</option>
                        </select>
                    </label>
                    <label class="form-group assets-search-field assets-search-field--narrow">
                        <span class="form-label">Stock Status</span>
                        <select name="stock_status" class="form-input assets-search-input">
                            <option value="">All stock levels</option>
                            <option value="HIGH">HIGH</option>
                            <option value="MEDIUM">MEDIUM</option>
                            <option value="LOW">LOW</option>
                        </select>
                    </label>
                    <div class="toolbar-actions toolbar-actions--stacked assets-toolbar__actions">
                        <button type="button" class="action-secondary inventory-toolbar__clear" id="resetInventoryFilters">Clear</button>
                        <button type="button" class="action-primary action-primary--accent inventory-toolbar__add" id="openInventoryModalToolbar">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            <span>Add Inventory</span>
                        </button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-[0.9rem] border border-slate-200 bg-white view-table-shell assets-directory-table-shell inventory-table-shell">
                    <div class="view-table-scroll">
                        <table class="w-full divide-y divide-slate-200 text-sm assets-directory-table inventory-table inventory-table--aligned">
                            <colgroup>
                                <col class="inventory-table__col inventory-table__col--index">
                                <col class="inventory-table__col inventory-table__col--ris">
                                <col class="inventory-table__col inventory-table__col--report-type">
                                <col class="inventory-table__col inventory-table__col--stock">
                                <col class="inventory-table__col inventory-table__col--item">
                                <col class="inventory-table__col inventory-table__col--qty">
                                <col class="inventory-table__col inventory-table__col--status">
                                <col class="inventory-table__col inventory-table__col--date">
                                <col class="inventory-table__col inventory-table__col--action">
                            </colgroup>
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium text-center">No.</th>
                                    <th class="px-4 py-3 font-medium">RIS No.</th>
                                    <th class="px-4 py-3 font-medium text-center">Report Type</th>
                                    <th class="px-4 py-3 font-medium">Stock Number</th>
                                    <th class="px-4 py-3 font-medium">Item</th>
                                    <th class="px-4 py-3 font-medium text-center">Quantity</th>
                                    <th class="px-4 py-3 font-medium text-center">Stock Level</th>
                                    <th class="px-4 py-3 font-medium text-center">Date</th>
                                    <th class="px-4 py-3 font-medium text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody" class="divide-y divide-slate-100 bg-white"></tbody>
                        </table>
                    </div>
                    <div class="table-pagination">
                        <div class="pagination-info">
                            <span id="inventoryTableMeta" class="pagination-meta">0 records</span>
                            <input type="hidden" id="inventoryRowsPerPage" class="pagination-rows-input" value="10" min="1" max="500">
                        </div>
                        <div class="pagination-controls">
                            <button id="inventoryPrevPage" class="pagination-btn pagination-btn--prev" aria-label="Previous page">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                            </button>
                            <div id="inventoryPageNumbers" class="pagination-numbers"></div>
                            <button id="inventoryNextPage" class="pagination-btn pagination-btn--next" aria-label="Next page">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="inventoryStockOutPanel" class="inventory-mode-panel hidden">
            <div class="view-fill-card workspace-shell assets-directory-card">
                <div class="inventory-history-shell">
                    <div class="inventory-history-head">
                        <div>
                        </div>
                    </div>
                    <form id="inventoryStockOutFilterForm" class="assets-toolbar assets-toolbar--merged assets-toolbar--expanded inventory-toolbar inventory-history-toolbar inventory-history-toolbar--stockout">
                    <label class="form-group assets-search-field inventory-history-toolbar__field inventory-history-toolbar__field--wide">
                            <span class="form-label">Search</span>
                            <div class="assets-search-wrap">
                                <input id="inventoryStockOutSearch" type="text" name="search" class="form-input assets-search-input" placeholder="Officer name or item">
                                <svg class="assets-search-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                            </div>
                        </label>
                        <label class="form-group assets-search-field assets-search-field--narrow inventory-history-toolbar__field">
                            <span class="form-label">Category</span>
                            <select id="inventoryStockOutCategory" name="category" class="form-input assets-search-input">
                                <option value="">All categories</option>
                            </select>
                        </label>
                        <label class="form-group assets-search-field assets-search-field--narrow inventory-history-toolbar__field">
                            <span class="form-label">Date From</span>
                            <input id="inventoryStockOutDateFromFilter" type="date" name="date_from" class="form-input assets-search-input">
                        </label>
                        <label class="form-group assets-search-field assets-search-field--narrow inventory-history-toolbar__field">
                            <span class="form-label">Date To</span>
                            <input id="inventoryStockOutDateToFilter" type="date" name="date_to" class="form-input assets-search-input">
                        </label>
                        <div class="toolbar-actions toolbar-actions--stacked assets-toolbar__actions inventory-history-toolbar__actions">
                            <button type="button" class="action-secondary action-excel action-excel--icon" id="printInventoryStockOutFilters" aria-label="Print stock out records" title="Print stock out records">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 9V4.5h10V9"></path>
                                    <path d="M6.5 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1.5"></path>
                                    <path d="M7 14h10v5.5H7z"></path>
                                </svg>
                            </button>
                            <button type="button" class="action-secondary" id="clearInventoryStockOutFilters">Clear</button>
                            <button type="button" class="action-primary" id="openInventoryStockOutFromPanel">Stock Out</button>
                        </div>
                    </form>
                    <div class="overflow-hidden rounded-[0.9rem] border border-slate-200 bg-white view-table-shell assets-directory-table-shell inventory-table-shell inventory-table-shell--history">
                        <div class="view-table-scroll">
                            <table class="w-full divide-y divide-slate-200 text-sm assets-directory-table inventory-history-table">
                                <colgroup>
                                    <col class="inventory-history-table__col inventory-history-table__col--index">
                                    <col class="inventory-history-table__col inventory-history-table__col--ris">
                                    <col class="inventory-history-table__col inventory-history-table__col--officer">
                                    <col class="inventory-history-table__col inventory-history-table__col--stock">
                                    <col class="inventory-history-table__col inventory-history-table__col--item">
                                    <col class="inventory-history-table__col inventory-history-table__col--unit">
                                    <col class="inventory-history-table__col inventory-history-table__col--quantity">
                                    <col class="inventory-history-table__col inventory-history-table__col--date">
                                    <col class="inventory-history-table__col inventory-history-table__col--action">
                                </colgroup>
                                <thead class="bg-slate-50 text-left text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 font-medium text-center inventory-history-table__heading inventory-history-table__heading--index">No.</th>
                                        <th class="px-4 py-3 font-medium inventory-history-table__heading inventory-history-table__heading--ris">RIS No.</th>
                                        <th class="px-4 py-3 font-medium inventory-history-table__heading inventory-history-table__heading--officer">Officer Name</th>
                                        <th class="px-4 py-3 font-medium inventory-history-table__heading inventory-history-table__heading--stock">Stock No.</th>
                                        <th class="px-4 py-3 font-medium inventory-history-table__heading inventory-history-table__heading--item">Item</th>
                                        <th class="px-4 py-3 font-medium inventory-history-table__heading inventory-history-table__heading--unit">Unit</th>
                                        <th class="px-4 py-3 font-medium text-center inventory-history-table__heading inventory-history-table__heading--quantity">Quantity</th>
                                        <th class="px-4 py-3 font-medium text-center inventory-history-table__heading inventory-history-table__heading--date">Date</th>
                                        <th class="px-4 py-3 font-medium text-center inventory-history-table__heading inventory-history-table__heading--action">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryStockOutTableBody" class="divide-y divide-slate-100 bg-white"></tbody>
                            </table>
                        </div>
                        <div class="table-pagination">
                            <div class="pagination-info">
                                <span id="inventoryStockOutMeta" class="pagination-meta">0 records</span>
                                <input type="hidden" id="inventoryStockOutRowsPerPage" class="pagination-rows-input" value="10" min="1" max="500">
                            </div>
                            <div class="pagination-controls">
                                <button id="inventoryStockOutPrevPage" class="pagination-btn pagination-btn--prev" aria-label="Previous page">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                                </button>
                                <div id="inventoryStockOutPageNumbers" class="pagination-numbers"></div>
                                <button id="inventoryStockOutNextPage" class="pagination-btn pagination-btn--next" aria-label="Next page">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="inventoryModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="registration-modal-shell inventory-modal-shell">
        <div class="registration-modal__head">
            <div>
                <p class="panel-eyebrow">Inventory</p>
                <h3 id="inventoryModalTitle" class="registration-modal__title">Add Inventory</h3>
                <p class="registration-modal__copy">Use the guided 2-step form to save a new inventory record.</p>
            </div>
            <button id="closeInventoryModal" type="button" class="asset-entry-close registration-modal__close" aria-label="Close inventory form">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        <form id="inventoryForm" class="registration-modal__form">
            <input type="hidden" name="inventory_item_id" value="">
            <input type="hidden" name="request_type" value="">
            <input type="hidden" name="division" value="">
            <input type="hidden" name="officer_id" value="">
            <input type="hidden" name="description" value="">

            <section id="inventoryStep1Section" class="wizard-step">
                <div class="wizard-form inventory-stockout-card">
                    <div class="wizard-form__content asset-shell-frame">
                        <div class="registration-step-card__header">
                            <h4 class="asset-step-card__title">Request &amp; Funding</h4>
                        </div>

                        <div class="registration-form-grid">
                            <div class="asset-shell-frame">
                                <div class="form-label">Request Type</div>
                                <div class="inventory-request-picker">
                                    <?php foreach ($inventoryRequestTypes as $type): ?>
                                        <button type="button" class="inventory-request-card" data-request-type="<?= escape_html($type); ?>">
                                            <span class="inventory-request-card__code"><?= escape_html($type); ?></span>
                                            <span class="inventory-request-card__copy">
                                                <?= $type === 'RSMI'
                                                    ? 'Report of Supplies and Materials Issued'
                                                    : 'Other Supplies and Materials Issued'; ?>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <span class="field-error hidden" data-error-for="request_type"></span>
                            </div>

                            <div class="asset-shell-frame">
                                <div class="form-label">Funding Source</div>
                                <input type="hidden" name="funding_source" value="">
                                <div class="inventory-request-picker inventory-request-picker--stacked">
                                    <button type="button" class="inventory-request-card inventory-funding-card" data-funding-source="DEPDev">
                                        <span class="inventory-request-card__code">DEPDev</span>
                                        <span class="inventory-request-card__copy">Department of Economy, Planning, and Development</span>
                                    </button>
                                    <button type="button" class="inventory-request-card inventory-funding-card" data-funding-source="RDC">
                                        <span class="inventory-request-card__code">RDC</span>
                                        <span class="inventory-request-card__copy">Regional Development Council</span>
                                    </button>
                                </div>
                                <span class="field-error hidden" data-error-for="funding_source"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="inventoryStep2Section" class="wizard-step">
                <div class="wizard-form inventory-stockout-card">
                    <div class="wizard-form__content asset-shell-frame">
                        <div class="registration-step-card__header">
                            <h4 class="asset-step-card__title">Inventory Details</h4>
                        </div>

                        <div class="inventory-identifier-grid inventory-identifier-grid--single inventory-identifier-grid--centered registration-form-grid__wide">
                            <input type="hidden" name="ris_number" value="">
                            <label class="form-group inventory-identifier-grid__stock">
                                <span class="form-label">Stock Number</span>
                                <input type="text" name="stock_number" class="form-input" placeholder="Auto-generated from category" readonly>
                                <span class="field-error hidden" data-error-for="stock_number"></span>
                            </label>
                        </div>

                        <div class="registration-form-grid registration-form-grid--inventory inventory-form-grid">
                            <label class="form-group">
                                <span class="form-label">Category</span>
                                <select name="category" class="form-input" disabled>
                                </select>
                                <span class="field-error hidden" data-error-for="category"></span>
                            </label>
                            <label class="form-group">
                                <span class="form-label">Item</span>
                                <input type="text" name="item_name" class="form-input" placeholder="Enter item name">
                                <span class="field-error hidden" data-error-for="item_name"></span>
                            </label>
                            <label class="form-group">
                                <span class="form-label">Unit</span>
                                <select name="unit" class="form-input">
                                    <option value="">Select unit</option>
                                    <?php foreach ($inventoryUnits as $unit): ?>
                                        <option value="<?= escape_html($unit); ?>"><?= escape_html($unit); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-error hidden" data-error-for="unit"></span>
                            </label>
                            <label class="form-group">
                                <span class="form-label">Quantity</span>
                                <input type="number" min="1" name="quantity_issued" class="form-input" value="1">
                                <span class="field-error hidden" data-error-for="quantity_issued"></span>
                            </label>
                            <label class="form-group">
                                <span class="form-label">Unit Cost (PHP)</span>
                                <input type="number" min="0" step="0.01" name="unit_cost" class="form-input" value="" placeholder="Enter unit cost">
                                <span class="field-error hidden" data-error-for="unit_cost"></span>
                            </label>
                            <label class="form-group">
                                <span class="form-label">Date</span>
                                <input type="date" name="issued_at" class="form-input" value="<?= escape_html($today); ?>">
                                <span class="field-error hidden" data-error-for="issued_at"></span>
                            </label>
                            <label class="form-group registration-form-grid__wide">
                                <span class="form-label">Total Amount</span>
                                <input type="text" name="total_amount" class="form-input" placeholder="Auto-computed" readonly>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <div class="asset-step-card__actions registration-modal__actions inventory-modal__actions-fixed">
                <button id="cancelInventoryModal" type="button" class="action-secondary">Cancel</button>
                <button id="saveInventoryButton" type="submit" class="action-primary">Save Inventory Entry</button>
            </div>
        </form>
    </div>
</div>

<script id="inventoryAllocationTargetsData" type="application/json"><?= json_encode($inventoryAllocationTargets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div id="inventoryAllocationModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="registration-modal-shell inventory-modal-shell">
        <div class="registration-modal__head">
            <div>
                <p class="panel-eyebrow">Inventory Allocation</p>
                <h3 id="inventoryAllocationModalTitle" class="registration-modal__title">Item Name | Stock No. | Request Type</h3>
            </div>
            <button id="closeInventoryAllocationModal" type="button" class="asset-entry-close registration-modal__close" aria-label="Close allocation form">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        <form id="inventoryAllocationForm" class="registration-modal__form">
            <input type="hidden" name="inventory_item_id" value="">
            <input type="hidden" name="allocation_mode" value="default">
            <div class="wizard-form asset-step-card">
                <div class="wizard-form__content">
                    <div class="inventory-allocation-head">
                        <div>
                            <span class="form-label">Allocations</span>
                            <p id="inventoryAllocationModalMeta" class="inventory-allocation-copy"></p>
                        </div>
                        <div id="inventoryAllocationSummary" class="inventory-allocation-summary">Allocated: 0 / 1</div>
                    </div>
                    <div id="inventoryAllocationFormNotice" class="field-error hidden"></div>
                    <label id="inventoryAllocationQuantityField" class="form-group hidden">
                        <span class="form-label">Stock In Quantity</span>
                        <input type="number" min="1" step="1" name="stock_in_quantity" class="form-input" value="1">
                        <span class="field-error hidden" data-error-for="stock_in_quantity"></span>
                    </label>
                    <input type="hidden" name="allocations" value="">
                    <div id="inventoryAllocationRows" class="inventory-allocation-list inventory-allocation-list--stacked"></div>
                    <span class="field-error hidden" data-error-for="allocations"></span>
                </div>
            </div>
            <div class="asset-step-card__actions registration-modal__actions inventory-modal__actions-fixed">
                <button id="skipInventoryAllocationButton" type="button" class="action-secondary">Skip</button>
                <button id="saveInventoryAllocationButton" type="submit" class="action-primary">Save Allocation</button>
            </div>
        </form>
    </div>
</div>

<div id="inventoryBatchStockOutModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="registration-modal-shell inventory-stockout-shell">
        <div class="registration-modal__head">
            <div>
                <p class="panel-eyebrow">Inventory</p>
                <h3 class="registration-modal__title">Stock Out Request</h3>
            </div>
            <button id="closeInventoryBatchStockOutModal" type="button" class="asset-entry-close registration-modal__close" aria-label="Close stock out form">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        <form id="inventoryBatchStockOutForm" class="registration-modal__form">
            <input type="hidden" name="movement_type" value="DEDUCT">
            <div class="registration-step-card inventory-stockout-card">
                <section id="inventoryStockOutDetailsSection" class="inventory-stockout-step">
                    <div class="registration-form-grid inventory-stockout-header-grid inventory-stockout-form-grid">
                        <label class="form-group">
                            <span class="form-label">Responsibility Center Code</span>
                            <select name="division" class="form-input">
                                <option value="">Select responsibility center code</option>
                                <?php foreach ($editableDivisions as $divisionCode): ?>
                                    <option value="<?= escape_html($divisionCode); ?>"><?= escape_html($divisionCode); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-error hidden" data-error-for="division"></span>
                        </label>
                        <label class="form-group">
                            <span class="form-label">Accountable Officer</span>
                            <select name="officer_id" class="form-input" disabled>
                                <option value="">Select responsibility center code first</option>
                            </select>
                            <span class="field-error hidden" data-error-for="officer_id"></span>
                        </label>
                        <label class="form-group">
                            <span class="form-label">Date</span>
                            <input type="date" name="movement_date" class="form-input" value="<?= escape_html($today); ?>">
                            <span class="field-error hidden" data-error-for="movement_date"></span>
                        </label>
                        <label class="form-group registration-form-grid__wide">
                            <span class="form-label">Description</span>
                            <textarea name="notes" rows="3" class="form-input" placeholder="Describe this stock out request."></textarea>
                            <span class="field-error hidden" data-error-for="notes"></span>
                        </label>
                    </div>

                    <div class="asset-step-card__actions registration-modal__actions inventory-stockout-actions">
                        <button id="cancelInventoryBatchStockOut" type="button" class="action-secondary">Cancel</button>
                        <button id="inventoryStockOutDetailsNext" type="button" class="action-primary">Save and Continue</button>
                    </div>
                </section>

                <section id="inventoryStockOutItemsSection" class="inventory-stockout-step hidden">
                    <div class="inventory-stockout-items-card">
                        <div class="inventory-stockout-items-head">
                            <div>
                                <h4 class="asset-step-card__title">Items to Stock Out</h4>
                            </div>
                            <button id="addInventoryStockOutRow" type="button" class="action-secondary">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                <span>Add Item</span>
                            </button>
                        </div>

                        <div class="inventory-stockout-list-shell">
                            <div id="inventoryBatchStockOutRows" class="inventory-stockout-list"></div>
                        </div>
                    </div>

                    <div class="asset-step-card__actions registration-modal__actions inventory-stockout-actions">
                        <button id="inventoryStockOutItemsBack" type="button" class="action-secondary">Back</button>
                        <button id="saveInventoryBatchStockOutButton" type="submit" class="action-primary">Save Stock Out</button>
                    </div>
                </section>
            </div>
        </form>
    </div>
</div>

<div id="inventoryMovementModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="registration-modal-shell inventory-movement-shell">
        <div class="registration-modal__head">
            <div>
                <p class="panel-eyebrow">Stock Movement</p>
                <h3 id="inventoryMovementTitle" class="registration-modal__title">Update Stock</h3>
                <p id="inventoryMovementCopy" class="registration-modal__copy">Record a stock addition or deduction and keep the movement history complete.</p>
            </div>
            <button id="closeInventoryMovementModal" type="button" class="asset-entry-close registration-modal__close" aria-label="Close stock movement">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        <form id="inventoryMovementForm" class="registration-modal__form">
            <input type="hidden" name="inventory_item_id" value="">
            <input type="hidden" name="movement_type" value="ADD">
            <div class="registration-step-card">
                <div id="inventoryMovementSummary" class="inventory-summary-card"></div>
                <div id="inventoryMovementProgressTracker" class="asset-progress-card hidden">
                    <div class="asset-progress">
                        <div class="asset-progress-step" data-inventory-movement-step="step1">
                            <span class="asset-progress-step__circle">
                                <span class="asset-progress-step__number">1</span>
                                <svg class="asset-progress-step__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 13 4 4L19 7" /></svg>
                            </span>
                            <span class="asset-progress-step__label">Accountable Officer</span>
                        </div>
                        <span class="asset-progress__line inventory-progress__line" aria-hidden="true"></span>
                        <div class="asset-progress-step" data-inventory-movement-step="step2">
                            <span class="asset-progress-step__circle">
                                <span class="asset-progress-step__number">2</span>
                                <svg class="asset-progress-step__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 13 4 4L19 7" /></svg>
                            </span>
                            <span class="asset-progress-step__label">Quantity &amp; Description</span>
                        </div>
                    </div>
                </div>

                <section id="inventoryMovementStep1Section" class="wizard-step hidden">
                    <div class="registration-form-grid inventory-movement-step1-grid">
                        <label class="form-group">
                            <span class="form-label">Responsibility Center Code</span>
                            <select name="division" class="form-input">
                                <option value="">Select responsibility center code</option>
                                <?php foreach ($editableDivisions as $divisionCode): ?>
                                    <option value="<?= escape_html($divisionCode); ?>"><?= escape_html($divisionCode); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-error hidden" data-error-for="division"></span>
                        </label>
                        <label class="form-group">
                            <span class="form-label">Accountable Officer</span>
                            <select name="officer_id" class="form-input" disabled>
                                <option value="">Select responsibility center code first</option>
                            </select>
                            <span class="field-error hidden" data-error-for="officer_id"></span>
                        </label>
                    </div>
                </section>

                <section id="inventoryMovementStep2Section" class="wizard-step">
                    <div class="registration-form-grid registration-form-grid--stacked">
                        <label class="form-group">
                            <span class="form-label">Date</span>
                            <input type="date" name="movement_date" class="form-input" value="<?= escape_html($today); ?>">
                            <span class="field-error hidden" data-error-for="movement_date"></span>
                        </label>
                        <label class="form-group">
                            <span class="form-label">Quantity</span>
                            <input type="number" min="1" name="quantity" class="form-input" value="1">
                            <span class="field-error hidden" data-error-for="quantity"></span>
                        </label>
                        <label class="form-group registration-form-grid__wide">
                            <span class="form-label">Description</span>
                            <textarea name="notes" rows="4" class="form-input" placeholder="Describe this stock movement."></textarea>
                            <span class="field-error hidden" data-error-for="notes"></span>
                        </label>
                    </div>
                </section>

                <div class="asset-step-card__actions registration-modal__actions">
                    <button id="cancelInventoryMovement" type="button" class="action-secondary">Cancel</button>
                    <button id="inventoryMovementBackButton" type="button" class="asset-step-btn asset-step-btn--ghost hidden">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 6-6 6 6 6" /></svg>
                        <span>Back</span>
                    </button>
                    <button id="inventoryMovementNextButton" type="button" class="asset-step-btn asset-step-btn--primary hidden">
                        <span>Next</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6" /></svg>
                    </button>
                    <button id="saveInventoryMovementButton" type="submit" class="action-primary">Save Stock Movement</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="inventoryDetailsModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="w-full max-w-4xl max-h-[calc(100vh-2rem)] overflow-hidden rounded-[1.2rem] bg-white shadow-2xl">
        <div class="inventory-details-header">
            <div class="inventory-details-header__content">
                <p class="inventory-details-eyebrow">Inventory</p>
                <h3 id="inventoryDetailsName" class="inventory-details-title">Inventory Item</h3>
                <p id="inventoryDetailsMeta" class="inventory-details-meta"></p>
            </div>
            <div class="inventory-details-header__actions">
                <button id="closeInventoryDetailsModal" type="button" class="inventory-details-close-btn" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
        </div>
        <div id="inventoryDetailsContent" class="inventory-details-content"></div>
        <div class="inventory-details-footer">
            <button id="inventoryDetailsUpdateButton" type="button" class="action-secondary">Edit</button>
            <button id="inventoryDetailsDeleteStockButton" type="button" class="action-secondary action-secondary--danger">Delete</button>
            <button id="closeInventoryDetailsButton" type="button" class="action-secondary">Cancel</button>
        </div>
    </div>
</div>

<div id="inventoryHistoryModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] overflow-hidden rounded-[1.2rem] bg-white shadow-2xl">
        <div class="inventory-details-header">
            <div class="inventory-details-header__content">
                <p class="inventory-details-eyebrow">Inventory</p>
                <h3 id="inventoryHistoryName" class="inventory-details-title">History</h3>
                <p id="inventoryHistoryMeta" class="inventory-details-meta"></p>
            </div>
            <div class="inventory-details-header__actions">
                <button id="closeInventoryHistoryModal" type="button" class="inventory-details-close-btn" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
        </div>
        <div id="inventoryHistoryContent" class="inventory-details-content"></div>
        <div class="inventory-details-footer">
            <button id="closeInventoryHistoryButton" type="button" class="action-secondary">Close</button>
        </div>
    </div>
</div>

<div id="inventoryStockOutDetailsModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="w-full max-w-4xl max-h-[calc(100vh-2rem)] overflow-hidden rounded-[1.2rem] bg-white shadow-2xl">
        <div class="inventory-details-header">
            <div class="inventory-details-header__content">
                <p class="inventory-details-eyebrow">Stock Out</p>
                <h3 id="inventoryStockOutDetailsTitle" class="inventory-details-title">Stock Out Details</h3>
                <p id="inventoryStockOutDetailsMeta" class="inventory-details-meta"></p>
            </div>
            <div class="inventory-details-header__actions">
                <button id="closeInventoryStockOutDetailsModal" type="button" class="inventory-details-close-btn" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
        </div>
        <div id="inventoryStockOutDetailsContent" class="inventory-details-content"></div>
        <div class="inventory-details-footer">
            <button id="closeInventoryStockOutDetailsButton" type="button" class="action-secondary">Close</button>
        </div>
    </div>
</div>
