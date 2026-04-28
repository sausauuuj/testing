<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$currentYear = (int) date('Y');
$currentMonth = (int) date('n');
$dashboardYears = range($currentYear - 4, $currentYear + 1);
$dashboardMonths = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
];

?>
<section id="dashboard" class="app-view active" data-view="dashboard">
    <div class="view-scroll section-stack dashboard-view">
        <div class="dashboard-shell">
            <header class="dashboard-topbar">
                <div class="dashboard-topbar__title">
                    <h2 class="dashboard-title">Dashboard</h2>
                    <p id="dashboardHeroCopy" class="dashboard-subtitle">Track asset totals, stock levels, and inventory concentration from a single glance.</p>
                </div>

                <div class="dashboard-topbar__tools">
                    <label class="dashboard-search" for="dashboardSearchPlaceholder">
                        <input
                            id="dashboardSearchPlaceholder"
                            class="form-input dashboard-search__input"
                            type="search"
                            placeholder="Search placeholder"
                            aria-label="Search placeholder"
                        >
                        <span class="dashboard-search__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3.5-3.5"></path>
                            </svg>
                        </span>
                    </label>

                    <button type="button" class="dashboard-icon-button" aria-label="Messages">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 5.5h16v10H8l-4 4z"></path>
                            <path d="M8 9h8"></path>
                        </svg>
                    </button>

                    <button type="button" class="dashboard-icon-button dashboard-icon-button--alert" aria-label="Notifications">
                        <span class="dashboard-icon-button__badge" aria-hidden="true"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 17H5l1.5-1.5c.8-.8 1.2-1.9 1.2-3.1V10a4.3 4.3 0 0 1 8.6 0v2.4c0 1.2.4 2.3 1.2 3.1L19 17z"></path>
                            <path d="M9.5 17a2.5 2.5 0 0 0 5 0"></path>
                        </svg>
                    </button>

                    <div class="dashboard-user">
                        <span class="dashboard-user__name">Andrew Forbist</span>
                        <span class="dashboard-user__avatar">AF</span>
                    </div>
                </div>
            </header>

            <div class="dashboard-filters">
                <div class="dashboard-filter-group">
                    <label for="dashboardFilterMode" class="dashboard-filter-label">View</label>
                    <select id="dashboardFilterMode" class="form-input dashboard-filter-select">
                        <option value="monthly" selected>Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <div class="dashboard-filter-group dashboard-yearly-picker">
                    <label for="dashboardYear" class="dashboard-filter-label">Year</label>
                    <select id="dashboardYear" class="form-input dashboard-filter-select">
                        <?php foreach ($dashboardYears as $year): ?>
                            <option value="<?php echo htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $year === $currentYear ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="dashboard-filter-group dashboard-monthly-picker">
                    <label for="dashboardMonth" class="dashboard-filter-label">Month</label>
                    <select id="dashboardMonth" class="form-input dashboard-filter-select">
                        <?php foreach ($dashboardMonths as $monthNumber => $monthName): ?>
                            <option value="<?php echo htmlspecialchars((string) $monthNumber, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $monthNumber === $currentMonth ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($monthName, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="dashboard-layout">
                <div class="dashboard-layout__main">
                    <section class="dashboard-panel dashboard-hero-card">
                        <div class="dashboard-hero-card__decor" aria-hidden="true"></div>

                        <div class="dashboard-hero-card__head">
                            <div>
                                <span id="dashboardModeBadge" class="dashboard-mode-badge">Overview</span>
                                <h3 class="dashboard-hero-card__name">Andrew Forbist</h3>
                            </div>

                            <div class="dashboard-hero-card__signal" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 20c4-4 4-12 0-16"></path>
                                    <path d="M9 18c2.5-2.5 2.5-9 0-12"></path>
                                    <path d="M14 16c1.4-1.4 1.4-5.2 0-7"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="dashboard-hero-card__balance">
                            <p class="dashboard-hero-card__label">Balance Amount</p>
                            <strong class="dashboard-hero-card__value">PHP 562,000</strong>
                        </div>

                        <div class="dashboard-hero-card__meta">
                            <div class="dashboard-hero-card__meta-item">
                                <span>EXP</span>
                                <strong>11/29</strong>
                            </div>
                            <div class="dashboard-hero-card__meta-item">
                                <span>CVV</span>
                                <strong>323</strong>
                            </div>
                        </div>
                    </section>

                    <div class="dashboard-quick-actions">
                        <button type="button" class="dashboard-quick-action">
                            <span class="dashboard-quick-action__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5v14"></path>
                                    <path d="M5 12h14"></path>
                                </svg>
                            </span>
                            <span>Top Up</span>
                        </button>
                        <button type="button" class="dashboard-quick-action">
                            <span class="dashboard-quick-action__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 12h16"></path>
                                    <path d="M10 6l-6 6 6 6"></path>
                                </svg>
                            </span>
                            <span>Transfer</span>
                        </button>
                        <button type="button" class="dashboard-quick-action">
                            <span class="dashboard-quick-action__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5v14"></path>
                                    <path d="M5 12h14"></path>
                                    <path d="M12 5a7 7 0 1 1 0 14"></path>
                                </svg>
                            </span>
                            <span>Request</span>
                        </button>
                        <button type="button" class="dashboard-quick-action">
                            <span class="dashboard-quick-action__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 6h14"></path>
                                    <path d="M5 12h14"></path>
                                    <path d="M5 18h14"></path>
                                </svg>
                            </span>
                            <span>History</span>
                        </button>
                    </div>

                    <div class="dashboard-kpi-grid">
                        <div class="dashboard-kpi-card">
                            <p class="dashboard-kpi-card__label">Total Assets</p>
                            <h3 id="metricAssets" class="dashboard-kpi-card__value">0</h3>
                        </div>
                        <div class="dashboard-kpi-card">
                            <p class="dashboard-kpi-card__label">Total Value</p>
                            <h3 id="metricValue" class="dashboard-kpi-card__value">PHP 0.00</h3>
                        </div>
                        <div class="dashboard-kpi-card">
                            <p class="dashboard-kpi-card__label">Total Stocks</p>
                            <h3 id="metricTotalStocks" class="dashboard-kpi-card__value">0</h3>
                        </div>
                        <div class="dashboard-kpi-card">
                            <p class="dashboard-kpi-card__label">Total Amount</p>
                            <h3 id="metricTotalAmount" class="dashboard-kpi-card__value">PHP 0.00</h3>
                        </div>
                    </div>

                    <div class="dashboard-mini-grid">
                        <div class="dashboard-mini-card">
                            <p class="dashboard-mini-card__label">PPE Items</p>
                            <h3 id="metricPpe" class="dashboard-mini-card__value">0</h3>
                        </div>
                        <div class="dashboard-mini-card">
                            <p class="dashboard-mini-card__label">Semi-Expendable</p>
                            <h3 id="metricSemi" class="dashboard-mini-card__value">0</h3>
                        </div>
                    </div>

                    <section class="dashboard-panel dashboard-cashflow-card">
                        <div class="dashboard-panel__head">
                            <div>
                                <h3 id="dashboardFundingTitle" class="dashboard-section-title">Cashflow</h3>
                                <p id="dashboardFundingCopy" class="dashboard-section-copy">Track inflow and outflow trends for the selected period.</p>
                            </div>

                            <select class="form-input dashboard-card-select" aria-label="Cashflow range">
                                <option selected>This Year</option>
                                <option>This Quarter</option>
                                <option>This Month</option>
                            </select>
                        </div>

                        <div class="dashboard-cashflow-summary">
                            <div>
                                <p class="dashboard-cashflow-summary__label">Total Balance</p>
                                <strong class="dashboard-cashflow-summary__value">PHP 562,000</strong>
                            </div>

                            <div class="dashboard-cashflow-legend" aria-hidden="true">
                                <span><i class="dashboard-legend-swatch dashboard-legend-swatch--income"></i>Income</span>
                                <span><i class="dashboard-legend-swatch dashboard-legend-swatch--expense"></i>Expense</span>
                            </div>
                        </div>

                        <div class="dashboard-cashflow-chart" aria-hidden="true">
                            <div class="dashboard-cashflow-chart__yaxis">
                                <span>8K</span>
                                <span>4K</span>
                                <span>0</span>
                                <span>-4K</span>
                                <span>-8K</span>
                            </div>

                            <div class="dashboard-cashflow-chart__bars">
                                <span style="height: 44%"></span>
                                <span style="height: 28%"></span>
                                <span style="height: 36%"></span>
                                <span style="height: 52%"></span>
                                <span style="height: 32%"></span>
                                <span style="height: 48%"></span>
                                <span style="height: 26%"></span>
                                <span style="height: 34%"></span>
                                <span style="height: 44%"></span>
                                <span style="height: 58%"></span>
                                <span style="height: 42%"></span>
                                <span style="height: 36%"></span>
                            </div>

                            <div class="dashboard-cashflow-chart__months">
                                <span>Jan</span>
                                <span>Feb</span>
                                <span>Mar</span>
                                <span>Apr</span>
                                <span>May</span>
                                <span>Jun</span>
                                <span>Jul</span>
                                <span>Aug</span>
                                <span>Sep</span>
                                <span>Oct</span>
                                <span>Nov</span>
                                <span>Dec</span>
                            </div>
                        </div>
                    </section>

                    <section class="dashboard-panel dashboard-stock-card">
                        <div class="dashboard-panel__head dashboard-panel__head--stacked-mobile">
                            <div>
                                <h3 class="dashboard-section-title">Recent Transactions</h3>
                                <p class="dashboard-section-copy">Current inventory snapshots and stock-level alerts.</p>
                            </div>

                            <div class="dashboard-stock-card__head-meta">
                                <span id="dashboardInventoryTotal" class="dashboard-total-pill">0 Items</span>
                                <select class="form-input dashboard-card-select dashboard-card-select--compact" aria-label="Transaction range">
                                    <option selected>This Month</option>
                                    <option>This Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="dashboard-inventory-filters">
                            <button type="button" class="dashboard-filter-btn" data-stock-filter="HIGH">
                                <span class="filter-btn-label">High Stock</span>
                                <span id="dashboardHighStockCount" class="filter-btn-count">0</span>
                            </button>
                            <button type="button" class="dashboard-filter-btn" data-stock-filter="MEDIUM">
                                <span class="filter-btn-label">Medium Stock</span>
                                <span id="dashboardMediumStockCount" class="filter-btn-count">0</span>
                            </button>
                            <button type="button" class="dashboard-filter-btn" data-stock-filter="LOW">
                                <span class="filter-btn-label">Low Stock</span>
                                <span id="dashboardLowStockCount" class="filter-btn-count">0</span>
                            </button>
                        </div>

                        <div class="dashboard-inventory-table-wrapper">
                            <table class="dashboard-inventory-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Stock #</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboardInventoryTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-slate-500 py-4">No inventory data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <aside class="dashboard-layout__aside">
                    <section class="dashboard-panel dashboard-stat-card">
                        <div class="dashboard-panel__head">
                            <div>
                                <h3 id="dashboardCategoryTitle" class="dashboard-section-title">Statistic</h3>
                                <p id="dashboardCategoryCopy" class="dashboard-section-copy">Breakdown of items by classification for the current view.</p>
                            </div>

                            <select class="form-input dashboard-card-select dashboard-card-select--compact" aria-label="Statistic range">
                                <option selected>This Month</option>
                                <option>This Year</option>
                            </select>
                        </div>

                        <div class="dashboard-stat-card__chart">
                            <canvas id="categoryChart"></canvas>
                        </div>

                        <div class="dashboard-stat-card__list">
                            <div class="dashboard-stat-card__item">
                                <span class="dashboard-stat-card__pill dashboard-stat-card__pill--dark">60%</span>
                                <span>Rent & Living</span>
                                <strong>2,100</strong>
                            </div>
                            <div class="dashboard-stat-card__item">
                                <span class="dashboard-stat-card__pill dashboard-stat-card__pill--green">15%</span>
                                <span>Investment</span>
                                <strong>525</strong>
                            </div>
                            <div class="dashboard-stat-card__item">
                                <span class="dashboard-stat-card__pill dashboard-stat-card__pill--mint">12%</span>
                                <span>Education</span>
                                <strong>420</strong>
                            </div>
                            <div class="dashboard-stat-card__item">
                                <span class="dashboard-stat-card__pill dashboard-stat-card__pill--slate">8%</span>
                                <span>Food & Drink</span>
                                <strong>280</strong>
                            </div>
                            <div class="dashboard-stat-card__item">
                                <span class="dashboard-stat-card__pill dashboard-stat-card__pill--gray">5%</span>
                                <span>Entertainment</span>
                                <strong>175</strong>
                            </div>
                        </div>
                    </section>

                    <section class="dashboard-panel dashboard-activity-card">
                        <div class="dashboard-panel__head">
                            <div>
                                <h3 class="dashboard-section-title">Recent Activity</h3>
                                <p class="dashboard-section-copy">Latest updates from the inventory and reports workspace.</p>
                            </div>
                        </div>

                        <div class="dashboard-activity-card__group">
                            <h4 class="dashboard-activity-card__group-title">Today</h4>
                            <ul id="dashboardActivity" class="dashboard-activity-list">
                                <li class="dashboard-activity-item">
                                    <span class="dashboard-activity-item__avatar dashboard-activity-item__avatar--lime">JS</span>
                                    <div>
                                        <p class="dashboard-activity-item__title">Jamie Smith updated account settings</p>
                                        <span class="dashboard-activity-item__meta">16:05</span>
                                    </div>
                                </li>
                                <li class="dashboard-activity-item">
                                    <span class="dashboard-activity-item__avatar dashboard-activity-item__avatar--blue">AJ</span>
                                    <div>
                                        <p class="dashboard-activity-item__title">Alex Johnson logged in</p>
                                        <span class="dashboard-activity-item__meta">13:05</span>
                                    </div>
                                </li>
                                <li class="dashboard-activity-item">
                                    <span class="dashboard-activity-item__avatar dashboard-activity-item__avatar--green">ML</span>
                                    <div>
                                        <p class="dashboard-activity-item__title">Morgan Lee added a new savings goal</p>
                                        <span class="dashboard-activity-item__meta">02:05</span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="dashboard-activity-card__group">
                            <h4 class="dashboard-activity-card__group-title">Yesterday</h4>
                            <ul class="dashboard-activity-list">
                                <li class="dashboard-activity-item">
                                    <span class="dashboard-activity-item__avatar dashboard-activity-item__avatar--amber">TG</span>
                                    <div>
                                        <p class="dashboard-activity-item__title">Taylor Green reviewed recent transactions</p>
                                        <span class="dashboard-activity-item__meta">21:05</span>
                                    </div>
                                </li>
                                <li class="dashboard-activity-item">
                                    <span class="dashboard-activity-item__avatar dashboard-activity-item__avatar--teal">WB</span>
                                    <div>
                                        <p class="dashboard-activity-item__title">Wilson Baptista transferred funds to emergency fund</p>
                                        <span class="dashboard-activity-item__meta">09:05</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</section>
