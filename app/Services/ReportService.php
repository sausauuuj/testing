<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;

final class ReportService
{
    private const ENTITY_NAME = 'Department of Economy, Planning, and Development IX';
    private const PAR_ISSUED_BY_NAME = 'Kristy R. Mante';
    private const POSITION_ABBREVIATIONS = [
        'SUPERVISING ADMINISTRATIVE OFFICER' => 'SAO',
        'CHIEF ADMINISTRATIVE OFFICER' => 'CAO',
        'ASSISTANT REGIONAL DIRECTOR' => 'ARD',
        'REGIONAL DIRECTOR' => 'RD',
        'SENIOR ECONOMIC DEVELOPMENT SPECIALIST' => 'Sr. EDS',
        'SUPERVISING ECONOMIC DEVELOPMENT SPECIALIST' => 'SEDS',
        'CHIEF ECONOMIC DEVELOPMENT SPECIALIST' => 'CEDS',
        'ECONOMIC DEVELOPMENT SPECIALIST' => 'EDS',
        'ADMINISTRATIVE ASSISTANT' => 'ADAS',
        'ADMINISTRATIVE OFFICER' => 'AO',
        'ADMINISTRATIVE AIDE' => 'ADA',
        'ACCOUNTANT' => 'ACT',
        'INFORMATION SPECIALIST ANALYST' => 'ISA',
        'INFORMATION OFFICER' => 'IO',
        'DEVELOPMENT RESEARCH OFFICER' => 'DRO',
        'DEVELOPMENT MANAGEMENT OFFICER' => 'DMO',
        'PLANNING OFFICER' => 'PO',
    ];

    private AssetService $assetService;
    private OfficerService $officerService;
    private InventoryService $inventoryService;
    private ?array $issuedByProfile = null;

    public function __construct(?AssetService $assetService = null)
    {
        $this->assetService = $assetService ?? new AssetService();
        $this->officerService = new OfficerService();
        $this->inventoryService = new InventoryService();
    }

    public function generate(array $filters = []): array
    {
        $reportType = strtoupper(trim((string) ($filters['report_type'] ?? '')));

        if ($reportType === 'PAR' || $reportType === 'ICS') {
            $filters = $this->prepareDocumentFilters($filters, $reportType);
            return $this->generateDocumentReport($filters);
        }

        if ($reportType === 'RPCPPE') {
            $filters = $this->prepareSpiFilters($filters);
            return $this->generateRpcppeReport($filters);
        }

        if ($reportType === 'REGSPI') {
            $filters = $this->prepareSpiFilters($filters);
            return $this->generateRegSpiReport($filters);
        }

        if ($reportType === 'INVENTORY') {
            $filters = $this->prepareInventoryFilters($filters);
            return $this->generateInventoryReport($filters);
        }

        throw new ValidationException('Select a report type first.', [
            'report_type' => 'Choose PAR, SPI, ICS, or Inventory before generating a report.',
        ]);
    }

    public function exportCsv(array $filters = []): array
    {
        $reportType = strtoupper(trim((string) ($filters['report_type'] ?? 'PAR')));
        $filters = $this->prepareDocumentFilters($filters, $reportType === 'ICS' ? 'ICS' : 'PAR');
        $assets = $this->assetService->listFiltered($filters, 3000);
        $sheets = $this->groupAssetsByPar($assets);
        $defaultPrefix = strtoupper(trim((string) ($filters['report_type'] ?? 'PAR')));

        return [
            'filename' => $this->buildCsvFilename($sheets, $defaultPrefix),
            'content' => $this->renderParCsv($sheets),
        ];
    }

    public function exportSpreadsheet(array $filters = []): array
    {
        $reportType = strtoupper(trim((string) ($filters['report_type'] ?? 'PAR')));

        if ($reportType === 'INVENTORY') {
            $filters = $this->prepareInventoryFilters($filters);
            $items = $this->inventoryService->listItems($filters, 3000);

            return [
                'filename' => $this->buildInventorySpreadsheetFilename($filters),
                'content' => $this->renderInventoryReportCsv($items),
                'content_type' => 'text/csv; charset=UTF-8',
            ];
        }

        $filters = $this->prepareDocumentFilters($filters, $reportType === 'ICS' ? 'ICS' : 'PAR');
        $assets = $this->assetService->listFiltered($filters, 3000);
        $sheets = $this->groupAssetsByPar($assets);
        $defaultPrefix = strtoupper(trim((string) ($filters['report_type'] ?? 'PAR')));

        return [
            'filename' => $this->buildSpreadsheetFilename($sheets, $defaultPrefix),
            'content' => $this->renderParSpreadsheetWorkbook($sheets),
            'content_type' => 'application/vnd.ms-excel; charset=UTF-8',
        ];
    }

    private function generateDocumentReport(array $filters): array
    {
        $assets = $this->assetService->listFiltered($filters, 3000);
        $sheets = $this->groupAssetsByPar($assets);
        $sheetCount = count($sheets);
        $recordCount = count($assets);
        $documentType = strtoupper(trim((string) ($filters['report_type'] ?? 'PAR')));
        $documentLabel = $documentType === 'ICS' ? 'ICS' : 'PAR';

        return [
            'count' => $sheetCount,
            'record_count' => $recordCount,
            'meta_label' => sprintf(
                '%d %s sheet%s | %d asset line%s',
                $sheetCount,
                $documentLabel,
                $sheetCount === 1 ? '' : 's',
                $recordCount,
                $recordCount === 1 ? '' : 's'
            ),
            'html' => $this->renderParHtml($sheets, $filters),
        ];
    }

    private function generateRpcppeReport(array $filters): array
    {
        $assets = $this->assetService->listFiltered($filters, 3000);
        $recordCount = count($assets);

        return [
            'count' => 1,
            'record_count' => $recordCount,
            'meta_label' => sprintf('%d asset line%s', $recordCount, $recordCount === 1 ? '' : 's'),
            'html' => $this->renderRpcppeHtml($assets, $filters),
        ];
    }

    private function generateRegSpiReport(array $filters): array
    {
        $assets = $this->assetService->listFiltered($filters, 3000);
        $recordCount = count($assets);

        return [
            'count' => 1,
            'record_count' => $recordCount,
            'meta_label' => sprintf('%d asset line%s', $recordCount, $recordCount === 1 ? '' : 's'),
            'html' => $this->renderRegSpiHtml($assets, $filters),
        ];
    }

    private function generateInventoryReport(array $filters): array
    {
        $items = $this->inventoryService->listItems($filters, 3000);
        $recordCount = count($items);

        return [
            'count' => 1,
            'record_count' => $recordCount,
            'meta_label' => sprintf('%d inventory record%s', $recordCount, $recordCount === 1 ? '' : 's'),
            'html' => $this->renderInventoryReportHtml($items, $filters),
        ];
    }

