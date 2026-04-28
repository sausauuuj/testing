<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Support\Logger;
use PDO;
use Throwable;

final class InventoryService
{
    public const REQUEST_TYPES = ['RSMI', 'OSMI'];
    public const ITEM_TYPES = self::REQUEST_TYPES;
    public const CATEGORY_MAP = [
        'RSMI' => [
            'A - Inks & Ribbons',
            'B - Bond Papers',
            'C - Envelopes',
            'D - Folders',
            'E - Record Books',
            'I - Janitorial Use',
            "J - Auditors' Items",
            'K - Discs',
            'L - Ring Binders',
            'M - Battery',
            'N - Flourescent Lights',
            'O - Whiteboards',
            'P - Flash Drive, Computer accessories, etc.',
            '0 - Cork Boards',
            'R - Calculators',
            'S - Tokens, Pins & Frames',
            'T - Box, Bag, etc.',
            'U - Others',
        ],
        'OSMI' => [
            'A - Equipment parts',
            'B - Tokens and supplies',
            'C - Common office Use',
            'D - Office Vehicle Tools',
            'E - Equipment',
            'F - Construction Materials',
            'G - Fitness and Wellness Activities',
            'H - Advocacy Materials',
            'I - Others',
        ],
    ];
    public const UNITS = ['pc', 'box', 'ream', 'roll', 'pack', 'set', 'bottle'];
    public const STATUS_LABELS = [
        'HIGH' => 'HIGH',
        'MEDIUM' => 'MEDIUM',
        'NEAR' => 'MEDIUM',
        'LOW' => 'LOW',
        'AT_LIMIT' => 'HIGH',
        'NORMAL' => 'HIGH',
    ];

