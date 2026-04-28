<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/_context.php';

?>
<section id="registration" class="app-view" data-view="registration">
    <div class="view-scroll section-stack">
        <div class="section-head">
            <h2 class="section-title">Accountable Officers</h2>
        </div>

        <div class="table-meta-bar table-meta-bar--assets">
            <span id="registrationTableMeta" class="status-pill hidden" aria-hidden="true">0 RECORDS</span>
        </div>

        <div class="view-fill-card registration-card workspace-shell">
            <div class="registration-toolbar">
                <form id="registrationFilterForm" class="registration-toolbar__form">
                    <label class="form-group assets-search-field">
                        <span class="form-label">Name</span>
                        <div class="assets-search-wrap">
                            <input
                                type="text"
                                name="name"
                                class="form-input assets-search-input"
                                placeholder="Search accountable officer"
                                autocomplete="off"
                            >
                            <svg class="assets-search-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                        </div>
                    </label>
                    <label class="form-group">
                        <span class="form-label">Division</span>
                        <select name="division" class="form-input registration-division-filter">
                            <option value="">All divisions</option>
                            <?php
                            $registrationDivisionOptions = $editableDivisionDescriptions ?? $editableDivisions ?? $divisions;
                            foreach ($registrationDivisionOptions as $code => $label):
                            ?>
                                <option value="<?= escape_html($code); ?>"><?= escape_html($code . ' - ' . $label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="registration-toolbar__clear">
                        <button id="clearOfficerFilters" type="button" class="action-secondary">
                            <span>Clear</span>
                        </button>
                    </div>
                    <div class="toolbar-actions toolbar-actions--stacked registration-toolbar__actions">
                        <button id="openOfficerRegistration" type="button" class="action-primary action-primary--accent">
                            <svg class="inline -ml-1 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
                            <span>Add Officer</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-[0.95rem] border border-slate-200 bg-white view-table-shell registration-table-shell">
                <div class="view-table-scroll">
<table class="min-w-full divide-y divide-slate-200 text-sm view-table registration-table">
    <thead class="bg-slate-50 text-left text-slate-500"><tr>
        <th class="px-4 py-3 font-medium registration-table__heading registration-table__heading--index text-center">No.</th>
        <th class="px-4 py-3 font-medium registration-table__heading registration-table__heading--name">Name</th>
        <th class="px-4 py-3 font-medium registration-table__heading registration-table__heading--division text-center">Division</th>
        <th class="px-4 py-3 font-medium registration-table__heading registration-table__heading--position">Position</th>
        <th id="registrationUnitHeading" class="px-4 py-3 font-medium registration-table__heading registration-table__heading--unit text-center hidden">Unit</th>
        <th class="px-4 py-3 font-medium registration-table__heading registration-table__heading--updated text-center">Updated</th>
        <th class="px-4 py-3 font-medium registration-table__heading registration-table__heading--actions">Action</th>
    </tr>
</thead>
                        <tbody id="registrationTableBody"></tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <div class="pagination-info">
                        <span id="registrationPaginationMeta" class="pagination-meta">0 records</span>
                        <input type="hidden" id="registrationRowsPerPage" class="pagination-rows-input" value="10" min="1" max="500">
                    </div>
                    <div class="pagination-controls">
                        <button id="registrationPrevPage" class="pagination-btn pagination-btn--prev" aria-label="Previous page">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                        </button>
                        <div id="registrationPageNumbers" class="pagination-numbers"></div>
                        <button id="registrationNextPage" class="pagination-btn pagination-btn--next" aria-label="Next page">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="officerRegistrationModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="registration-modal-shell registration-modal-shell--officer">
        <div class="registration-modal__head">
            <div>
                <p class="panel-eyebrow">Accountable Officer</p>
                <h3 id="officerModalTitle" class="registration-modal__title">Add Officer</h3>
            </div>
            <button id="closeOfficerRegistration" type="button" class="asset-entry-close registration-modal__close" aria-label="Close officer registration">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        <form id="officerRegistrationForm" class="registration-modal__form">
            <input type="hidden" name="officer_id" value="">
            <div class="registration-modal__panel registration-modal__panel--officer-center">
                <div class="registration-form-grid registration-form-grid--officer-compact asset-shell-frame">
                    <div class="asset-step-card__header registration-form-grid__wide">
                        <h4 class="asset-step-card__title">Officer Details</h4>
                    </div>
                    <label class="form-group">
                        <span class="form-label">Division</span>
                        <select id="officerDivisionSelect" name="division" class="form-input" autocomplete="off">
                            <option value="">Select division</option>
                        </select>
                        <span class="field-error hidden" data-error-for="division"></span>
                    </label>
                    <label class="form-group">
                        <span class="form-label">Name <span class="registration-form-note">(Last Name, First Name, MI.)</span></span>
                        <input type="text" name="name" class="form-input" placeholder="Enter accountable officer name" autocomplete="off">
                        <span class="field-error hidden" data-error-for="name"></span>
                    </label>
                    <label class="form-group">
                        <span class="form-label">Position</span>
                        <input type="text" name="position" class="form-input" placeholder="Enter position" autocomplete="off">
                        <span class="field-error hidden" data-error-for="position"></span>
                    </label>
                    <label id="officerUnitField" class="form-group registration-form-grid__wide hidden">
                        <span class="form-label">Unit <span class="registration-form-note"></span></span>
                        <select name="unit" class="form-input" autocomplete="off">
                            <option value="">Select unit or office</option>
                        </select>
                        <span class="field-error hidden" data-error-for="unit"></span>
                    </label>
                </div>

                <div class="asset-step-card__actions registration-modal__actions">
                    <button id="deleteOfficerButton" type="button" class="action-secondary action-secondary--danger hidden">Delete Officer</button>
                    <div class="flex gap-2 ml-auto">
                        <button id="cancelOfficerRegistration" type="button" class="action-secondary">Cancel</button>
                        <button id="saveOfficerButton" type="submit" class="action-primary officer-action-btn officer-action-btn--edit">Save Officer</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="officerDetailsModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <div class="officer-details-shell w-full max-w-[720px] max-h-[calc(100vh-2rem)] overflow-hidden rounded-[1.2rem] bg-white shadow-2xl">
        <div class="officer-details-head registration-modal__head" style="background: #1f5fb4;">
            <div>
                <p class="panel-eyebrow">OFFICER INFORMATION</p>
                <h3 id="officerDetailsName" class="registration-modal__title"></h3>
                <p id="officerDetailsMeta" class="registration-modal__copy"></p>
            </div>
            <button id="closeOfficerDetailsModal" type="button" class="asset-entry-close registration-modal__close" aria-label="Close officer details">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>
        <div id="officerDetailsContent" class="p-5 max-h-[calc(100vh-10rem)] overflow-y-auto space-y-4 pr-2">
            <!-- Content will be rendered here -->
        </div>
        <div class="officer-details-actions flex justify-end gap-2 border-t border-slate-200 bg-slate-50 p-3">
            <button id="officerDetailsUpdateButton" type="button" class="action-primary officer-action-btn officer-action-btn--edit">Edit Officer</button>
            <button id="officerDetailsDeleteButton" type="button" class="action-secondary action-secondary--danger officer-action-btn officer-action-btn--delete">Delete Officer</button>
        </div>
    </div>
</div>