    private function renderInventoryReportHtml(array $items, array $filters): string
    {
        $officerName = trim((string) ($filters['officer_name'] ?? ''));
        $itemLabel = trim((string) ($filters['item_label'] ?? ''));
        $chips = [];

        if (trim((string) ($filters['date_from'] ?? '')) !== '' || trim((string) ($filters['date_to'] ?? '')) !== '') {
            $chips[] = 'Date: '
                . ($filters['date_from'] !== '' ? $this->formatLongDate((string) $filters['date_from']) : 'Start')
                . ' to '
                . ($filters['date_to'] !== '' ? $this->formatLongDate((string) $filters['date_to']) : 'Present');
        }

        if ($officerName !== '') {
            $chips[] = 'Officer: ' . $officerName;
        }

        if ($itemLabel !== '') {
            $chips[] = 'Item: ' . $itemLabel;
        }

        $html = '<article class="inventory-report-page">';
        $html .= '<div class="inventory-report-page__head">';
        $html .= '<div><p class="panel-eyebrow">Inventory Report</p><h2 class="inventory-report-page__title">Inventory and Supplies Issuance Report</h2></div>';
        $html .= '<div class="inventory-report-page__meta">Generated ' . escape_html(date('F j, Y g:i A')) . '</div>';
        $html .= '</div>';

        if ($chips !== []) {
            $html .= '<div class="report-chip-set inventory-report-page__filters">';

            foreach ($chips as $chip) {
                $html .= '<span class="report-chip">' . escape_html($chip) . '</span>';
            }

            $html .= '</div>';
        }

        if ($items === []) {
            $html .= '<div class="report-empty-state">No inventory records matched the selected filters.</div>';
            $html .= '</article>';
            return $html;
        }

        $html .= '<div class="mt-5 overflow-hidden rounded-[1rem] border border-slate-200 bg-white">';
        $html .= '<div class="report-preview-table-scroll">';
        $html .= '<table class="min-w-full divide-y divide-slate-200 text-sm inventory-report-table">';
        $html .= '<thead class="bg-slate-50 text-left text-slate-500"><tr>';
        $html .= '<th class="px-4 py-3 font-medium">Date</th>';
        $html .= '<th class="px-4 py-3 font-medium">RIS No.</th>';
        $html .= '<th class="px-4 py-3 font-medium">Type</th>';
        $html .= '<th class="px-4 py-3 font-medium">Responsibility Center</th>';
        $html .= '<th class="px-4 py-3 font-medium">Officer</th>';
        $html .= '<th class="px-4 py-3 font-medium">Stock No.</th>';
        $html .= '<th class="px-4 py-3 font-medium">Item</th>';
        $html .= '<th class="px-4 py-3 font-medium">Unit</th>';
        $html .= '<th class="px-4 py-3 font-medium">Qty Issued</th>';
        $html .= '<th class="px-4 py-3 font-medium">Current Stock</th>';
        $html .= '<th class="px-4 py-3 font-medium">Unit Cost</th>';
        $html .= '<th class="px-4 py-3 font-medium">Total Amount</th>';
        $html .= '<th class="px-4 py-3 font-medium">Stock Limit</th>';
        $html .= '<th class="px-4 py-3 font-medium">Status</th>';
        $html .= '</tr></thead><tbody class="divide-y divide-slate-100 bg-white">';

        foreach ($items as $item) {
            $html .= '<tr>';
            $html .= '<td class="px-4 py-3">' . escape_html($this->formatDate((string) ($item['issued_at'] ?? ''))) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['ris_number'] ?? '')) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['request_type'] ?? '')) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['division'] ?? '')) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['officer_name'] ?? '')) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['stock_number'] ?? '')) . '</td>';
            $html .= '<td class="px-4 py-3"><div class="inventory-report-table__item-name">' . escape_html((string) ($item['item_name'] ?? '')) . '</div><div class="inventory-report-table__item-meta">' . escape_html((string) ($item['description'] ?? '')) . '</div></td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['unit'] ?? '')) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['quantity_issued'] ?? 0)) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['current_stock'] ?? 0)) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html(number_format((float) ($item['unit_cost'] ?? 0), 2)) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html(number_format((float) ($item['total_amount'] ?? 0), 2)) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['stock_limit'] ?? 0)) . '</td>';
            $html .= '<td class="px-4 py-3">' . escape_html((string) ($item['stock_status_label'] ?? 'In Stock')) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div></div></article>';

        return $html;
    }

    private function renderInventoryReportCsv(array $items): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return '';
        }

        fputcsv($stream, [
            'Date',
            'RIS No.',
            'Type',
            'Responsibility Center',
            'Officer',
            'Stock No.',
            'Item',
            'Description',
            'Unit',
            'Qty Issued',
            'Current Stock',
            'Unit Cost',
            'Total Amount',
            'Stock Limit',
            'Status',
        ], ',', '"', '\\');

        foreach ($items as $item) {
            fputcsv($stream, [
                $this->formatDate((string) ($item['issued_at'] ?? '')),
                (string) ($item['ris_number'] ?? ''),
                (string) ($item['request_type'] ?? ''),
                (string) ($item['division'] ?? ''),
                (string) ($item['officer_name'] ?? ''),
                (string) ($item['stock_number'] ?? ''),
                (string) ($item['item_name'] ?? ''),
                (string) ($item['description'] ?? ''),
                (string) ($item['unit'] ?? ''),
                (string) ($item['quantity_issued'] ?? 0),
                (string) ($item['current_stock'] ?? 0),
                number_format((float) ($item['unit_cost'] ?? 0), 2, '.', ''),
                number_format((float) ($item['total_amount'] ?? 0), 2, '.', ''),
                (string) ($item['stock_limit'] ?? 0),
                (string) ($item['stock_status_label'] ?? 'In Stock'),
            ], ',', '"', '\\');
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content === false ? '' : $content;
    }

    private function renderRpcppeHtml(array $assets, array $filters): string
    {
        if ($assets === []) {
            return '<div class="report-empty-state">No records matched the selected filters.</div>';
        }

        $html = '<article class="rpcppe-report-page">';
        $html .= '<table class="rpcppe-report-table">';
        $html .= '<thead><tr>';
        $html .= '<th>PAR NO</th><th>PAR DATE</th><th>ACCOUNTABLE OFFICER</th><th>TYPE</th><th>PROPERTY</th>';
        $html .= '<th>PROPERTY DESCRIPTION</th><th>PROPERTY NO</th><th>DATE ACQUIRED</th><th>PROPERTY AMOUNT</th>';
        $html .= '<th>FUND</th><th>QTY</th><th>QTY PER COUNT</th><th>SHORTAGE/OVERAGE</th><th>REMARKS</th>';
        $html .= '</tr></thead><tbody>';

        $totalAmount = 0.0;

        foreach ($assets as $asset) {
            $html .= '<tr>';
            $html .= '<td>' . escape_html((string) ($asset['par_number'] ?? '')) . '</td>';
            $html .= '<td>' . escape_html($this->formatDate((string) ($asset['par_date'] ?? ''))) . '</td>';
            $html .= '<td>' . escape_html((string) ($asset['officer_name'] ?? '')) . '</td>';
            $html .= '<td>' . escape_html((string) ($asset['property_type'] ?? 'OFFICE EQUIPMENT')) . '</td>';
            $html .= '<td>' . escape_html((string) ($asset['classification'] ?? 'SEMI')) . '</td>';
            $html .= '<td><div class="rpcppe-description">' . nl2br(escape_html($this->buildAssetDescription($asset))) . '</div></td>';
            $html .= '<td>' . escape_html((string) ($asset['property_number'] ?? '')) . '</td>';
            $html .= '<td>' . escape_html($this->formatDate((string) ($asset['date_acquired'] ?? ''))) . '</td>';
            $html .= '<td class="rpcppe-amount">' . escape_html(number_format((float) ($asset['unit_cost'] ?? 0), 2)) . '</td>';
            $html .= '<td>' . escape_html($this->normalizeFundingSourceLabel((string) ($asset['funding_source'] ?? 'NEDA'))) . '</td>';
            $html .= '<td class="rpcppe-qty">' . escape_html((string) ((int) ($asset['quantity'] ?? 1) ?: 1)) . '</td>';
            $html .= '<td class="rpcppe-qty"></td>';
            $html .= '<td class="rpcppe-qty"></td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            $totalAmount += (float) ($asset['unit_cost'] ?? 0);
        }

        $html .= '<tr class="rpcppe-total-row">';
        $html .= '<td colspan="8"><strong>TOTAL</strong></td>';
        $html .= '<td class="rpcppe-amount"><strong>' . escape_html(number_format($totalAmount, 2)) . '</strong></td>';
        $html .= '<td colspan="5"></td>';
        $html .= '</tr>';

        $html .= '</tbody></table>';
        $html .= '</article>';

        return $html;
    }

    private function renderRegSpiHtml(array $assets, array $filters): string
    {
        if ($assets === []) {
            return '<div class="report-empty-state">No records matched the selected filters.</div>';
        }

        $html = '<article class="regspi-report-page">';
        $html .= '<h2 class="regspi-title">REGISTRY OF SEMI-EXPENDABLE PROPERTY ISSUED</h2>';
        $html .= '<p class="regspi-date">Made as of ' . date('F d, Y') . '</p>';

        $html .= '<table class="regspi-report-table">';
        $html .= '<thead><tr>';
        $html .= '<th>DATE</th><th>REFERENCES</th><th>Property No.</th><th>ITEM DESCRIPTION</th>';
        $html .= '<th>Estimated Life</th><th>Issued Qty</th><th>Returned Qty</th><th>Reissued Qty</th>';
        $html .= '<th>Disposed Qty</th><th>Balance</th><th>AMOUNT</th><th>REMARKS</th>';
        $html .= '</tr></thead><tbody>';

        $totalAmount = 0.0;
        $totalBalance = 0;

        foreach ($assets as $asset) {
            $quantity = (int) ($asset['quantity'] ?? 1);
            $html .= '<tr>';
            $html .= '<td>' . escape_html($this->formatDate((string) ($asset['date_acquired'] ?? ''))) . '</td>';
            $html .= '<td>' . escape_html((string) ($asset['par_number'] ?? '')) . '</td>';
            $html .= '<td>' . escape_html((string) ($asset['property_number'] ?? '')) . '</td>';
            $html .= '<td><div class="regspi-description">' . nl2br(escape_html($this->buildAssetDescription($asset))) . '</div></td>';
            $html .= '<td class="regspi-center">3</td>';
            $html .= '<td class="regspi-qty">' . escape_html((string) $quantity) . '</td>';
            $html .= '<td class="regspi-qty"></td>';
            $html .= '<td class="regspi-qty"></td>';
            $html .= '<td class="regspi-qty"></td>';
            $html .= '<td class="regspi-qty">' . escape_html((string) $quantity) . '</td>';
            $html .= '<td class="regspi-amount">' . escape_html(number_format((float) ($asset['unit_cost'] ?? 0), 2)) . '</td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            $totalAmount += (float) ($asset['unit_cost'] ?? 0);
            $totalBalance += $quantity;
        }

        $html .= '<tr class="regspi-total-row">';
        $html .= '<td colspan="5"><strong>TOTAL</strong></td>';
        $html .= '<td colspan="4"></td>';
        $html .= '<td class="regspi-qty"><strong>' . escape_html((string) $totalBalance) . '</strong></td>';
        $html .= '<td class="regspi-amount"><strong>' . escape_html(number_format($totalAmount, 2)) . '</strong></td>';
        $html .= '<td></td>';
        $html .= '</tr>';

        $html .= '</tbody></table>';
        $html .= '</article>';

        return $html;
    }

    private function groupAssetsByPar(array $assets): array
    {
        $groups = [];

        foreach ($assets as $asset) {
            $parDate = (string) ($asset['par_date'] ?? '');
            $documentType = strtoupper(trim((string) ($asset['document_type'] ?? '')));
            if ($documentType === '') {
                $documentType = strtoupper(trim((string) ($asset['classification'] ?? ''))) === 'SEMI' ? 'ICS' : 'PAR';
            }
            $groupKey = implode('|', [
                $documentType,
                trim((string) ($asset['officer_name'] ?? '')),
                $parDate,
            ]);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'document_type' => $documentType,
                    'par_number' => (string) ($asset['par_number'] ?? 'PAR'),
                    'par_date' => $parDate,
                    'officer_name' => (string) ($asset['officer_name'] ?? ''),
                    'officer_position' => (string) ($asset['officer_position'] ?? ''),
                    'officer_unit' => (string) ($asset['officer_unit'] ?? ''),
                    'division' => (string) ($asset['division'] ?? ''),
                    'funding_source' => (string) ($asset['funding_source'] ?? ''),
                    'has_ppe' => false,
                    'has_semi' => false,
                    'items' => [],
                    'total_amount' => 0.0,
                ];
            }

            $classification = strtoupper(trim((string) ($asset['classification'] ?? '')));
            $groups[$groupKey]['items'][] = $asset;
            $groups[$groupKey]['total_amount'] += (float) ($asset['unit_cost'] ?? 0);
            $groups[$groupKey]['has_ppe'] = $groups[$groupKey]['has_ppe'] || $classification === 'PPE';
            $groups[$groupKey]['has_semi'] = $groups[$groupKey]['has_semi'] || $classification === 'SEMI';
        }

        return array_values($groups);
    }

    private function renderParHtml(array $sheets, array $filters): string
    {
        if ($sheets === []) {
            $documentType = strtoupper(trim((string) ($filters['report_type'] ?? 'PAR')));
            return '<div class="report-empty-state">No ' . escape_html($documentType === 'ICS' ? 'ICS' : 'PAR') . ' records matched the selected filters.</div>';
        }

        $html = '<div class="par-report-stack">';

        // Group sheets into pages of 3 copies each
        $pages = array_chunk($sheets, 3);

        foreach ($pages as $pageSheets) {
            $html .= '<div class="par-report-multi-copy-page">';

            foreach ($pageSheets as $sheet) {
                $html .= $this->renderParSheet($sheet);
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    private function renderParSheet(array $sheet): string
    {
        $classes = ['par-report-page', 'par-report-page--sheet'];

        if ($this->isCompactSheet($sheet)) {
            $classes[] = 'par-report-page--compact';
        }

        $html = '<article class="' . escape_html(implode(' ', $classes)) . '">';
        $html .= '<table class="par-sheet-table">';
        $html .= '<colgroup>';
        $html .= '<col style="width:7%">';
        $html .= '<col style="width:6%">';
        $html .= '<col style="width:41%">';
        $html .= '<col style="width:15%">';
        $html .= '<col style="width:15%">';
        $html .= '<col style="width:16%">';
        $html .= '</colgroup>';
        $html .= '<tbody>';
        $html .= '<tr><td colspan="6" class="par-sheet-table__title">' . escape_html($this->documentTitle($sheet)) . '</td></tr>';
        $html .= '<tr class="par-sheet-table__blank-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
        $html .= '<tr>';
        $html .= '<td colspan="4" class="par-sheet-table__meta"><strong>Entity Name :</strong> ' . escape_html(self::ENTITY_NAME) . '</td>';
        $html .= '<td class="par-sheet-table__meta-label">' . escape_html($this->documentNumberLabel($sheet)) . ' :</td>';
        $html .= '<td class="par-sheet-table__meta-value">' . escape_html($this->formatDocumentNumberForSheet((string) $sheet['par_number'], $sheet)) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td colspan="4" class="par-sheet-table__meta"><strong>Fund Cluster:</strong> <span class="par-sheet-table__line">' . escape_html($this->normalizeFundingSourceLabel((string) ($sheet['funding_source'] ?? ''))) . '</span></td>';
        $html .= '<td class="par-sheet-table__meta-label">' . escape_html($this->documentNumberLabel($sheet)) . ' Date :</td>';
        $html .= '<td class="par-sheet-table__meta-value">' . escape_html($this->formatLongDate((string) ($sheet['par_date'] ?? ''))) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th class="par-sheet-table__head">Quantity</th>';
        $html .= '<th class="par-sheet-table__head">Unit</th>';
        $html .= '<th class="par-sheet-table__head">Description</th>';
        $html .= '<th class="par-sheet-table__head">' . $this->buildSheetReferenceHeadingHtml($sheet) . '</th>';
        $html .= '<th class="par-sheet-table__head">Date<br>Acquired</th>';
        $html .= '<th class="par-sheet-table__head">Amount</th>';
        $html .= '</tr>';

        $items = $sheet['items'] ?? [];

        foreach ($items as $itemIndex => $asset) {
            $isLastItem = $itemIndex === count($items) - 1;
            $rowClass = $isLastItem ? ' class="par-sheet-table__item-row par-sheet-table__item-row--last"' : ' class="par-sheet-table__item-row"';
            $html .= '<tr' . $rowClass . '>';
            $html .= '<td class="par-sheet-table__cell par-sheet-table__cell--center">' . escape_html((string) ((int) ($asset['quantity'] ?? 1) ?: 1)) . '</td>';
            $html .= '<td class="par-sheet-table__cell par-sheet-table__cell--center">unit</td>';
            $html .= '<td class="par-sheet-table__cell"><div class="par-sheet-table__description">' . nl2br(escape_html($this->buildAssetDescription($asset))) . '</div></td>';
            $html .= '<td class="par-sheet-table__cell par-sheet-table__cell--center">' . escape_html((string) ($asset['property_number'] ?? '')) . '</td>';
            $html .= '<td class="par-sheet-table__cell par-sheet-table__cell--center">' . escape_html($this->formatAcquiredStamp($asset)) . '</td>';
            $html .= '<td class="par-sheet-table__cell par-sheet-table__cell--amount">' . escape_html(number_format((float) ($asset['unit_cost'] ?? 0), 2)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr class="par-sheet-table__blank-row par-sheet-table__signature-gap"><td colspan="6"></td></tr>';
        $html .= '<tr class="par-sheet-table__signature-label-row">';
        $html .= '<td colspan="3" class="par-sheet-table__sign-label">Received by:</td>';
        $html .= '<td colspan="3" class="par-sheet-table__sign-label">Issued by:</td>';
        $html .= '</tr>';
        $html .= '<tr class="par-sheet-table__signature-row">';
        $html .= '<td colspan="3" class="par-sheet-table__signature-cell">' . $this->renderSignatureBlock($sheet, 'received') . '</td>';
        $html .= '<td colspan="3" class="par-sheet-table__signature-cell">' . $this->renderSignatureBlock($sheet, 'issued') . '</td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        $html .= '</article>';

        return $html;
    }

    private function renderSignatureBlock(array $sheet, string $mode): string
    {
        $profile = $this->resolveSignatureProfile($sheet, $mode);
        $date = $this->formatLongDate((string) ($sheet['par_date'] ?? ''));
        $caption = $mode === 'issued'
            ? 'Signature over Printed Name of Issuing Officer'
            : 'Signature over Printed Name of Accountable Officer';

        $html = '<div class="par-sheet-signature">';
        $html .= '<div class="par-sheet-signature__name-wrap">';
        $html .= '<div class="par-sheet-signature__name">' . escape_html($profile['name']) . '</div>';
        $html .= '</div>';
        $html .= '<div class="par-sheet-signature__caption">' . escape_html($caption) . '</div>';
        $html .= '<div class="par-sheet-signature__role">' . escape_html($profile['role']) . '</div>';
        $html .= '<div class="par-sheet-signature__caption">Position/Office</div>';
        $html .= '<div class="par-sheet-signature__date">' . escape_html($date) . '</div>';
        $html .= '<div class="par-sheet-signature__caption">Date</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderParCsv(array $sheets): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return '';
        }

        foreach ($sheets as $index => $sheet) {
            $receivedProfile = $this->resolveSignatureProfile($sheet, 'received');
            $issuedProfile = $this->resolveSignatureProfile($sheet, 'issued');
            fputcsv($stream, ['', '', $this->documentTitle($sheet), '', '', ''], ',', '"', '\\');
            fputcsv($stream, [], ',', '"', '\\');
            fputcsv($stream, ['Entity Name :', self::ENTITY_NAME, '', '', $this->documentNumberLabel($sheet) . ' :', $this->formatDocumentNumberForSheet((string) $sheet['par_number'], $sheet)], ',', '"', '\\');
            fputcsv($stream, ['Fund Cluster:', $this->normalizeFundingSourceLabel((string) ($sheet['funding_source'] ?? '')), '', '', $this->documentNumberLabel($sheet) . ' Date :', $this->formatLongDate((string) ($sheet['par_date'] ?? ''))], ',', '"', '\\');
            fputcsv($stream, ['Quantity', 'Unit', 'Description', $this->buildSheetReferenceHeadingCsv($sheet), 'Date Acquired', 'Amount'], ',', '"', '\\');

            foreach ($sheet['items'] as $asset) {
                fputcsv($stream, [
                    (string) ((int) ($asset['quantity'] ?? 1) ?: 1),
                    'unit',
                    $this->buildAssetDescription($asset),
                    (string) ($asset['property_number'] ?? ''),
                    $this->formatAcquiredStamp($asset),
                    number_format((float) ($asset['unit_cost'] ?? 0), 2),
                ], ',', '"', '\\');
            }

            fputcsv($stream, [], ',', '"', '\\');
            fputcsv($stream, ['Received by:', '', '', 'Issued by:', '', ''], ',', '"', '\\');
            fputcsv($stream, ['', $receivedProfile['name'], '', '', $issuedProfile['name'], ''], ',', '"', '\\');
            fputcsv($stream, ['', 'Signature over Printed Name of Accountable Officer', '', '', 'Signature over Printed Name of Issuing Officer', ''], ',', '"', '\\');
            fputcsv($stream, ['', $receivedProfile['role'], '', '', $issuedProfile['role'], ''], ',', '"', '\\');
            fputcsv($stream, ['', 'Position/Office', '', '', 'Position/Office', ''], ',', '"', '\\');
            fputcsv($stream, ['', $this->formatLongDate((string) ($sheet['par_date'] ?? '')), '', '', $this->formatLongDate((string) ($sheet['par_date'] ?? '')), ''], ',', '"', '\\');
            fputcsv($stream, ['', 'Date', '', '', 'Date', ''], ',', '"', '\\');

            if ($index < count($sheets) - 1) {
                fputcsv($stream, [], ',', '"', '\\');
                fputcsv($stream, [], ',', '"', '\\');
            }
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content === false ? '' : $content;
    }

    private function prepareDocumentFilters(array $filters, string $documentType): array
    {
        $reportType = strtoupper(trim((string) ($filters['report_type'] ?? '')));
        $officerName = trim((string) ($filters['officer_name'] ?? ''));
        $officerId = (int) ($filters['officer_id'] ?? 0);
        $documentType = strtoupper(trim($documentType));

        if ($reportType === '') {
            $reportType = $documentType;
        }

        if (!in_array($reportType, ['PAR', 'ICS'], true) || $reportType !== $documentType) {
            throw new ValidationException('Select a valid document report type first.', [
                'report_type' => 'Choose either PAR or ICS for this workflow.',
            ]);
        }

        if ($officerId <= 0 && $officerName === '') {
            throw new ValidationException('Select an accountable officer first.', [
                'officer_name' => 'Choose an accountable officer before generating the PAR report.',
            ]);
        }

        if ($officerName === '' && $officerId > 0) {
            $officer = $this->officerService->findById($officerId);

            if ($officer !== null) {
                $filters['officer_name'] = (string) ($officer['name'] ?? '');
                if (trim((string) ($filters['division'] ?? '')) === '') {
                    $filters['division'] = (string) ($officer['division'] ?? '');
                }
            }
        }

        $filters['report_type'] = $documentType;
        $filters['document_type'] = $documentType;
        $filters['classification'] = $documentType === 'ICS' ? 'SEMI' : 'PPE';

        return $filters;
    }

    private function prepareSpiFilters(array $filters): array
    {
        // SPI filters: classification, funding_source, asset_type, date_from, date_to
        $filters['classification'] = trim((string) ($filters['classification'] ?? ''));
        $filters['funding_source'] = trim((string) ($filters['funding_source'] ?? ''));
        $filters['asset_type'] = trim((string) ($filters['asset_type'] ?? ''));
        $filters['date_from'] = trim((string) ($filters['date_from'] ?? ''));
        $filters['date_to'] = trim((string) ($filters['date_to'] ?? ''));

        return $filters;
    }

    private function prepareInventoryFilters(array $filters): array
    {
        $filters['date_from'] = trim((string) ($filters['date_from'] ?? ''));
        $filters['date_to'] = trim((string) ($filters['date_to'] ?? ''));
        $filters['officer_id'] = (int) ($filters['officer_id'] ?? 0);
        $filters['inventory_item_id'] = (int) ($filters['inventory_item_id'] ?? $filters['item_id'] ?? 0);

        if ($filters['officer_id'] > 0) {
            $officer = $this->officerService->findById($filters['officer_id']);

            if ($officer !== null) {
                $filters['officer_name'] = (string) ($officer['name'] ?? '');
            }
        }

        if ($filters['inventory_item_id'] > 0) {
            $item = $this->inventoryService->findById($filters['inventory_item_id']);

            if ($item !== null) {
                $filters['item_label'] = (string) ($item['item_name'] ?? '');
            }
        }

        return $filters;
    }

    private function buildAssetDescription(array $asset): string
    {
        $description = (string) ($asset['description'] ?? '');
        $description = str_replace(["\r\n", "\r"], "\n", $description);
        $description = rtrim($description, "\n");

        $parts = array_filter([
            trim((string) ($asset['property_name'] ?? '')),
            $description,
            trim((string) ($asset['property_id'] ?? '')) !== ''
                ? 'Serial No.: ' . trim((string) ($asset['property_id'] ?? ''))
                : '',
        ], static fn (string $value): bool => $value !== '');

        return implode("\n", $parts);
    }

    private function formatParNumberForSheet(string $parNumber): string
    {
        return preg_replace('/^PAR-/', '', $parNumber) ?: $parNumber;
    }

    private function formatAcquiredStamp(array $asset): string
    {
        $fundingSource = strtoupper(trim((string) ($asset['funding_source'] ?? '')));
        $prefix = str_contains($fundingSource, 'RDC') ? 'RDC' : 'DEPDev';

        return trim($prefix . ' ' . $this->formatDate((string) ($asset['date_acquired'] ?? '')));
    }

    private function buildCsvFilename(array $sheets, string $defaultPrefix = 'PAR'): string
    {
        if ($sheets !== []) {
            $firstNumber = $this->formatDocumentNumberForSheet((string) ($sheets[0]['par_number'] ?? 'PAR'), $sheets[0]);
            $prefix = strtoupper(trim((string) ($sheets[0]['document_type'] ?? 'PAR')));
            return $prefix . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $firstNumber) . '.csv';
        }

        return $defaultPrefix . '_report_' . date('Ymd_His') . '.csv';
    }

    private function buildSpreadsheetFilename(array $sheets, string $defaultPrefix = 'PAR'): string
    {
        if ($sheets !== []) {
            $firstNumber = $this->formatDocumentNumberForSheet((string) ($sheets[0]['par_number'] ?? 'PAR'), $sheets[0]);
            $prefix = strtoupper(trim((string) ($sheets[0]['document_type'] ?? 'PAR')));
            return $prefix . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $firstNumber) . '.xls';
        }

        return $defaultPrefix . '_report_' . date('Ymd_His') . '.xls';
    }

    private function buildInventorySpreadsheetFilename(array $filters): string
    {
        $segments = ['INVENTORY'];

        if (!empty($filters['officer_name'])) {
            $segments[] = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $filters['officer_name']) ?: 'Officer';
        }

        if (!empty($filters['item_label'])) {
            $segments[] = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $filters['item_label']) ?: 'Item';
        }

        $segments[] = date('Ymd_His');

        return implode('_', $segments) . '.csv';
    }

    private function renderParSpreadsheetWorkbook(array $sheets): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<?mso-application progid="Excel.Sheet"?>';
        $xml[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml[] = ' xmlns:o="urn:schemas-microsoft-com:office:office"';
        $xml[] = ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
        $xml[] = ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml[] = ' xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml[] = '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">';
        $xml[] = '<Author>Asset Management System</Author>';
        $xml[] = '<Created>' . gmdate('Y-m-d\TH:i:s\Z') . '</Created>';
        $xml[] = '</DocumentProperties>';
        $xml[] = '<ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">';
        $xml[] = '<ProtectStructure>False</ProtectStructure>';
        $xml[] = '<ProtectWindows>False</ProtectWindows>';
        $xml[] = '</ExcelWorkbook>';
        $xml[] = $this->renderSpreadsheetStylesXml();

        if ($sheets === []) {
            $xml[] = $this->renderEmptySpreadsheetWorksheetXml();
        } else {
            foreach ($sheets as $index => $sheet) {
                $xml[] = $this->renderSpreadsheetWorksheetXml($sheet, $index + 1);
            }
        }

        $xml[] = '</Workbook>';

        return implode('', $xml);
    }

    private function renderSpreadsheetStylesXml(): string
    {
        return '<Styles>'
            . '<Style ss:ID="Default" ss:Name="Normal">'
            . '<Alignment ss:Vertical="Center"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="Title">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="16" ss:Bold="1"/>'
            . '</Style>'
            . '<Style ss:ID="MetaText">'
            . '<Alignment ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1"/>'
            . '</Style>'
            . '<Style ss:ID="MetaLabelCenter">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1"/>'
            . '</Style>'
            . '<Style ss:ID="MetaValueCenter">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1"/>'
            . '</Style>'
            . '<Style ss:ID="Header">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetAllBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1"/>'
            . '<Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/>'
            . '</Style>'
            . '<Style ss:ID="CellCenter">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetAllBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="CellCenterNoBottom">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetBordersWithoutBottomXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="CellText">'
            . '<Alignment ss:Horizontal="Left" ss:Vertical="Top" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetAllBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="CellTextNoBottom">'
            . '<Alignment ss:Horizontal="Left" ss:Vertical="Top" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetBordersWithoutBottomXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="CellAmount">'
            . '<Alignment ss:Horizontal="Right" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetAllBordersXml() . '</Borders>'
            . '<NumberFormat ss:Format="#,##0.00"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="CellAmountNoBottom">'
            . '<Alignment ss:Horizontal="Right" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetBordersWithoutBottomXml() . '</Borders>'
            . '<NumberFormat ss:Format="#,##0.00"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureSpacer">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetVerticalBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureLabelLeft">'
            . '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetVerticalBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureLabelRight">'
            . '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetRightBorderXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureNameLeft">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetVerticalBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1" ss:Underline="Single"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureNameRight">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetRightBorderXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Bold="1" ss:Underline="Single"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureCaptionLeft">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetVerticalBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="10"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureCaptionRight">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetRightBorderXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="10"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureRoleLeft">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetVerticalBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Underline="Single"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureRoleRight">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetRightBorderXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Underline="Single"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureDateLeft">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetVerticalBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Underline="Single"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureDateRight">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>'
            . '<Borders>' . $this->spreadsheetRightBorderXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="11" ss:Underline="Single"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureCaptionLeftBottom">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetVerticalBottomBordersXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="10"/>'
            . '</Style>'
            . '<Style ss:ID="SignatureCaptionRightBottom">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Borders>' . $this->spreadsheetRightBottomBorderXml() . '</Borders>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="10"/>'
            . '</Style>'
            . '<Style ss:ID="Note">'
            . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'
            . '<Font ss:FontName="Tahoma" x:Family="Swiss" ss:Size="12" ss:Bold="1"/>'
            . '</Style>'
            . '</Styles>';
    }

    private function renderEmptySpreadsheetWorksheetXml(): string
    {
        $rows = [
            $this->spreadsheetRowXml([
                $this->spreadsheetCellXml('PROPERTY ACKNOWLEDGMENT RECEIPT', 'Title', 'String', 5),
            ], 28),
            $this->spreadsheetRowXml([
                $this->spreadsheetCellXml('No PAR records matched the selected filters.', 'Note', 'String', 5),
            ], 24),
        ];

        return '<Worksheet ss:Name="PAR">'
            . '<Names><NamedRange ss:Name="Print_Area" ss:RefersTo="=\'PAR\'!R1C1:R2C6"/></Names>'
            . '<Table ss:ExpandedColumnCount="6" ss:ExpandedRowCount="2" x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="18">'
            . $this->renderSpreadsheetColumnsXml()
            . implode('', $rows)
            . '</Table>'
            . $this->renderSpreadsheetWorksheetOptionsXml()
            . '</Worksheet>';
    }

    private function renderSpreadsheetWorksheetXml(array $sheet, int $sheetIndex): string
    {
        $sheetName = str_pad((string) $sheetIndex, 3, '0', STR_PAD_LEFT);
        $receivedProfile = $this->resolveSignatureProfile($sheet, 'received');
        $issuedProfile = $this->resolveSignatureProfile($sheet, 'issued');
        $rows = [];

        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml($this->documentTitle($sheet), 'Title', 'String', 5),
        ], 30);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('Entity Name : ' . self::ENTITY_NAME, 'MetaText', 'String', 3),
            $this->spreadsheetCellXml($this->documentNumberLabel($sheet) . ' :', 'MetaLabelCenter'),
            $this->spreadsheetCellXml($this->formatDocumentNumberForSheet((string) ($sheet['par_number'] ?? 'PAR'), $sheet), 'MetaValueCenter'),
        ], 20);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('Fund Cluster: ' . $this->normalizeFundingSourceLabel((string) ($sheet['funding_source'] ?? '')), 'MetaText', 'String', 3),
            $this->spreadsheetCellXml($this->documentNumberLabel($sheet) . ' Date :', 'MetaLabelCenter'),
            $this->spreadsheetCellXml($this->formatLongDate((string) ($sheet['par_date'] ?? '')), 'MetaValueCenter'),
        ], 20);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('Quantity', 'Header'),
            $this->spreadsheetCellXml('Unit', 'Header'),
            $this->spreadsheetCellXml('Description', 'Header'),
            $this->spreadsheetCellXml($this->buildSheetReferenceHeadingCsv($sheet), 'Header'),
            $this->spreadsheetCellXml("Date\nAcquired", 'Header'),
            $this->spreadsheetCellXml('Amount', 'Header'),
        ], 28);

        $items = $sheet['items'] ?? [];

        foreach ($items as $itemIndex => $asset) {
            $isLastItem = $itemIndex === count($items) - 1;
            $centerStyle = $isLastItem ? 'CellCenterNoBottom' : 'CellCenter';
            $textStyle = $isLastItem ? 'CellTextNoBottom' : 'CellText';
            $amountStyle = $isLastItem ? 'CellAmountNoBottom' : 'CellAmount';
            $rowHeight = $this->estimateSpreadsheetDescriptionRowHeight($this->buildAssetDescription($asset));

            $rows[] = $this->spreadsheetRowXml([
                $this->spreadsheetCellXml((string) ((int) ($asset['quantity'] ?? 1) ?: 1), $centerStyle, 'Number'),
                $this->spreadsheetCellXml('unit', $centerStyle),
                $this->spreadsheetCellXml($this->buildAssetDescription($asset), $textStyle),
                $this->spreadsheetCellXml((string) ($asset['property_number'] ?? ''), $centerStyle),
                $this->spreadsheetCellXml($this->formatAcquiredStamp($asset), $centerStyle),
                $this->spreadsheetCellXml(number_format((float) ($asset['unit_cost'] ?? 0), 2, '.', ''), $amountStyle, 'Number'),
            ], $rowHeight);
        }

        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('', 'SignatureSpacer', 'String', 5),
        ], 8);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('Received by:', 'SignatureLabelLeft', 'String', 2),
            $this->spreadsheetCellXml('Issued by:', 'SignatureLabelRight', 'String', 2),
        ], 18);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml($receivedProfile['name'], 'SignatureNameLeft', 'String', 2),
            $this->spreadsheetCellXml($issuedProfile['name'], 'SignatureNameRight', 'String', 2),
        ], 20);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('Signature over Printed Name of Accountable Officer', 'SignatureCaptionLeft', 'String', 2),
            $this->spreadsheetCellXml('Signature over Printed Name of Issuing Officer', 'SignatureCaptionRight', 'String', 2),
        ], 18);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml($receivedProfile['role'], 'SignatureRoleLeft', 'String', 2),
            $this->spreadsheetCellXml($issuedProfile['role'], 'SignatureRoleRight', 'String', 2),
        ]);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('Position/Office', 'SignatureCaptionLeft', 'String', 2),
            $this->spreadsheetCellXml('Position/Office', 'SignatureCaptionRight', 'String', 2),
        ], 18);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml($this->formatLongDate((string) ($sheet['par_date'] ?? '')), 'SignatureDateLeft', 'String', 2),
            $this->spreadsheetCellXml($this->formatLongDate((string) ($sheet['par_date'] ?? '')), 'SignatureDateRight', 'String', 2),
        ], 18);
        $rows[] = $this->spreadsheetRowXml([
            $this->spreadsheetCellXml('Date', 'SignatureCaptionLeftBottom', 'String', 2),
            $this->spreadsheetCellXml('Date', 'SignatureCaptionRightBottom', 'String', 2),
        ], 18);

        $rowCount = count($rows);

        return '<Worksheet ss:Name="' . $this->spreadsheetXmlEscape($sheetName) . '">'
            . '<Names><NamedRange ss:Name="Print_Area" ss:RefersTo="=\'' . $this->spreadsheetXmlEscape($sheetName) . '\'!R1C1:R' . $rowCount . 'C6"/></Names>'
            . '<Table ss:ExpandedColumnCount="6" ss:ExpandedRowCount="' . $rowCount . '" x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="18">'
            . $this->renderSpreadsheetColumnsXml()
            . implode('', $rows)
            . '</Table>'
            . $this->renderSpreadsheetWorksheetOptionsXml($this->isCompactSheet($sheet))
            . '</Worksheet>';
    }

    private function renderSpreadsheetColumnsXml(): string
    {
        return '<Column ss:AutoFitWidth="0" ss:Width="58"/>'
            . '<Column ss:AutoFitWidth="0" ss:Width="52"/>'
            . '<Column ss:AutoFitWidth="0" ss:Width="420"/>'
            . '<Column ss:AutoFitWidth="0" ss:Width="128"/>'
            . '<Column ss:AutoFitWidth="0" ss:Width="112"/>'
            . '<Column ss:AutoFitWidth="0" ss:Width="110"/>';
    }

    private function renderSpreadsheetWorksheetOptionsXml(bool $compact = false): string
    {
        $pageMargins = $compact
            ? '<PageMargins x:Bottom="0.18" x:Left="0.22" x:Right="0.22" x:Top="0.18"/>'
            : '<PageMargins x:Bottom="0.3" x:Left="0.25" x:Right="0.25" x:Top="0.3"/>';
        $zoom = $compact ? '110' : '90';

        return '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'
            . '<PageSetup>'
            . '<Layout x:Orientation="Landscape"/>'
            . '<Header x:Margin="0.15"/>'
            . '<Footer x:Margin="0.15"/>'
            . $pageMargins
            . '</PageSetup>'
            . '<FitToPage/>'
            . '<Print>'
            . '<ValidPrinterInfo/>'
            . '<PaperSizeIndex>9</PaperSizeIndex>'
            . '<FitWidth>1</FitWidth>'
            . '<FitHeight>0</FitHeight>'
            . '<HorizontalResolution>600</HorizontalResolution>'
            . '<VerticalResolution>600</VerticalResolution>'
            . '</Print>'
            . '<Zoom>' . $zoom . '</Zoom>'
            . '<ProtectObjects>False</ProtectObjects>'
            . '<ProtectScenarios>False</ProtectScenarios>'
            . '</WorksheetOptions>';
    }

    private function isCompactSheet(array $sheet): bool
    {
        return count($sheet['items'] ?? []) <= 2;
    }

    private function spreadsheetRowXml(array $cells, ?float $height = null): string
    {
        $attributes = ' ss:AutoFitHeight="' . ($height === null ? '1' : '0') . '"';

        if ($height !== null) {
            $attributes .= ' ss:Height="' . rtrim(rtrim(sprintf('%.2F', $height), '0'), '.') . '"';
        }

        return '<Row' . $attributes . '>' . implode('', $cells) . '</Row>';
    }

    private function spreadsheetCellXml(
        string $value,
        string $styleId,
        string $type = 'String',
        int $mergeAcross = 0
    ): string {
        $attributes = ' ss:StyleID="' . $this->spreadsheetXmlEscape($styleId) . '"';

        if ($mergeAcross > 0) {
            $attributes .= ' ss:MergeAcross="' . $mergeAcross . '"';
        }

        if ($value === '') {
            return '<Cell' . $attributes . '/>';
        }

        return '<Cell' . $attributes . '><Data ss:Type="' . $this->spreadsheetXmlEscape($type) . '">'
            . $this->spreadsheetXmlEscape($value)
            . '</Data></Cell>';
    }

    private function spreadsheetAllBordersXml(): string
    {
        return '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
    }

    private function spreadsheetBordersWithoutBottomXml(): string
    {
        return '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
    }

    private function spreadsheetVerticalBordersXml(): string
    {
        return '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
    }

    private function spreadsheetVerticalBottomBordersXml(): string
    {
        return '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
    }

    private function spreadsheetRightBorderXml(): string
    {
        return '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
    }

    private function spreadsheetRightBottomBorderXml(): string
    {
        return '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
    }

    private function spreadsheetBottomBorderXml(): string
    {
        return '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
    }

    private function estimateSpreadsheetDescriptionRowHeight(string $description): float
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $description);
        $lines = preg_split('/\n/', $normalized) ?: [''];
        $visualLineCount = 0;

        foreach ($lines as $line) {
            $expandedLine = str_replace("\t", '    ', $line);
            $length = function_exists('mb_strlen')
                ? mb_strlen($expandedLine)
                : strlen($expandedLine);
            $visualLineCount += max(1, (int) ceil($length / 42));
        }

        return max(24.0, min(220.0, 10.5 + ($visualLineCount * 14.0)));
    }

    private function spreadsheetXmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function formatDate(string $value): string
    {
        if ($value === '' || strtotime($value) === false) {
            return $value;
        }

        return date('n/j/Y', strtotime($value));
    }

    private function formatLongDate(string $value): string
    {
        if ($value === '' || strtotime($value) === false) {
            return $value;
        }

        return date('F j, Y', strtotime($value));
    }

    private function documentType(array $sheet): string
    {
        $documentType = strtoupper(trim((string) ($sheet['document_type'] ?? '')));

        if ($documentType !== '') {
            return $documentType;
        }

        return !empty($sheet['has_semi']) && empty($sheet['has_ppe']) ? 'ICS' : 'PAR';
    }

    private function documentTitle(array $sheet): string
    {
        return $this->documentType($sheet) === 'ICS'
            ? 'INVENTORY CUSTODIAN SLIP'
            : 'PROPERTY ACKNOWLEDGMENT RECEIPT';
    }

    private function documentNumberLabel(array $sheet): string
    {
        return $this->documentType($sheet) === 'ICS' ? 'ICS No.' : 'PAR No.';
    }

    private function buildOfficerOfficeLabel(array $sheet): string
    {
        $parts = array_values(array_filter([
            $this->normalizeOfficerProfilePart($sheet['officer_position'] ?? ''),
            $this->normalizeOfficerProfilePart($sheet['officer_unit'] ?? ''),
            $this->normalizeOfficerProfilePart($sheet['division'] ?? ''),
        ], static fn (string $value): bool => $value !== ''));

        if ($parts === []) {
            return '';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0] . "\n" . implode(' | ', array_slice($parts, 1));
    }

    private function buildSignatureOfficeLabel(array $sheet): string
    {
        $position = $this->abbreviatePosition((string) ($sheet['officer_position'] ?? ''));
        $division = $this->normalizeOfficerProfilePart($sheet['division'] ?? '');

        if ($position !== '' && $division !== '') {
            return $position . ' - ' . $division;
        }

        return $position !== '' ? $position : $division;
    }

    private function resolveSignatureProfile(array $sheet, string $mode): array
    {
        if ($mode === 'issued') {
            return $this->resolveIssuedByProfile();
        }

        return [
            'name' => (string) ($sheet['officer_name'] ?? ''),
            'role' => $this->buildSignatureOfficeLabel($sheet),
        ];
    }

    private function resolveIssuedByProfile(): array
    {
        if ($this->issuedByProfile !== null) {
            return $this->issuedByProfile;
        }

        $officer = $this->officerService->findByName(self::PAR_ISSUED_BY_NAME);

        if ($officer === null) {
            $this->issuedByProfile = [
                'name' => self::PAR_ISSUED_BY_NAME,
                'role' => '',
            ];

            return $this->issuedByProfile;
        }

        $this->issuedByProfile = [
            'name' => (string) ($officer['name'] ?? self::PAR_ISSUED_BY_NAME),
            'role' => $this->buildSignatureOfficeLabel([
                'officer_position' => (string) ($officer['position'] ?? ''),
                'officer_unit' => (string) ($officer['unit'] ?? ''),
                'division' => (string) ($officer['division'] ?? ''),
            ]),
        ];

        return $this->issuedByProfile;
    }

    private function buildSheetReferenceHeadingHtml(array $sheet): string
    {
        if ($this->documentType($sheet) === 'ICS' || (!empty($sheet['has_semi']) && empty($sheet['has_ppe']))) {
            return 'Stock<br>No.';
        }

        if (!empty($sheet['has_semi']) && !empty($sheet['has_ppe'])) {
            return 'Property /<br>Stock No.';
        }

        return 'Property<br>Number';
    }

    private function buildSheetReferenceHeadingCsv(array $sheet): string
    {
        if ($this->documentType($sheet) === 'ICS' || (!empty($sheet['has_semi']) && empty($sheet['has_ppe']))) {
            return 'Stock No.';
        }

        if (!empty($sheet['has_semi']) && !empty($sheet['has_ppe'])) {
            return 'Property / Stock No.';
        }

        return 'Property Number';
    }

    private function formatDocumentNumberForSheet(string $documentNumber, array $sheet): string
    {
        $prefix = $this->documentType($sheet) . '-';
        return preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $documentNumber) ?: $documentNumber;
    }

    private function normalizeOfficerProfilePart(mixed $value): string
    {
        $normalized = trim((string) $value);

        if (in_array($normalized, ['', '-', '--', '---', 'N/A', 'NA'], true)) {
            return '';
        }

        return $normalized;
    }

    private function abbreviatePosition(string $position): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $position) ?? $position);

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/\(([^)]+)\)\s*$/', $normalized, $matches) === 1) {
            return trim($matches[1]);
        }

        $upper = strtoupper($normalized);

        foreach (self::POSITION_ABBREVIATIONS as $full => $abbr) {
            if ($upper === $full) {
                return $abbr;
            }

            if (str_starts_with($upper, $full)) {
                return $abbr . substr($normalized, strlen($full));
            }
        }

        return $normalized;
    }

    private function normalizeFundingSourceLabel(string $value): string
    {
        $normalized = trim($value);

        return match ($normalized) {
            'DEPDev IX', 'NEDA/DEPDev IX' => 'DEPDev',
            default => $normalized,
        };
    }
}