    private PDO $db;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::connection();
    }

    public function listItems(array $filters = [], int $limit = 250): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $requestType = strtoupper(trim((string) ($filters['request_type'] ?? ($filters['item_type'] ?? ''))));
        $category = trim((string) ($filters['category'] ?? ''));
        $status = strtoupper(trim((string) ($filters['stock_status'] ?? '')));
        if ($status === 'MEDIUM') {
            $status = 'NEAR';
        }
        $division = trim((string) ($filters['division'] ?? ''));
        $officerId = (int) ($filters['officer_id'] ?? 0);
        $itemId = (int) ($filters['inventory_item_id'] ?? $filters['item_id'] ?? 0);
        $dateFrom = $this->normalizeIssueDate((string) ($filters['date_from'] ?? ''));
        $dateTo = $this->normalizeIssueDate((string) ($filters['date_to'] ?? ''));

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(ii.item_code LIKE :search_item_code
                OR ii.ris_number LIKE :search_ris_number
                OR ii.stock_number LIKE :search_stock_number
                OR ii.item_name LIKE :search_item_name
                OR ii.description LIKE :search_description
                OR ao.name LIKE :search_officer_name)';
            $searchPattern = '%' . $search . '%';
            $params['search_item_code'] = $searchPattern;
            $params['search_ris_number'] = $searchPattern;
            $params['search_stock_number'] = $searchPattern;
            $params['search_item_name'] = $searchPattern;
            $params['search_description'] = $searchPattern;
            $params['search_officer_name'] = $searchPattern;
        }

        if (in_array($requestType, self::REQUEST_TYPES, true)) {
            $where[] = 'ii.request_type = :request_type';
            $params['request_type'] = $requestType;
        }

        if ($category !== '') {
            $where[] = 'ii.category LIKE :category';
            $params['category'] = '%' . $category . '%';
        }

        if ($division !== '') {
            $where[] = 'd.code = :division';
            $params['division'] = $division;
        }

        if ($officerId > 0) {
            $where[] = 'ii.officer_id = :officer_id';
            $params['officer_id'] = $officerId;
        }

        if ($itemId > 0) {
            $where[] = 'ii.inventory_item_id = :inventory_item_id';
            $params['inventory_item_id'] = $itemId;
        }

        if ($dateFrom !== '') {
            $where[] = 'ii.issued_at >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = 'ii.issued_at <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $statement = $this->db->prepare(
            'SELECT
                ii.inventory_item_id,
                ii.item_code,
                ii.request_type,
                ii.funding_source,
                ii.category,
                ii.ris_number,
                ii.stock_number,
                ii.item_name,
                ii.item_type,
                ii.unit,
                ii.division_id,
                ii.officer_id,
                ii.quantity_issued,
                ii.current_stock,
                ii.stock_limit,
                ii.low_stock_threshold,
                ii.unit_cost,
                ii.total_amount,
                ii.issued_at,
                ii.description,
                ii.allocations_json,
                ii.created_at,
                ii.updated_at,
                d.code AS division,
                d.label AS division_label,
                ao.name AS officer_name,
                ao.position AS officer_position,
                ao.unit AS officer_unit
             FROM inventory_items ii
             LEFT JOIN divisions d ON d.division_id = ii.division_id
             LEFT JOIN accountable_officers ao ON ao.officer_id = ii.officer_id' .
             ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where)) .
             ' ORDER BY ii.ris_number ASC,
                      COALESCE(ii.issued_at, DATE(ii.created_at)) ASC,
                      ii.inventory_item_id ASC
               LIMIT ' . (int) $limit
        );
        $statement->execute($params);

        $items = array_map(fn (array $row): array => $this->hydrateItem($row), $statement->fetchAll());

        if ($status !== '' && isset(self::STATUS_LABELS[$status])) {
            $items = array_values(array_filter(
                $items,
                static fn (array $item): bool => strtoupper((string) ($item['stock_status_code'] ?? '')) === $status
            ));
        }

        return $items;
    }

    public function preview(array $payload): array
    {
        $itemId = (int) ($payload['inventory_item_id'] ?? 0);
        $existing = $itemId > 0 ? $this->findById($itemId) : null;
        $issuedAt = $this->normalizeIssueDate((string) ($payload['issued_at'] ?? ''));
        $requestType = strtoupper(trim((string) ($payload['request_type'] ?? ($existing['request_type'] ?? ''))));
        $category = trim((string) ($payload['category'] ?? ''));
        $itemName = trim((string) ($payload['item_name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $quantityIssued = max(0, (int) ($payload['quantity_issued'] ?? 0));
        $unitCost = $this->normalizeMoney($payload['unit_cost'] ?? 0);
        $resolvedCategory = $this->resolveCategoryValue($requestType, $category);
        $stockNumber = '';

        if ($existing !== null
            && $resolvedCategory !== ''
            && strcasecmp(trim((string) ($existing['category'] ?? '')), $resolvedCategory) === 0) {
            $stockNumber = trim((string) ($existing['stock_number'] ?? ''));
        }

        if ($stockNumber === '' && $resolvedCategory !== '') {
            $stockNumber = $this->generateStockNumber($requestType, $resolvedCategory, $itemName, $description, $itemId);
        }

        return [
            'ris_number' => trim((string) ($existing['ris_number'] ?? '')) !== ''
                ? (string) $existing['ris_number']
                : ($issuedAt !== '' ? $this->nextRisNumber($issuedAt, $itemId) : ''),
            'stock_number' => $stockNumber,
            'category' => $resolvedCategory,
            'total_amount' => round($quantityIssued * $unitCost, 2),
        ];
    }

    public function add(array $payload): array
    {
        $data = $this->validateItemPayload($payload);

        try {
            $this->db->beginTransaction();

            $statement = $this->db->prepare(
                'INSERT INTO inventory_items (
                    item_code,
                    request_type,
                    funding_source,
                    category,
                    ris_number,
                    stock_number,
                    item_name,
                    item_type,
                    unit,
                    division_id,
                    officer_id,
                    quantity_issued,
                    current_stock,
                    stock_limit,
                    low_stock_threshold,
                    unit_cost,
                    total_amount,
                    issued_at,
                    description,
                    allocations_json
                 ) VALUES (
                    :item_code,
                    :request_type,
                    :funding_source,
                    :category,
                    :ris_number,
                    :stock_number,
                    :item_name,
                    :item_type,
                    :unit,
                    :division_id,
                    :officer_id,
                    :quantity_issued,
                    :current_stock,
                    :stock_limit,
                    :low_stock_threshold,
                    :unit_cost,
                    :total_amount,
                    :issued_at,
                    :description,
                    :allocations_json
                 )'
            );
            $statement->execute([
                'item_code' => $this->nextItemCode(),
                'request_type' => $data['request_type'],
                'funding_source' => $data['funding_source'],
                'category' => $data['category'],
                'ris_number' => $this->nextRisNumber($data['issued_at']),
                'stock_number' => $data['stock_number'],
                'item_name' => $data['item_name'],
                'item_type' => $data['request_type'],
                'unit' => $data['unit'],
                'division_id' => $data['division_id'],
                'officer_id' => $data['officer_id'],
                'quantity_issued' => $data['quantity_issued'],
                'current_stock' => $data['quantity_issued'],
                'stock_limit' => $data['stock_limit'],
                'low_stock_threshold' => $data['low_stock_threshold'],
                'unit_cost' => $data['unit_cost'],
                'total_amount' => $data['total_amount'],
                'issued_at' => $data['issued_at'],
                'description' => $data['description'],
                'allocations_json' => $data['allocations_json'],
            ]);

            $itemId = (int) $this->db->lastInsertId();
            $item = $this->findById($itemId);

            if ($item === null) {
                throw new ValidationException('Unable to load the saved inventory record.');
            }

            $this->recordMovement(
                $itemId,
                'INITIAL',
                $data['quantity_issued'],
                0,
                $data['quantity_issued'],
                $data['issued_at'],
                sprintf(
                    '%s issuance saved under RIS %s.',
                    $data['request_type'],
                    (string) ($item['ris_number'] ?? '')
                )
            );

            $this->db->commit();

            return $this->findById($itemId) ?? $item;
        } catch (Throwable $throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            Logger::error('Unable to save inventory item.', [
                'exception' => $throwable->getMessage(),
                'payload' => $payload,
            ]);

            throw $throwable;
        }
    }

    public function update(int $itemId, array $payload): array
    {
        $existing = $this->findById($itemId);

        if ($existing === null) {
            throw new ValidationException('The selected inventory item could not be found.', [
                'inventory_item_id' => 'Choose a valid inventory item.',
            ]);
        }

        $data = $this->validateItemPayload($payload);

        if (!$this->hasItemChanges($existing, $data)) {
            throw new ValidationException('No changes were made to this inventory item.', [
                'inventory_item_id' => 'Update at least one field before saving.',
            ]);
        }

        $statement = $this->db->prepare(
            'UPDATE inventory_items
             SET request_type = :request_type,
                 funding_source = :funding_source,
                 category = :category,
                 ris_number = :ris_number,
                 stock_number = :stock_number,
                 item_name = :item_name,
                 item_type = :item_type,
                 unit = :unit,
                 division_id = :division_id,
                 officer_id = :officer_id,
                 quantity_issued = :quantity_issued,
                 stock_limit = :stock_limit,
                 low_stock_threshold = :low_stock_threshold,
                 unit_cost = :unit_cost,
                 total_amount = :total_amount,
                 issued_at = :issued_at,
                 description = :description,
                 allocations_json = :allocations_json
             WHERE inventory_item_id = :inventory_item_id'
        );
        $statement->execute([
            'request_type' => $data['request_type'],
            'funding_source' => $data['funding_source'],
            'category' => $data['category'],
            'ris_number' => trim((string) ($existing['ris_number'] ?? '')) !== ''
                ? (string) $existing['ris_number']
                : $this->nextRisNumber($data['issued_at'], $itemId),
            'stock_number' => $data['stock_number'],
            'item_name' => $data['item_name'],
            'item_type' => $data['request_type'],
            'unit' => $data['unit'],
            'division_id' => $data['division_id'],
            'officer_id' => $data['officer_id'],
            'quantity_issued' => $data['quantity_issued'],
            'stock_limit' => $data['stock_limit'],
            'low_stock_threshold' => $data['low_stock_threshold'],
            'unit_cost' => $data['unit_cost'],
            'total_amount' => $data['total_amount'],
            'issued_at' => $data['issued_at'],
            'description' => $data['description'],
            'allocations_json' => $data['allocations_json'],
            'inventory_item_id' => $itemId,
        ]);

        return $this->findById($itemId) ?? $existing;
    }

    public function delete(int $itemId): array
    {
        $item = $this->findById($itemId);

        if ($item === null) {
            throw new ValidationException('The selected inventory item could not be found.', [
                'inventory_item_id' => 'Choose a valid inventory item.',
            ]);
        }

        $statement = $this->db->prepare(
            'DELETE FROM inventory_items
             WHERE inventory_item_id = :inventory_item_id'
        );
        $statement->execute([
            'inventory_item_id' => $itemId,
        ]);

        return $item;
    }

    public function adjustStock(int $itemId, array $payload): array
    {
        $item = $this->findById($itemId);

        if ($item === null) {
            throw new ValidationException('The selected inventory item could not be found.', [
                'inventory_item_id' => 'Choose a valid inventory item.',
            ]);
        }

        $movementType = strtoupper(trim((string) ($payload['movement_type'] ?? '')));
        $quantity = (int) ($payload['quantity'] ?? 0);
        $notes = trim((string) ($payload['notes'] ?? ''));
        $division = strtoupper(trim((string) ($payload['division'] ?? ($item['division'] ?? ''))));
        $officerId = (int) ($payload['officer_id'] ?? 0);
        $movementDate = $this->normalizeIssueDate((string) ($payload['movement_date'] ?? ''));
        $errors = [];

        if (!in_array($movementType, ['ADD', 'DEDUCT'], true)) {
            $errors['movement_type'] = 'Choose a valid stock action.';
        }

        if ($quantity <= 0) {
            $errors['quantity'] = 'Quantity must be at least 1.';
        }

        if ($movementDate === '') {
            $errors['movement_date'] = 'Choose a valid date.';
        }

        $currentStock = (int) ($item['current_stock'] ?? 0);
        $newStock = $movementType === 'DEDUCT'
            ? $currentStock - $quantity
            : $currentStock + $quantity;

        if ($movementType === 'DEDUCT' && $newStock < 0) {
            $errors['quantity'] = 'Not enough stock.';
        }

        if ($movementType === 'DEDUCT') {
            $officer = $officerId > 0 ? $this->findOfficerSnapshot($officerId) : null;

            if ($division === '') {
                $errors['officer_id'] = 'This inventory item does not have a responsibility center code yet.';
            } elseif ($officerId <= 0) {
                $errors['officer_id'] = 'Choose an accountable officer.';
            } elseif ($officer === null) {
                $errors['officer_id'] = 'Choose a valid accountable officer.';
            } elseif (strtoupper(trim((string) ($officer['division'] ?? ''))) !== $division) {
                $errors['officer_id'] = 'Choose an officer under the selected responsibility center code.';
            } else {
                $notes = $this->buildStockDeductionNotes($officer, $notes);
            }
        }

        if ($errors !== []) {
            throw new ValidationException('Please review the stock movement.', $errors);
        }

        try {
            $this->db->beginTransaction();

            $updatedQuantityIssued = $movementType === 'ADD'
                ? ((int) ($item['quantity_issued'] ?? 0) + $quantity)
                : (int) ($item['quantity_issued'] ?? 0);

            $update = $this->db->prepare(
                'UPDATE inventory_items
                 SET current_stock = :current_stock,
                     quantity_issued = :quantity_issued,
                     stock_limit = :stock_limit,
                     low_stock_threshold = :low_stock_threshold
                 WHERE inventory_item_id = :inventory_item_id'
            );
            $update->execute([
                'current_stock' => $newStock,
                'quantity_issued' => $updatedQuantityIssued,
                'stock_limit' => $updatedQuantityIssued,
                'low_stock_threshold' => max(1, (int) ceil($updatedQuantityIssued * 0.2)),
                'inventory_item_id' => $itemId,
            ]);

            $this->recordMovement(
                $itemId,
                $movementType,
                $quantity,
                $currentStock,
                $newStock,
                $movementDate,
                $notes
            );

            $this->db->commit();

            return $this->findById($itemId) ?? $item;
        } catch (Throwable $throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            Logger::error('Unable to adjust inventory stock.', [
                'inventory_item_id' => $itemId,
                'exception' => $throwable->getMessage(),
                'payload' => $payload,
            ]);

            throw $throwable;
        }
    }

    public function batchStockOut(array $payload): array
    {
        $division = strtoupper(trim((string) ($payload['division'] ?? '')));
        $officerId = (int) ($payload['officer_id'] ?? 0);
        $movementDate = $this->normalizeIssueDate((string) ($payload['movement_date'] ?? ''));
        $notes = trim((string) ($payload['notes'] ?? ''));
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $errors = [];

        $divisionId = $division !== '' ? $this->findDivisionIdByCode($division) : null;
        if ($division === '' || $divisionId === null) {
            $errors['division'] = 'Choose a valid responsibility center code.';
        }

        $officer = $officerId > 0 ? $this->findOfficerSnapshot($officerId) : null;
        if ($officerId <= 0) {
            $errors['officer_id'] = 'Choose an accountable officer.';
        } elseif ($officer === null) {
            $errors['officer_id'] = 'Choose a valid accountable officer.';
        } elseif (strtoupper(trim((string) ($officer['division'] ?? ''))) !== $division) {
            $errors['officer_id'] = 'Choose an officer under the selected responsibility center code.';
        }

        if ($movementDate === '') {
            $errors['movement_date'] = 'Choose a valid date.';
        }

        if ($items === []) {
            $errors['items'] = 'Add at least one item to stock out.';
        }

        $normalizedItems = [];
        $seenItemIds = [];

        foreach ($items as $index => $itemPayload) {
            if (!is_array($itemPayload)) {
                continue;
            }

            $itemId = (int) ($itemPayload['inventory_item_id'] ?? 0);
            $quantity = (int) ($itemPayload['quantity'] ?? 0);
            $key = 'items.' . $index;

            if ($itemId <= 0) {
                $errors[$key] = 'Choose an inventory item for each stock out row.';
                continue;
            }

            if (isset($seenItemIds[$itemId])) {
                $errors[$key] = 'Each inventory item can only be selected once per stock out request.';
                continue;
            }

            if ($quantity <= 0) {
                $errors[$key] = 'Quantity must be at least 1 for each stock out row.';
                continue;
            }

            $inventoryItem = $this->findById($itemId);
            if ($inventoryItem === null) {
                $errors[$key] = 'One of the selected inventory items could not be found.';
                continue;
            }

            $currentStock = (int) ($inventoryItem['current_stock'] ?? 0);
            $allocationRemaining = $this->allocationRemainingForOfficer($inventoryItem, $officer ?? []);
            $allowedQuantity = min($currentStock, $allocationRemaining);
            if ($allowedQuantity < $quantity) {
                $errors[$key] = sprintf('Not enough stock for %s.', (string) ($inventoryItem['item_name'] ?? 'the selected item'));
                continue;
            }

            $seenItemIds[$itemId] = true;
            $normalizedItems[] = [
                'item' => $inventoryItem,
                'quantity' => $quantity,
            ];
        }

        if ($errors !== []) {
            throw new ValidationException('Please review the stock out request.', $errors);
        }

        $deductionNotes = $this->buildStockDeductionNotes($officer ?? [], $notes);
        $updatedItems = [];

        try {
            $this->db->beginTransaction();

            foreach ($normalizedItems as $entry) {
                $inventoryItem = $entry['item'];
                $quantity = (int) $entry['quantity'];
                $itemId = (int) ($inventoryItem['inventory_item_id'] ?? 0);
                $currentStock = (int) ($inventoryItem['current_stock'] ?? 0);
                $quantityIssued = (int) ($inventoryItem['quantity_issued'] ?? 0);
                $newStock = $currentStock - $quantity;

                $update = $this->db->prepare(
                    'UPDATE inventory_items
                     SET current_stock = :current_stock,
                         stock_limit = :stock_limit,
                         low_stock_threshold = :low_stock_threshold
                     WHERE inventory_item_id = :inventory_item_id'
                );
                $update->execute([
                    'current_stock' => $newStock,
                    'stock_limit' => $quantityIssued,
                    'low_stock_threshold' => max(1, (int) ceil($quantityIssued * 0.2)),
                    'inventory_item_id' => $itemId,
                ]);

                $this->recordMovement(
                    $itemId,
                    'DEDUCT',
                    $quantity,
                    $currentStock,
                    $newStock,
                    $movementDate,
                    $deductionNotes
                );

                $updatedItems[] = $this->findById($itemId) ?? $inventoryItem;
            }

            $this->db->commit();

            return [
                'items' => $updatedItems,
            ];
        } catch (Throwable $throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            Logger::error('Unable to save stock out batch.', [
                'exception' => $throwable->getMessage(),
                'payload' => $payload,
            ]);

            throw $throwable;
        }
    }

    public function findById(int $itemId): ?array
    {
        if ($itemId <= 0) {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT
                ii.inventory_item_id,
                ii.item_code,
                ii.request_type,
                ii.funding_source,
                ii.category,
                ii.ris_number,
                ii.stock_number,
                ii.item_name,
                ii.item_type,
                ii.unit,
                ii.division_id,
                ii.officer_id,
                ii.quantity_issued,
                ii.current_stock,
                ii.stock_limit,
                ii.low_stock_threshold,
                ii.unit_cost,
                ii.total_amount,
                ii.issued_at,
                ii.description,
                ii.allocations_json,
                ii.created_at,
                ii.updated_at,
                d.code AS division,
                d.label AS division_label,
                ao.name AS officer_name,
                ao.position AS officer_position,
                ao.unit AS officer_unit
             FROM inventory_items ii
             LEFT JOIN divisions d ON d.division_id = ii.division_id
             LEFT JOIN accountable_officers ao ON ao.officer_id = ii.officer_id
             WHERE ii.inventory_item_id = :inventory_item_id
             LIMIT 1'
        );
        $statement->execute([
            'inventory_item_id' => $itemId,
        ]);

        $item = $statement->fetch();

        return $item ? $this->hydrateItem($item) : null;
    }

    public function details(int $itemId): array
    {
        $item = $this->findById($itemId);

        if ($item === null) {
            throw new ValidationException('The selected inventory item could not be found.', [
                'inventory_item_id' => 'Choose a valid inventory item.',
            ]);
        }

        return [
            'item' => $this->appendAllocationStatus($item),
            'movements' => $this->listMovements($itemId),
        ];
    }

    public function listMovements(int $itemId, int $limit = 40): array
    {
        $statement = $this->db->prepare(
            'SELECT
                movement_id,
                inventory_item_id,
                movement_type,
                quantity,
                previous_stock,
                current_stock,
                movement_date,
                notes,
                created_at
             FROM inventory_movements
             WHERE inventory_item_id = :inventory_item_id
             ORDER BY COALESCE(movement_date, DATE(created_at)) DESC, created_at DESC, movement_id DESC
             LIMIT ' . (int) $limit
        );
        $statement->execute([
            'inventory_item_id' => $itemId,
        ]);

        return $statement->fetchAll();
    }

    public function listAllMovements(string $movementType = '', int $limit = 200): array
    {
        $normalizedType = strtoupper(trim($movementType));
        $params = [];
        $where = '';

        if ($normalizedType !== '') {
            $where = 'WHERE im.movement_type = :movement_type';
            $params['movement_type'] = $normalizedType;
        }

        $statement = $this->db->prepare(
             'SELECT
                 im.movement_id,
                 im.inventory_item_id,
                 im.movement_type,
                 im.quantity,
                im.previous_stock,
                im.current_stock,
                im.movement_date,
                im.notes,
                im.created_at,
                 ii.item_name,
                 ii.category,
                 ii.stock_number,
                 ii.ris_number,
                 ii.unit,
                ii.unit_cost,
                ii.quantity_issued
             FROM inventory_movements im
             INNER JOIN inventory_items ii ON ii.inventory_item_id = im.inventory_item_id
             ' . $where . '
             ORDER BY COALESCE(im.movement_date, DATE(im.created_at)) DESC, im.created_at DESC, im.movement_id DESC
             LIMIT ' . (int) $limit
        );
        $statement->execute($params);

        return array_map(function (array $movement): array {
            $movement['unit_cost'] = round((float) ($movement['unit_cost'] ?? 0), 2);
            $movement['amount'] = round(((int) ($movement['quantity'] ?? 0)) * $movement['unit_cost'], 2);

            if (preg_match('/^Accountable officer:\s*(.+?)\s*\(([^)]+)\)/i', (string) ($movement['notes'] ?? ''), $matches) === 1) {
                $movement['officer_name'] = trim((string) ($matches[1] ?? ''));
                $movement['division'] = trim((string) ($matches[2] ?? ''));
            } else {
                $movement['officer_name'] = '';
                $movement['division'] = '';
            }

            return $movement;
        }, $statement->fetchAll());
    }

    public function dashboardSummary(int $limit = 8): array
    {
        $items = $this->listItems([], 500);
        $watchlist = array_values(array_filter(
            $items,
            static fn (array $item): bool => in_array((string) ($item['stock_status_code'] ?? ''), ['LOW', 'NEAR'], true)
        ));

        usort($watchlist, static function (array $left, array $right): int {
            $priority = ['LOW' => 0, 'NEAR' => 1, 'NORMAL' => 2, 'AT_LIMIT' => 3];
            $leftPriority = $priority[(string) ($left['stock_status_code'] ?? 'NORMAL')] ?? 9;
            $rightPriority = $priority[(string) ($right['stock_status_code'] ?? 'NORMAL')] ?? 9;

            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            return ((int) ($left['current_stock'] ?? 0)) <=> ((int) ($right['current_stock'] ?? 0));
        });

        $sortedAscending = $items;
        usort($sortedAscending, static function (array $left, array $right): int {
            return ((int) ($left['current_stock'] ?? 0)) <=> ((int) ($right['current_stock'] ?? 0));
        });
        $sortedDescending = array_reverse($sortedAscending);
        $graphItems = [];
        $seenGraphIds = [];

        foreach (array_merge(array_slice($sortedAscending, 0, 5), array_slice($sortedDescending, 0, 5)) as $graphItem) {
            $graphId = (int) ($graphItem['inventory_item_id'] ?? 0);

            if ($graphId <= 0 || isset($seenGraphIds[$graphId])) {
                continue;
            }

            $seenGraphIds[$graphId] = true;
            $graphItems[] = $graphItem;
        }

        usort($graphItems, static function (array $left, array $right): int {
            return ((int) ($left['current_stock'] ?? 0)) <=> ((int) ($right['current_stock'] ?? 0));
        });

        return [
            'total_items' => count($items),
            'low_stock_count' => count(array_filter($items, static fn (array $item): bool => ($item['stock_status_code'] ?? '') === 'LOW')),
            'near_low_count' => count(array_filter($items, static fn (array $item): bool => ($item['stock_status_code'] ?? '') === 'NEAR')),
            'at_limit_count' => count(array_filter($items, static fn (array $item): bool => ($item['stock_status_code'] ?? '') === 'AT_LIMIT')),
            'high_stock_count' => count(array_filter($items, static fn (array $item): bool => ($item['stock_status_code'] ?? '') === 'AT_LIMIT')),
            'watchlist' => array_slice($watchlist, 0, $limit),
            'graph' => [
                'labels' => array_map(static fn (array $item): string => (string) ($item['item_name'] ?? 'Item'), $graphItems),
                'stocks' => array_map(static fn (array $item): int => (int) ($item['current_stock'] ?? 0), $graphItems),
                'limits' => array_map(static fn (array $item): int => (int) ($item['stock_limit'] ?? 0), $graphItems),
                'status_codes' => array_map(static fn (array $item): string => (string) ($item['stock_status_code'] ?? 'NORMAL'), $graphItems),
            ],
        ];
    }

    public function getFilterOptions(): array
    {
        return [
            'request_types' => self::REQUEST_TYPES,
            'item_types' => self::REQUEST_TYPES,
            'stock_statuses' => self::STATUS_LABELS,
            'units' => self::UNITS,
        ];
    }

    private function validateItemPayload(array $payload): array
    {
        $requestType = strtoupper(trim((string) ($payload['request_type'] ?? '')));
        $division = strtoupper(trim((string) ($payload['division'] ?? '')));
        $officerId = (int) ($payload['officer_id'] ?? 0);
        $category = trim((string) ($payload['category'] ?? ''));
        $stockNumber = trim((string) ($payload['stock_number'] ?? ''));
        $itemName = trim((string) ($payload['item_name'] ?? ''));
        $unit = trim((string) ($payload['unit'] ?? ''));
        $quantityIssued = (int) ($payload['quantity_issued'] ?? 0);
        $unitCost = $this->normalizeMoney($payload['unit_cost'] ?? 0);
        $issuedAtRaw = trim((string) ($payload['issued_at'] ?? ''));
        $issuedAt = $this->normalizeIssueDate($issuedAtRaw);
        $description = trim((string) ($payload['description'] ?? ''));
        $fundingSource = trim((string) ($payload['funding_source'] ?? ''));
        $inventoryItemId = (int) ($payload['inventory_item_id'] ?? 0);
        $existing = $inventoryItemId > 0 ? $this->findById($inventoryItemId) : null;
        $allocationLimit = $existing !== null
            ? max(0, (int) ($existing['current_stock'] ?? $quantityIssued))
            : $quantityIssued;
        $allocations = $this->normalizeAllocations($payload['allocations'] ?? [], $allocationLimit);
        $errors = [];

        if (!in_array($requestType, self::REQUEST_TYPES, true)) {
            $errors['request_type'] = 'Choose either RSMI or OSMI.';
        }

        if ($fundingSource === '') {
            $errors['funding_source'] = 'Choose a funding source.';
        }

        $divisionId = $division !== '' ? $this->findDivisionIdByCode($division) : null;

        if ($division !== '' && $divisionId === null) {
            $errors['division'] = 'Choose a valid responsibility center code.';
        }

        $officer = $officerId > 0 ? $this->findOfficerSnapshot($officerId) : null;

        if ($officerId > 0 && $officer === null) {
            $errors['officer_id'] = 'Choose a valid accountable officer.';
        } elseif ($division !== '' && strtoupper(trim((string) ($officer['division'] ?? ''))) !== $division) {
            $errors['officer_id'] = 'Choose an officer under the selected responsibility center code.';
        }

        if ($itemName === '') {
            $errors['item_name'] = 'Item name is required.';
        }

        $category = $this->resolveCategoryValue($requestType, $category);

        if ($category === '') {
            $errors['category'] = 'Category is required.';
        } elseif (!$this->isAllowedCategory($requestType, $category)) {
            $errors['category'] = 'Choose a valid category for the selected request form.';
        }

        if ($category !== '') {
            if ($existing !== null && strcasecmp(trim((string) ($existing['category'] ?? '')), $category) === 0) {
                $stockNumber = trim((string) ($existing['stock_number'] ?? ''));
            }

            if ($stockNumber === '') {
                $stockNumber = $this->generateStockNumber($requestType, $category, $itemName, $description, $inventoryItemId);
            }
        }

        if ($stockNumber === '') {
            $errors['stock_number'] = 'Stock number is required.';
        }

        if ($unit === '') {
            $errors['unit'] = 'Unit is required.';
        }

        if ($quantityIssued <= 0) {
            $errors['quantity_issued'] = 'Quantity issued must be at least 1.';
        }

        if ($unitCost < 0) {
            $errors['unit_cost'] = 'Unit cost cannot be negative.';
        }

        if ($issuedAtRaw === '') {
            $errors['issued_at'] = 'Date is required.';
        } elseif ($issuedAt === '') {
            $errors['issued_at'] = 'Enter a valid date.';
        }

        if ($errors !== []) {
            throw new ValidationException('Please review the inventory form.', $errors);
        }

        return [
            'request_type' => $requestType,
            'funding_source' => $fundingSource,
            'division' => $division,
            'division_id' => (int) $divisionId,
            'officer_id' => $officerId,
            'category' => $category,
            'stock_number' => $stockNumber,
            'item_name' => $itemName,
            'unit' => $unit,
            'quantity_issued' => $quantityIssued,
            'stock_limit' => $quantityIssued,
            'low_stock_threshold' => max(1, (int) ceil($quantityIssued * 0.2)),
            'unit_cost' => $unitCost,
            'total_amount' => round($quantityIssued * $unitCost, 2),
            'issued_at' => $issuedAt,
            'description' => $description,
            'allocations' => $allocations,
            'allocations_json' => $allocations !== [] ? json_encode($allocations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ];
    }

    private function hasItemChanges(array $existing, array $data): bool
    {
        return strtoupper(trim((string) ($existing['request_type'] ?? ''))) !== $data['request_type']
            || trim((string) ($existing['funding_source'] ?? '')) !== $data['funding_source']
            || trim((string) ($existing['category'] ?? '')) !== $data['category']
            || trim((string) ($existing['stock_number'] ?? '')) !== $data['stock_number']
            || strtoupper(trim((string) ($existing['division'] ?? ''))) !== $data['division']
            || (int) ($existing['officer_id'] ?? 0) !== $data['officer_id']
            || trim((string) ($existing['item_name'] ?? '')) !== $data['item_name']
            || trim((string) ($existing['unit'] ?? '')) !== $data['unit']
            || (int) ($existing['quantity_issued'] ?? 0) !== $data['quantity_issued']
            || (int) ($existing['stock_limit'] ?? 0) !== $data['stock_limit']
            || round((float) ($existing['unit_cost'] ?? 0), 2) !== round($data['unit_cost'], 2)
            || trim((string) ($existing['issued_at'] ?? '')) !== $data['issued_at']
            || trim((string) ($existing['description'] ?? '')) !== $data['description']
            || $this->normalizeAllocationSignature($existing['allocations'] ?? []) !== $this->normalizeAllocationSignature($data['allocations'] ?? []);
    }

    private function nextItemCode(): string
    {
        $year = date('Y');
        $prefix = 'INV-' . $year . '-';
        $statement = $this->db->prepare(
            'SELECT item_code
             FROM inventory_items
             WHERE item_code LIKE :prefix
             ORDER BY item_code DESC
             LIMIT 1'
        );
        $statement->execute([
            'prefix' => $prefix . '%',
        ]);

        $lastCode = (string) $statement->fetchColumn();
        $sequence = 1;

        if (preg_match('/-(\d{3})$/', $lastCode, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%03d', $prefix, $sequence);
    }

    private function nextRisNumber(string $issuedAt, int $excludeItemId = 0): string
    {
        $date = strtotime($issuedAt) ?: time();
        $prefix = date('Y-m-', $date);
        $statement = $this->db->prepare(
            'SELECT ris_number
             FROM inventory_items
             WHERE ris_number LIKE :prefix
               AND inventory_item_id <> :inventory_item_id
             ORDER BY ris_number DESC
             LIMIT 1'
        );
        $statement->execute([
            'prefix' => $prefix . '%',
            'inventory_item_id' => $excludeItemId,
        ]);

        $lastNumber = (string) $statement->fetchColumn();
        $sequence = 1;

        if (preg_match('/-(\d{3})$/', $lastNumber, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%03d', $prefix, $sequence);
    }

    private function resolveStockNumber(string $category, string $itemName, string $description, int $excludeItemId = 0): string
    {
        $categoryPrefix = $this->resolveCategoryPrefix($category);
        $normalizedName = $this->normalizeDescriptor($itemName);
        $normalizedDescription = $this->normalizeDescriptor($description);

        if ($categoryPrefix !== '' && $normalizedName !== '') {
            $statement = $this->db->query(
                'SELECT inventory_item_id, category, item_name, description, stock_number
                 FROM inventory_items
                 WHERE stock_number IS NOT NULL
                   AND stock_number <> ""
                 ORDER BY inventory_item_id ASC'
            );

            foreach ($statement->fetchAll() as $row) {
                if ((int) ($row['inventory_item_id'] ?? 0) === $excludeItemId) {
                    continue;
                }

                if ($this->resolveCategoryPrefix((string) ($row['category'] ?? '')) === $categoryPrefix
                    && $this->normalizeDescriptor((string) ($row['item_name'] ?? '')) === $normalizedName
                    && $this->normalizeDescriptor((string) ($row['description'] ?? '')) === $normalizedDescription) {
                    return (string) $row['stock_number'];
                }
            }
        }

        if ($categoryPrefix === '') {
            return '';
        }

        $statement = $this->db->prepare(
            'SELECT stock_number
             FROM inventory_items
             WHERE stock_number LIKE :prefix
               AND inventory_item_id <> :inventory_item_id
             ORDER BY stock_number DESC
             LIMIT 1'
        );
        $statement->execute([
            'prefix' => $categoryPrefix . '-%',
            'inventory_item_id' => $excludeItemId,
        ]);
        $lastNumber = (string) $statement->fetchColumn();
        $sequence = 1;

        if (preg_match('/^' . preg_quote($categoryPrefix, '/') . '-(\d{3})$/', $lastNumber, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%03d', $categoryPrefix, $sequence);
    }

    private function resolveCategoryPrefix(string $category): string
    {
        $normalized = strtoupper(trim($category));

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^([A-Z0-9])\s*-/', $normalized, $matches) === 1) {
            return $matches[1];
        }

        return preg_match('/^[A-Z0-9]$/', $normalized) === 1 ? $normalized : '';
    }

    private function isAllowedCategory(string $requestType, string $category): bool
    {
        return in_array($category, self::CATEGORY_MAP[$requestType] ?? [], true);
    }

    private function resolveCategoryValue(string $requestType, string $category): string
    {
        $normalizedCategory = trim($category);

        if ($normalizedCategory === '') {
            return '';
        }

        $needle = strtoupper(preg_replace('/\s+/', ' ', $normalizedCategory));

        foreach (self::CATEGORY_MAP[$requestType] ?? [] as $option) {
            $normalizedOption = strtoupper(preg_replace('/\s+/', ' ', trim($option)));
            if ($normalizedOption === $needle) {
                return $option;
            }
        }

        return $normalizedCategory;
    }

    private function generateStockNumber(string $requestType, string $category, string $itemName, string $description, int $excludeItemId = 0): string
    {
        if (!$this->isAllowedCategory($requestType, $category)) {
            return '';
        }

        return $this->resolveStockNumber($category, $itemName, $description, $excludeItemId);
    }

    private function findDivisionIdByCode(string $division): ?int
    {
        $statement = $this->db->prepare(
            'SELECT division_id
             FROM divisions
             WHERE code = :code
             LIMIT 1'
        );
        $statement->execute([
            'code' => strtoupper(trim($division)),
        ]);

        $divisionId = $statement->fetchColumn();

        return $divisionId === false ? null : (int) $divisionId;
    }

    private function findOfficerSnapshot(int $officerId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                ao.officer_id,
                ao.name,
                ao.position,
                ao.unit,
                d.code AS division,
                d.label AS division_label
             FROM accountable_officers ao
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE ao.officer_id = :officer_id
             LIMIT 1'
        );
        $statement->execute([
            'officer_id' => $officerId,
        ]);

        $officer = $statement->fetch();

        return $officer ?: null;
    }

    private function recordMovement(
        int $itemId,
        string $movementType,
        int $quantity,
        int $previousStock,
        int $currentStock,
        string $movementDate,
        string $notes
    ): void {
        $statement = $this->db->prepare(
            'INSERT INTO inventory_movements (
                inventory_item_id,
                movement_type,
                quantity,
                previous_stock,
                current_stock,
                movement_date,
                notes
             ) VALUES (
                :inventory_item_id,
                :movement_type,
                :quantity,
                :previous_stock,
                :current_stock,
                :movement_date,
                :notes
             )'
        );
        $statement->execute([
            'inventory_item_id' => $itemId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'current_stock' => $currentStock,
            'movement_date' => $movementDate !== '' ? $movementDate : null,
            'notes' => $notes,
        ]);
    }

    private function buildStockDeductionNotes(array $officer, string $notes): string
    {
        $officerName = trim((string) ($officer['name'] ?? ''));
        $division = trim((string) ($officer['division'] ?? ''));
        $unit = trim((string) ($officer['unit'] ?? ''));
        $prefix = 'Accountable officer';

        if ($officerName !== '' && $division !== '') {
            $meta = $division;
            if (strtoupper($division) === 'FAD' && $unit !== '') {
                $meta .= ' | ' . $unit;
            }

            $prefix .= ': ' . $officerName . ' (' . $meta . ')';
        } elseif ($officerName !== '') {
            $prefix .= ': ' . $officerName;
        }

        return $notes !== '' ? $prefix . ' | ' . $notes : $prefix;
    }

    private function hydrateItem(array $item): array
    {
        $currentStock = (int) ($item['current_stock'] ?? 0);
        $totalStock = max(0, (int) ($item['quantity_issued'] ?? 0));
        $stockLimit = $totalStock;
        $lowThreshold = max(1, (int) ceil($totalStock * 0.2));
        $statusCode = $this->statusCode($currentStock, $totalStock);
        $statusLabel = self::STATUS_LABELS[$statusCode] ?? self::STATUS_LABELS['NORMAL'];

        $item['request_type'] = strtoupper(trim((string) ($item['request_type'] ?? ''))) ?: 'RSMI';
        $item['funding_source'] = trim((string) ($item['funding_source'] ?? ''));
        $item['category'] = trim((string) ($item['category'] ?? ''));
        $item['quantity_issued'] = (int) ($item['quantity_issued'] ?? 0);
        $item['current_stock'] = $currentStock;
        $item['stock_limit'] = $stockLimit;
        $item['low_stock_threshold'] = $lowThreshold;
        $item['unit_cost'] = round((float) ($item['unit_cost'] ?? 0), 2);
        $item['total_amount'] = round((float) ($item['total_amount'] ?? 0), 2);
        $item['division'] = trim((string) ($item['division'] ?? ''));
        $item['division_label'] = trim((string) ($item['division_label'] ?? ''));
        $item['officer_name'] = trim((string) ($item['officer_name'] ?? ''));
        $item['officer_position'] = trim((string) ($item['officer_position'] ?? ''));
        $item['officer_unit'] = trim((string) ($item['officer_unit'] ?? ''));
        $item['allocations'] = $this->decodeAllocations($item['allocations_json'] ?? null);
        $item['stock_status_code'] = $statusCode;
        $item['stock_status_label'] = $statusCode === 'NEAR' ? 'MEDIUM' : $statusLabel;
        $item['stock_remark'] = match ($statusCode) {
            'LOW' => 'Remaining stock is at or below 20% of the total stock.',
            'NEAR' => 'Remaining stock is at or below 50% of the total stock.',
            default => 'Stock level remains healthy.',
        };

        return $item;
    }

    private function normalizeAllocations(mixed $rawAllocations, int $quantityIssued): array
    {
        $allocations = $this->decodeAllocations($rawAllocations);
        if ($allocations === []) {
            return [];
        }

        $validTargets = [];
        foreach ($this->allocationTargets() as $target) {
            $validTargets[(string) ($target['key'] ?? '')] = $target;
        }

        $normalized = [];
        $seenKeys = [];
        $totalAllocated = 0;

        foreach ($allocations as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $targetKey = trim((string) ($entry['target_key'] ?? $entry['key'] ?? ''));
            $quantity = (int) ($entry['quantity'] ?? 0);

            if ($targetKey === '' || $quantity <= 0 || isset($seenKeys[$targetKey])) {
                continue;
            }

            $target = $validTargets[$targetKey] ?? null;
            if ($target === null) {
                continue;
            }

            $seenKeys[$targetKey] = true;
            $totalAllocated += $quantity;
            $normalized[] = [
                'target_key' => $targetKey,
                'division' => (string) ($target['division'] ?? ''),
                'unit' => (string) ($target['unit'] ?? ''),
                'label' => (string) ($target['label'] ?? $targetKey),
                'quantity' => $quantity,
            ];
        }

        if ($totalAllocated > $quantityIssued) {
            throw new ValidationException('Please review the inventory form.', [
                'allocations' => 'Allocated quantity cannot be greater than the current stock.',
            ]);
        }

        return $normalized;
    }

    private function decodeAllocations(mixed $rawAllocations): array
    {
        if (is_string($rawAllocations)) {
            $trimmed = trim($rawAllocations);
            if ($trimmed === '') {
                return [];
            }

            $decoded = json_decode($trimmed, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($rawAllocations) ? $rawAllocations : [];
    }

    private function allocationTargets(): array
    {
        $targets = [];
        $divisionStatement = $this->db->query('SELECT code, label FROM divisions ORDER BY sort_order ASC, code ASC');
        foreach ($divisionStatement->fetchAll() as $division) {
            $code = strtoupper(trim((string) ($division['code'] ?? '')));
            $label = trim((string) ($division['label'] ?? $code));

            if ($code === '' || $code === 'FAD') {
                continue;
            }

            $targets[] = [
                'key' => $code,
                'division' => $code,
                'unit' => '',
                'label' => $label !== '' ? $code . ' - ' . $label : $code,
            ];
        }

        $unitStatement = $this->db->query(
            "SELECT DISTINCT unit
             FROM accountable_officers ao
             INNER JOIN divisions d ON d.division_id = ao.division_id
             WHERE d.code = 'FAD'
               AND ao.unit IS NOT NULL
               AND TRIM(ao.unit) <> ''
             ORDER BY ao.unit ASC"
        );

        foreach ($unitStatement->fetchAll(PDO::FETCH_COLUMN) as $unit) {
            $normalizedUnit = trim((string) $unit);
            if ($normalizedUnit === '') {
                continue;
            }

            $targets[] = [
                'key' => 'FAD::' . $normalizedUnit,
                'division' => 'FAD',
                'unit' => $normalizedUnit,
                'label' => 'FAD - ' . $normalizedUnit,
            ];
        }

        return $targets;
    }

    private function normalizeAllocationSignature(array $allocations): string
    {
        $normalized = array_map(static function (array $entry): array {
            return [
                'target_key' => trim((string) ($entry['target_key'] ?? '')),
                'quantity' => (int) ($entry['quantity'] ?? 0),
            ];
        }, $allocations);

        usort($normalized, static fn (array $left, array $right): int => strcmp($left['target_key'], $right['target_key']));

        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    private function appendAllocationStatus(array $item): array
    {
        $allocations = is_array($item['allocations'] ?? null) ? $item['allocations'] : [];
        if ($allocations === []) {
            $item['allocation_status'] = [];
            return $item;
        }

        $usage = [];
        foreach ($this->listMovements((int) ($item['inventory_item_id'] ?? 0), 500) as $movement) {
            if (strtoupper(trim((string) ($movement['movement_type'] ?? ''))) !== 'DEDUCT') {
                continue;
            }

            $targetKey = $this->movementAllocationTargetKey((string) ($movement['notes'] ?? ''));
            if ($targetKey === '') {
                continue;
            }

            $usage[$targetKey] = ($usage[$targetKey] ?? 0) + (int) ($movement['quantity'] ?? 0);
        }

        $item['allocation_status'] = array_map(static function (array $allocation) use ($usage): array {
            $key = trim((string) ($allocation['target_key'] ?? ''));
            $assigned = (int) ($allocation['quantity'] ?? 0);
            $used = (int) ($usage[$key] ?? 0);

            return [
                'target_key' => $key,
                'label' => (string) ($allocation['label'] ?? $key),
                'division' => (string) ($allocation['division'] ?? ''),
                'unit' => (string) ($allocation['unit'] ?? ''),
                'assigned' => $assigned,
                'used' => $used,
                'remaining' => max(0, $assigned - $used),
            ];
        }, $allocations);

        return $item;
    }

    private function movementAllocationTargetKey(string $notes): string
    {
        if (preg_match('/^Accountable officer:\s*(.+?)\s*\(([^)]+)\)/i', $notes, $matches) !== 1) {
            return '';
        }

        $meta = array_map(static fn (string $value): string => trim($value), explode('|', (string) ($matches[2] ?? '')));
        $division = strtoupper(trim((string) ($meta[0] ?? '')));
        $unit = trim((string) ($meta[1] ?? ''));

        if ($division === '') {
            return '';
        }

        if ($division === 'FAD' && $unit !== '') {
            return 'FAD::' . $unit;
        }

        return $division;
    }

    private function allocationRemainingForOfficer(array $item, array $officer): int
    {
        $item = $this->appendAllocationStatus($item);
        $allocationStatus = is_array($item['allocation_status'] ?? null) ? $item['allocation_status'] : [];

        if ($allocationStatus === []) {
            return max(0, (int) ($item['current_stock'] ?? 0));
        }

        $division = strtoupper(trim((string) ($officer['division'] ?? '')));
        $unit = trim((string) ($officer['unit'] ?? ''));
        if ($division === '') {
            return 0;
        }

        foreach ($allocationStatus as $allocation) {
            $allocationDivision = strtoupper(trim((string) ($allocation['division'] ?? '')));
            $allocationUnit = trim((string) ($allocation['unit'] ?? ''));

            if ($allocationDivision !== $division) {
                continue;
            }

            if ($allocationDivision === 'FAD' && $allocationUnit !== $unit) {
                continue;
            }

            return max(0, (int) ($allocation['remaining'] ?? 0));
        }

        return 0;
    }

    private function statusCode(int $currentStock, int $totalStock): string
    {
        if ($totalStock <= 0) {
            return $currentStock <= 0 ? 'LOW' : 'HIGH';
        }

        $lowThreshold = max(1, (int) ceil($totalStock * 0.2));
        $mediumThreshold = max(1, (int) ceil($totalStock * 0.5));

        if ($currentStock <= $lowThreshold) {
            return 'LOW';
        }

        if ($currentStock <= $mediumThreshold) {
            return 'NEAR';
        }

        return 'HIGH';
    }

    private function normalizeIssueDate(string $value): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return '';
        }

        $timestamp = strtotime($normalized);

        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    private function normalizeMoney(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return round((float) $value, 2);
    }

    private function normalizeDescriptor(string $value): string
    {
        $normalized = strtolower(trim($value));
        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }
}

