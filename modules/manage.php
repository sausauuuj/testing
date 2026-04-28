<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/_context.php';

?>
<section id="manage" class="app-view" data-view="manage">
    <div class="view-scroll section-stack">
        <div class="section-head">
            <h2 class="section-title">Manage</h2>
        </div>

        <div class="table-meta-bar">
            <span id="assetTableMeta" class="status-pill hidden" aria-hidden="true">0 RECORDS</span>
        </div>

        <div class="view-fill-card manage-table-card workspace-shell">
            <form id="assetFilterForm" class="toolbar-grid manage-toolbar">
                <label class="form-group">
                    <span class="form-label">Asset Name</span>
                    <div class="assets-search-wrap">
                        <input type="text" name="property_name" class="form-input assets-search-input" placeholder="Search asset name" id="assetNameSearch">
                        <svg class="assets-search-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="m16 16 4.5 4.5"></path>
                        </svg>
                    </div>
                </label>
                <label class="form-group">
                    <span class="form-label">Asset Type</span>
                    <select name="property_type" class="form-input">
                        <option value="">All asset types</option>
                        <?php foreach ($propertyTypes as $value): ?>
                            <option value="<?= escape_html($value); ?>"><?= escape_html($value); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-group">
                    <span class="form-label">Division</span>
                    <select name="division" id="manageDivisionFilter" class="form-input">
                        <option value="">All divisions</option>
                        <?php foreach ($divisions as $code => $label): ?>
                            <option value="<?= escape_html($code); ?>"><?= escape_html($code); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label id="manageOfficerField" class="form-group hidden">
                    <span class="form-label">Accountable Officer</span>
                    <select name="officer_id" id="manageOfficerSelect" class="form-input" disabled>
                        <option value="">Select division first</option>
                    </select>
                </label>
                <div class="toolbar-actions toolbar-actions--stacked manage-toolbar__actions">
                    <div class="toolbar-button-row">
                        <button type="submit" class="action-primary">Search</button>
                        <button id="resetFilters" type="button" class="action-secondary">Reset</button>
                    </div>
                </div>
            </form>
            <div class="mt-3 overflow-hidden rounded-[1rem] border border-slate-200 bg-white view-table-shell manage-table-shell">
                <div class="view-table-scroll">
                    <table class="min-w-full divide-y divide-slate-200 text-sm view-table manage-table">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium text-center" style="width: 3rem;">#</th>
                                <th class="px-4 py-3 font-medium">Asset</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 font-medium">Division</th>
                                <th class="px-4 py-3 font-medium">PAR / Cost</th>
                                <th class="px-4 py-3 font-medium">Condition</th>
                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assetTableBody"></tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <div class="pagination-info">
                        <span id="managePaginationMeta" class="pagination-meta">0 records</span>
                        <input type="hidden" id="manageRowsPerPage" class="pagination-rows-input" value="10" min="1" max="500">
                    </div>
                    <div class="pagination-controls">
                        <button id="managePrevPage" class="pagination-btn pagination-btn--prev" aria-label="Previous page">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                        </button>
                        <div id="managePageNumbers" class="pagination-numbers"></div>
                        <button id="manageNextPage" class="pagination-btn pagination-btn--next" aria-label="Next page">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
