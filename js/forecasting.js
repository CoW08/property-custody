class ForecastingPage {
    constructor() {
        this.highlightsContainer = document.getElementById('forecastHighlights');
        this.reorderTableBody = document.querySelector('#forecastReorderTable tbody');
        this.alertsList = document.getElementById('forecastAlertsList');
        this.generatedAtLabel = document.getElementById('forecastGeneratedAt');
        this.seasonalityChartCanvas = document.getElementById('seasonalityChart');
        this.seasonalityBreakdown = document.getElementById('seasonalityBreakdown');
        this.refreshButton = document.getElementById('forecastRefreshBtn');
        this.exportButton = document.getElementById('forecastExportBtn');
        this.seasonalityToggle = document.getElementById('seasonalityToggle');

        // Analytics & critical alerts containers (injected by forecasting.php)
        this.criticalAlertsBanner = document.getElementById('forecastCriticalAlertsBanner');
        this.analyticsSection = document.getElementById('forecastAnalyticsSection');

        this.chart = null;
        this.lastOverview = null;
        this.lastReorders = [];
        this.lastAlerts = [];
        this.lastSeasonality = [];
        this.lastAnalytics = null;
        this.init();
    }

    async init() {
        if (!this.highlightsContainer) return;

        this.bindEvents();
        try {
            await this.ensureChartLibrary();
            await this.loadAllData();
        } catch (error) {
            console.error('Unable to initialize forecasting page', error);
            this.notify('Unable to load forecasting data', 'error');
            this.renderSeasonality([], true);
        }
    }

    triggerBlobDownload(blob, filename) {
        if (window.navigator?.msSaveOrOpenBlob) {
            window.navigator.msSaveOrOpenBlob(blob, filename);
            return;
        }

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();

        setTimeout(() => {
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }, 500);
    }

    bindEvents() {
        if (this.refreshButton) {
            this.refreshButton.addEventListener('click', async () => {
                await this.handleManualRefresh();
            });
        }

        const seedBtn = document.getElementById('forecastSeedBtn');
        if (seedBtn) {
            seedBtn.addEventListener('click', async () => {
                if (!confirm('This will generate simulated historical transactions for all live inventory items that don\'t have transaction history yet. This enables AI predictions for runout days.\n\nContinue?')) return;
                seedBtn.disabled = true;
                seedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Seeding...';
                try {
                    const resp = await fetch('api/forecasting.php?action=seed_history', { method: 'POST' });
                    const data = await resp.json();
                    if (data.success) {
                        alert(`✅ ${data.message}`);
                        await this.handleManualRefresh();
                    } else {
                        alert('❌ ' + (data.message || 'Failed to seed history'));
                    }
                } catch (err) {
                    console.error('Seed error:', err);
                    alert('Error seeding history: ' + err.message);
                } finally {
                    seedBtn.disabled = false;
                    seedBtn.innerHTML = '<i class="fas fa-database"></i> Seed History';
                }
            });
        }

        if (this.exportButton) {
            this.exportButton.addEventListener('click', async () => {
                await this.handleExport();
            });
        }

        if (this.seasonalityToggle) {
            this.seasonalityToggle.addEventListener('click', () => {
                if (!this.seasonalityBreakdown) return;
                this.seasonalityBreakdown.classList.toggle('hidden');
                this.seasonalityToggle.textContent = this.seasonalityBreakdown.classList.contains('hidden')
                    ? 'View Details'
                    : 'Hide Details';
            });
        }
    }

    async loadAllData(force = false) {
        this.setLoadingState(true);
        try {
            const [overviewResult, reorderResult, alertsResult, seasonalityResult, analyticsResult] = await Promise.allSettled([
                API.getForecastOverview(),
                API.getForecastReorders(),
                API.getForecastAlerts(),
                API.getForecastSeasonality(),
                API.getForecastAnalytics()
            ]);

            const overviewData = overviewResult.status === 'fulfilled'
                ? (overviewResult.value?.data ?? overviewResult.value)
                : null;
            const reorderData = reorderResult.status === 'fulfilled'
                ? (reorderResult.value?.data ?? reorderResult.value)
                : [];
            const alertsData = alertsResult.status === 'fulfilled'
                ? (alertsResult.value?.data ?? alertsResult.value)
                : [];
            const seasonalityData = seasonalityResult.status === 'fulfilled'
                ? (Array.isArray(seasonalityResult.value?.data)
                    ? seasonalityResult.value.data
                    : Array.isArray(seasonalityResult.value)
                        ? seasonalityResult.value
                        : [])
                : [];
            const analyticsData = analyticsResult.status === 'fulfilled'
                ? (analyticsResult.value?.data ?? analyticsResult.value)
                : null;

            if (overviewData) {
                this.renderHighlights(overviewData);
            } else {
                this.renderHighlights(null);
            }

            this.renderReorders(reorderData);
            this.renderAlerts(alertsData, overviewData);
            this.renderSeasonality(seasonalityData, seasonalityResult.status === 'rejected');
            this.renderCriticalAlertsBanner(alertsData);
            this.renderAnalyticsSection(analyticsData, overviewData);

            this.lastOverview = overviewData;
            this.lastReorders = reorderData;
            this.lastAlerts = alertsData;
            this.lastSeasonality = seasonalityData;
            this.lastAnalytics = analyticsData;

            if (overviewResult.status === 'rejected' || reorderResult.status === 'rejected' || alertsResult.status === 'rejected') {
                console.warn('Some forecasting sections failed to load', {
                    overviewError: overviewResult.status === 'rejected' ? overviewResult.reason : null,
                    reorderError: reorderResult.status === 'rejected' ? reorderResult.reason : null,
                    alertsError: alertsResult.status === 'rejected' ? alertsResult.reason : null
                });
                this.notify('Some forecasting sections could not load completely.', 'warning');
            }
        } catch (error) {
            console.error('Failed to load forecasting data', error);
            this.notify('Unable to load forecasting data', 'error');
            this.renderSeasonality([], true);
        } finally {
            this.setLoadingState(false);
        }
    }

    async ensureChartLibrary() {
        if (typeof window.Chart !== 'undefined') {
            return;
        }

        if (!ForecastingPage.chartPromise) {
            ForecastingPage.chartPromise = new Promise((resolve, reject) => {
                const existingScript = document.querySelector('script[data-chartjs]');

                const handleLoad = () => {
                    if (typeof window.Chart !== 'undefined') {
                        resolve();
                    } else {
                        reject(new Error('Chart.js loaded but Chart is undefined'));
                    }
                };

                const handleError = () => reject(new Error('Chart.js failed to load'));

                if (existingScript) {
                    existingScript.addEventListener('load', handleLoad, { once: true });
                    existingScript.addEventListener('error', handleError, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.async = true;
                script.dataset.chartjs = 'true';
                script.addEventListener('load', handleLoad, { once: true });
                script.addEventListener('error', handleError, { once: true });
                document.head.appendChild(script);
            });
        }

        await ForecastingPage.chartPromise;

        if (typeof window.Chart === 'undefined') {
            throw new Error('Chart.js is unavailable');
        }
    }

    async handleManualRefresh() {
        try {
            await this.loadAllData(true);
            this.notify('Forecasts refreshed', 'success');
        } catch (error) {
            console.error('Manual refresh failed', error);
            this.notify('Unable to refresh forecasts', 'error');
        }
    }

    async handleExport() {
        if (!this.exportButton) return;

        const hasData = Array.isArray(this.lastReorders) && this.lastReorders.length > 0;
        if (!hasData) {
            this.notify('Load forecasts before exporting a report.', 'warning');
            return;
        }

        this.toggleExportLoading(true);

        try {
            const workbookHtml = this.buildExcelWorkbook();
            const blob = new Blob(['\ufeff' + workbookHtml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
            const timestamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
            this.triggerBlobDownload(blob, `forecast-report-${timestamp}.xls`);
            this.notify('Forecast report exported', 'success');
        } catch (error) {
            console.error('Failed to export forecast report', error);
            this.notify('Failed to export forecast report', 'error');
        } finally {
            this.toggleExportLoading(false);
        }
    }

    toggleExportLoading(isLoading) {
        if (!this.exportButton) return;
        this.exportButton.disabled = isLoading;
        this.exportButton.classList.toggle('opacity-70', isLoading);
        this.exportButton.classList.toggle('cursor-not-allowed', isLoading);

        const icon = this.exportButton.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-spin', isLoading);
        }
    }

    buildExcelWorkbook() {
        const generatedAt = this.lastOverview?.generated_at
            ? new Date(this.lastOverview.generated_at).toLocaleString()
            : new Date().toLocaleString();

        const sections = [];

        sections.push(this.generateTableSection('Report Details', ['Field', 'Value'], [
            ['Generated', generatedAt],
            ['Data Sources', 'Gemini AI Forecasting Module'],
            ['Items with Forecasts', (this.lastReorders?.length ?? 0).toString()]
        ]));

        if (this.lastOverview?.summary && Object.keys(this.lastOverview.summary).length > 0) {
            const summaryRows = Object.entries(this.lastOverview.summary).map(([key, value]) => [
                this.titleCase(key),
                value ?? 0
            ]);
            sections.push(this.generateTableSection('Summary Metrics', ['Metric', 'Value'], summaryRows));
        }

        if (Array.isArray(this.lastReorders) && this.lastReorders.length > 0) {
            const reorderRows = this.lastReorders.map(item => [
                item.item_code,
                item.name,
                item.current_stock ?? '',
                item.average_daily_usage ?? '',
                item.projected_runout_days ?? '',
                item.recommended_reorder_qty ?? '',
                this.titleCase(item.priority)
            ]);
            sections.push(this.generateTableSection(
                'Reorder Recommendations',
                ['Item Code', 'Supply', 'Current Stock', 'Avg Daily Usage', 'Runout (days)', 'Suggested Order', 'Priority'],
                reorderRows
            ));
        }

        if (Array.isArray(this.lastAlerts) && this.lastAlerts.length > 0) {
            const alertRows = this.lastAlerts.map(alert => [
                alert.title,
                alert.message,
                this.titleCase(alert.severity),
                alert.current_stock ?? '',
                alert.recommended_reorder_qty ?? ''
            ]);
            sections.push(this.generateTableSection(
                'Alerts & Warnings',
                ['Title', 'Message', 'Severity', 'Current Stock', 'Suggested Order'],
                alertRows
            ));
        }

        if (Array.isArray(this.lastSeasonality) && this.lastSeasonality.length > 0) {
            const seasonalityRows = this.lastSeasonality.map(item => [
                item.period,
                item.usage_qty,
                item.variation_percent,
                this.titleCase(item.classification)
            ]);
            sections.push(this.generateTableSection(
                'Seasonal Usage Trends',
                ['Period', 'Usage Qty', 'Variation %', 'Classification'],
                seasonalityRows
            ));
        }

        const bodyContent = sections.join('<br />');

        return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #0f172a; }
        .forecast-export-table { border-collapse: collapse; width: 100%; margin-bottom: 18px; }
        .forecast-export-table caption { text-align: left; font-weight: 600; margin-bottom: 6px; color: #0f172a; font-size: 14px; }
        .forecast-export-table th, .forecast-export-table td { border: 1px solid #d1d5db; padding: 8px 10px; }
        .forecast-export-table th { background: #eff6ff; font-weight: 600; }
    </style>
</head>
<body>
    <h1>Gemini AI Forecasting Report</h1>
    <p>Generated on <strong>${this.escapeHtml(generatedAt)}</strong></p>
    ${bodyContent}
</body>
</html>`;
    }

    generateTableSection(title, headers, rows) {
        const headerHtml = headers
            .map(header => `<th>${this.escapeHtml(header)}</th>`)
            .join('');

        const bodyHtml = Array.isArray(rows) && rows.length > 0
            ? rows.map(row => `<tr>${row.map(cell => `<td>${this.escapeHtml(cell)}</td>`).join('')}</tr>`).join('')
            : `<tr><td colspan="${headers.length}">No data available</td></tr>`;

        return `
            <table class="forecast-export-table">
                <caption>${this.escapeHtml(title)}</caption>
                <thead><tr>${headerHtml}</tr></thead>
                <tbody>${bodyHtml}</tbody>
            </table>
        `;
    }

    escapeHtml(value) {
        return (value ?? '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    titleCase(text = '') {
        return text
            .toString()
            .split('_')
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');
    }

    setLoadingState(isLoading) {
        if (!this.refreshButton) return;
        this.refreshButton.disabled = isLoading;
        const icon = this.refreshButton.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-spin', isLoading);
        }
    }

    renderHighlights(overview) {
        if (!this.highlightsContainer) return;
        this.highlightsContainer.innerHTML = '';

        if (!overview || !overview.summary || Object.keys(overview.summary).length === 0) {
            this.highlightsContainer.innerHTML = `
                <div class="forecast-empty">
                    <h3 class="text-lg font-semibold text-slate-700">No forecast data yet</h3>
                    <p class="text-slate-500 text-sm mt-2 max-w-md mx-auto">
                        Record supply transactions to enable AI forecasting insights.
                    </p>
                </div>
            `;
            return;
        }

        const cards = [
            {
                title: 'Supplies tracked',
                value: overview.summary.total_supplies ?? 0,
                icon: 'boxes-stacked',
                accent: 'bg-indigo-100 text-indigo-700'
            },
            {
                title: '30-day demand projection',
                value: overview.summary.forecasted_usage ?? 0,
                format: 'number',
                icon: 'chart-line',
                accent: 'bg-emerald-100 text-emerald-700'
            },
            {
                title: 'Critical low-stock items',
                value: overview.summary.critical_low_stock ?? 0,
                icon: 'triangle-exclamation',
                accent: 'bg-rose-100 text-rose-700'
            },
            {
                title: 'Seasonal trends detected',
                value: overview.summary.seasonal_trends ?? 0,
                icon: 'calendar-alt',
                accent: 'bg-sky-100 text-sky-700'
            }
        ];

        this.highlightsContainer.innerHTML = cards.map(card => `
            <article class="forecast-card">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl ${card.accent}">
                    <i class="fas fa-${card.icon}"></i>
                </span>
                <div>
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">${card.title}</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">${this.formatValue(card.value, card.format)}</p>
                </div>
            </article>
        `).join('');
    }

    renderReorders(data) {
        if (!this.reorderTableBody) return;

        if (!Array.isArray(data) || data.length === 0) {
            this.reorderTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-10 text-slate-500 text-sm">
                        No reorder recommendations yet.
                    </td>
                </tr>
            `;
            return;
        }

        const uniqueItems = this.deduplicateReorders(data);
        if (uniqueItems.length === 0) {
            this.reorderTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-10 text-slate-500 text-sm">
                        No reorder recommendations yet.
                    </td>
                </tr>
            `;
            return;
        }

        this.reorderTableBody.innerHTML = uniqueItems.map(item => `
            <tr>
                <td>
                    <div class="font-semibold text-slate-800">${item.name}</div>
                    <div class="text-xs text-slate-500">${item.item_code}</div>
                </td>
                <td>${item.current_stock}</td>
                <td>${item.average_daily_usage ?? 0}</td>
                <td>${item.projected_runout_days ?? '—'}</td>
                <td class="font-semibold">${item.recommended_reorder_qty}</td>
                <td>${this.renderPriorityChip(item.priority)}</td>
            </tr>
        `).join('');
    }

    deduplicateReorders(items) {
        const normalized = Array.isArray(items) ? items : [];
        const result = [];
        const indexByKey = new Map();

        normalized.forEach(item => {
            const key = this.normalizeReorderKey(item);
            if (!key) {
                result.push(item);
                return;
            }

            if (!indexByKey.has(key)) {
                indexByKey.set(key, result.length);
                result.push(item);
                return;
            }

            const existingIndex = indexByKey.get(key);
            const existing = result[existingIndex];
            if (this.isHigherPriorityReorder(item, existing)) {
                result[existingIndex] = item;
            }
        });

        return result;
    }

    normalizeReorderKey(item) {
        const nameKey = typeof item?.name === 'string' ? item.name.trim().toLowerCase() : '';
        if (nameKey) return nameKey;
        const codeKey = typeof item?.item_code === 'string' ? item.item_code.trim().toLowerCase() : '';
        return codeKey || '';
    }

    isHigherPriorityReorder(candidate, existing) {
        const candidateWeight = this.priorityWeight(candidate?.priority);
        const existingWeight = this.priorityWeight(existing?.priority);
        if (candidateWeight !== existingWeight) {
            return candidateWeight > existingWeight;
        }

        const candidateQty = Number(candidate?.recommended_reorder_qty ?? 0);
        const existingQty = Number(existing?.recommended_reorder_qty ?? 0);
        if (candidateQty !== existingQty) {
            return candidateQty > existingQty;
        }

        const candidateStock = Number(candidate?.current_stock ?? 0);
        const existingStock = Number(existing?.current_stock ?? 0);
        return candidateStock > existingStock;
    }

    priorityWeight(priority) {
        const value = (priority || '').toLowerCase();
        switch (value) {
            case 'critical':
                return 4;
            case 'high':
                return 3;
            case 'medium':
                return 2;
            case 'overstock':
                return 1;
            default:
                return 0;
        }
    }

    renderPriorityChip(priority) {
        const map = {
            critical: 'chip-critical',
            high: 'chip-warning',
            overstock: 'chip-info',
            medium: 'chip-normal',
            low: 'chip-normal'
        };
        const label = {
            critical: 'Critical',
            high: 'High',
            overstock: 'Overstock',
            medium: 'Medium',
            low: 'Stable'
        };
        return `<span class="forecast-chip ${map[priority] || 'chip-normal'}">${label[priority] || 'Medium'}</span>`;
    }

    renderAlerts(alerts, overview) {
        if (!this.alertsList) return;

        this.generatedAtLabel.textContent = overview?.generated_at ? new Date(overview.generated_at).toLocaleString() : '—';

        if (!Array.isArray(alerts) || alerts.length === 0) {
            this.alertsList.innerHTML = `
                <div class="forecast-empty text-sm">
                    <h3 class="text-base font-semibold text-slate-700">No active alerts</h3>
                    <p class="text-slate-500 mt-2">All monitored supplies are within safe stock levels.</p>
                </div>
            `;
            return;
        }

        this.alertsList.innerHTML = alerts.map(alert => `
            <div class="p-4 rounded-2xl border ${this.alertBorder(alert.severity)} bg-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">${alert.title}</p>
                        <p class="text-sm text-slate-600 mt-1">${alert.message}</p>
                    </div>
                    <span class="forecast-chip ${this.alertChip(alert.severity)}">${this.alertLabel(alert.severity)}</span>
                </div>
                ${alert.recommended_reorder_qty !== undefined ? `
                <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-slate-500">
                    <div>
                        <p class="font-semibold text-slate-700">Current Stock</p>
                        <p>${alert.current_stock}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-700">Suggested Order</p>
                        <p>${alert.recommended_reorder_qty}</p>
                    </div>
                </div>
                ` : ''}
            </div>
        `).join('');
    }

    alertBorder(severity) {
        return severity === 'critical'
            ? 'border-rose-200'
            : severity === 'high'
                ? 'border-amber-200'
                : severity === 'overstock'
                    ? 'border-sky-200'
                    : 'border-emerald-200';
    }

    alertChip(severity) {
        return severity === 'critical'
            ? 'chip-critical'
            : severity === 'high'
                ? 'chip-warning'
                : severity === 'overstock'
                    ? 'chip-info'
                    : 'chip-normal';
    }

    alertLabel(severity) {
        switch (severity) {
            case 'critical':
                return 'Critical';
            case 'high':
                return 'High';
            case 'overstock':
                return 'Overstock';
            default:
                return 'Normal';
        }
    }

    renderSeasonality(data, hasError = false) {
        if (!this.seasonalityChartCanvas) return;

        if (hasError) {
            this.seasonalityChartCanvas.textContent = 'We could not load seasonal usage trends right now.';
            return;
        }

        if (!Array.isArray(data) || data.length === 0) {
            this.seasonalityChartCanvas.textContent = 'Not enough data to identify seasonal usage.';
            return;
        }

        const labels = data.map(item => item.period);
        const usage = data.map(item => item.usage_qty);
        const variations = data.map(item => item.variation_percent);

        try {
            const canvas = document.createElement('canvas');
            this.seasonalityChartCanvas.innerHTML = '';
            this.seasonalityChartCanvas.appendChild(canvas);

            if (this.chart) {
                this.chart.destroy();
            }

            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Monthly usage',
                            data: usage,
                            borderColor: '#1d4ed8',
                            backgroundColor: 'rgba(29, 78, 216, 0.15)',
                            tension: 0.35,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Variation %',
                            data: variations,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                            tension: 0.3,
                            fill: false,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Units used' }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: false,
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Variation %' }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { usePointStyle: true }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Failed to render seasonal chart', error);
            this.seasonalityChartCanvas.textContent = 'We could not render the seasonal chart. Check console logs for details.';
            this.chart = null;
            return;
        }

        if (this.seasonalityBreakdown) {
            this.seasonalityBreakdown.innerHTML = data.map(item => `
                <div class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">${item.period}</p>
                        <p class="text-xs text-slate-500">${item.classification.toUpperCase()}</p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-semibold text-slate-800">${item.usage_qty} units</p>
                        <p class="text-xs ${item.variation_percent >= 0 ? 'text-emerald-600' : 'text-rose-600'}">
                            ${item.variation_percent >= 0 ? '+' : ''}${item.variation_percent}% vs avg
                        </p>
                    </div>
                </div>
            `).join('');
        }
    }

    // ---------------------------------------------------------------
    // Critical Alerts Banner
    // ---------------------------------------------------------------

    renderCriticalAlertsBanner(alerts) {
        if (!this.criticalAlertsBanner) return;

        const criticalAlerts = Array.isArray(alerts)
            ? alerts.filter(a => a.severity === 'critical')
            : [];

        if (criticalAlerts.length === 0) {
            this.criticalAlertsBanner.innerHTML = '';
            this.criticalAlertsBanner.classList.add('hidden');
            return;
        }

        this.criticalAlertsBanner.classList.remove('hidden');
        this.criticalAlertsBanner.innerHTML = `
            <div class="forecast-critical-banner">
                <div class="forecast-critical-banner__header">
                    <span class="forecast-critical-banner__icon">
                        <i class="fas fa-circle-exclamation"></i>
                    </span>
                    <div>
                        <p class="forecast-critical-banner__title">
                            ${criticalAlerts.length} Critical Stock Alert${criticalAlerts.length !== 1 ? 's' : ''} Require Immediate Attention
                        </p>
                        <p class="forecast-critical-banner__subtitle">
                            The following supplies are at imminent risk of stockout. Immediate procurement action is required.
                        </p>
                    </div>
                </div>
                <div class="forecast-critical-banner__items">
                    ${criticalAlerts.map(alert => `
                        <div class="forecast-critical-banner__item">
                            <div class="forecast-critical-banner__item-header">
                                <span class="forecast-critical-banner__severity-dot forecast-critical-banner__severity-dot--critical"></span>
                                <p class="forecast-critical-banner__item-title">${this.escapeHtml(alert.title)}</p>
                                <span class="forecast-chip chip-critical ml-auto">Critical</span>
                            </div>
                            <p class="forecast-critical-banner__item-message">${this.escapeHtml(alert.message)}</p>
                            ${alert.recommended_action ? `
                            <div class="forecast-critical-banner__action">
                                <i class="fas fa-bolt text-rose-500 shrink-0"></i>
                                <p><span class="font-semibold">Action:</span> ${this.escapeHtml(alert.recommended_action)}</p>
                            </div>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // ---------------------------------------------------------------
    // Data Analytics Section
    // ---------------------------------------------------------------

    renderAnalyticsSection(analytics, overview) {
        if (!this.analyticsSection) return;

        const trendInfo   = overview?.analytics ?? null;
        const topItems    = analytics?.top_consuming ?? [];
        const costTrend   = analytics?.cost_trend ?? [];
        const savings     = analytics?.savings_opportunities ?? [];
        const turnover    = analytics?.turnover_rates ?? [];

        const hasData = topItems.length > 0 || costTrend.length > 0;

        if (!hasData) {
            this.analyticsSection.innerHTML = `
                <div class="forecast-empty">
                    <h3 class="text-lg font-semibold text-slate-700">Analytics data not available</h3>
                    <p class="text-slate-500 text-sm mt-2 max-w-md mx-auto">
                        Record supply transactions to unlock data analytics insights.
                    </p>
                </div>
            `;
            return;
        }

        // Trend indicator
        const trendHtml = trendInfo ? this.renderTrendIndicator(trendInfo) : '';

        // Top consuming supplies list
        const topConsumingHtml = topItems.length > 0 ? `
            <div class="forecast-analytics__card">
                <h3 class="forecast-analytics__card-title">
                    <i class="fas fa-fire-flame-curved text-orange-500"></i>
                    Top Consuming Supplies
                </h3>
                <div class="space-y-3">
                    ${topItems.map((item, idx) => `
                        <div class="forecast-analytics__top-item">
                            <span class="forecast-analytics__rank">${idx + 1}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">${this.escapeHtml(item.name)}</p>
                                <p class="text-xs text-slate-500">${this.escapeHtml(item.item_code)}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-slate-900">${new Intl.NumberFormat().format(item.forecast_30_day)} units</p>
                                <p class="text-xs text-slate-500">30-day forecast</p>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : '';

        // Cost projections
        const costProjectionHtml = costTrend.length > 0 ? (() => {
            const totalCost = costTrend.reduce((sum, m) => sum + (m.estimated_cost ?? 0), 0);
            const lastThree = costTrend.slice(-3);
            const recentAvg = lastThree.length > 0
                ? lastThree.reduce((s, m) => s + (m.estimated_cost ?? 0), 0) / lastThree.length
                : 0;

            return `
                <div class="forecast-analytics__card">
                    <h3 class="forecast-analytics__card-title">
                        <i class="fas fa-peso-sign text-emerald-600"></i>
                        Cost Trend Analysis
                    </h3>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="forecast-analytics__metric">
                            <p class="forecast-analytics__metric-label">Total period cost</p>
                            <p class="forecast-analytics__metric-value">&#8369;${new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(totalCost)}</p>
                        </div>
                        <div class="forecast-analytics__metric">
                            <p class="forecast-analytics__metric-label">Recent monthly avg</p>
                            <p class="forecast-analytics__metric-value">&#8369;${new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(recentAvg)}</p>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        ${costTrend.slice(-6).map(month => {
                            const maxCost = Math.max(...costTrend.map(m => m.estimated_cost ?? 0), 1);
                            const barWidth = maxCost > 0 ? Math.round(((month.estimated_cost ?? 0) / maxCost) * 100) : 0;
                            const barColor = month.classification === 'peak' ? 'bg-rose-400' :
                                             month.classification === 'low'  ? 'bg-sky-400' : 'bg-emerald-400';
                            return `
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-16 shrink-0 text-slate-500 font-medium">${this.escapeHtml(month.period)}</span>
                                    <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="${barColor} h-2 rounded-full transition-all" style="width:${barWidth}%"></div>
                                    </div>
                                    <span class="w-20 shrink-0 text-right font-semibold text-slate-700">
                                        &#8369;${new Intl.NumberFormat('en-PH', { maximumFractionDigits: 0 }).format(month.estimated_cost ?? 0)}
                                    </span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        })() : '';

        // Savings opportunities
        const savingsHtml = savings.length > 0 ? `
            <div class="forecast-analytics__card forecast-analytics__card--savings">
                <h3 class="forecast-analytics__card-title">
                    <i class="fas fa-piggy-bank text-emerald-600"></i>
                    Projected Savings Opportunities
                </h3>
                <div class="space-y-3">
                    ${savings.map(op => {
                        const isEarlyReorder = op.type === 'early_reorder';
                        const iconClass = isEarlyReorder ? 'fa-clock text-amber-500' : 'fa-arrow-trend-down text-emerald-500';
                        return `
                            <div class="forecast-analytics__saving-item">
                                <i class="fas ${iconClass} shrink-0 mt-0.5"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-800">${this.escapeHtml(op.name)}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">${this.escapeHtml(op.description)}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold text-emerald-700">
                                        <i class="fas fa-arrow-up text-emerald-500 text-xs"></i>
                                        &#8369;${new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(op.potential_saving)}
                                    </p>
                                    <p class="text-xs text-slate-400">potential saving</p>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        ` : '';

        // Supply turnover rates
        const turnoverHtml = turnover.length > 0 ? `
            <div class="forecast-analytics__card">
                <h3 class="forecast-analytics__card-title">
                    <i class="fas fa-arrows-rotate text-indigo-600"></i>
                    Supply Turnover Rates
                </h3>
                <div class="space-y-2">
                    ${turnover.slice(0, 6).map(item => {
                        const chipClass = item.classification === 'fast_moving' ? 'chip-normal' :
                                          item.classification === 'slow_moving' ? 'chip-warning' : 'chip-info';
                        const chipLabel = item.classification === 'fast_moving' ? 'Fast' :
                                          item.classification === 'slow_moving' ? 'Slow' : 'Normal';
                        const trendIcon = item.classification === 'fast_moving'
                            ? '<i class="fas fa-arrow-trend-up text-emerald-500"></i>'
                            : item.classification === 'slow_moving'
                                ? '<i class="fas fa-arrow-trend-down text-rose-500"></i>'
                                : '<i class="fas fa-minus text-slate-400"></i>';
                        return `
                            <div class="flex items-center gap-3 py-2 border-b border-slate-100 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 truncate">${this.escapeHtml(item.name)}</p>
                                    <p class="text-xs text-slate-500">Annual forecast: ${new Intl.NumberFormat().format(item.annual_forecast)} units</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    ${trendIcon}
                                    <span class="text-sm font-bold text-slate-700">${item.turnover_rate}x</span>
                                    <span class="forecast-chip ${chipClass}">${chipLabel}</span>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        ` : '';

        this.analyticsSection.innerHTML = `
            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold text-slate-900">Data Analytics</h2>
                    ${trendHtml}
                </div>
                <div class="forecast-analytics__grid">
                    ${topConsumingHtml}
                    ${costProjectionHtml}
                    ${savingsHtml}
                    ${turnoverHtml}
                </div>
            </div>
        `;
    }

    renderTrendIndicator(trendInfo) {
        const dir     = trendInfo.trend_direction ?? 'stable';
        const pct     = trendInfo.percentage_change ?? 0;
        const conf    = trendInfo.confidence_score ?? 0;

        const dirConfig = {
            up:     { icon: 'fa-arrow-trend-up',   color: 'text-rose-600',    bg: 'bg-rose-50',    label: 'Increasing demand' },
            down:   { icon: 'fa-arrow-trend-down',  color: 'text-emerald-600', bg: 'bg-emerald-50', label: 'Decreasing demand' },
            stable: { icon: 'fa-minus',              color: 'text-slate-600',   bg: 'bg-slate-50',   label: 'Stable demand' }
        };

        const cfg = dirConfig[dir] ?? dirConfig.stable;
        const pctLabel = pct > 0 ? `+${pct}%` : `${pct}%`;

        return `
            <div class="flex items-center gap-3 flex-wrap">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl ${cfg.bg} border border-slate-200">
                    <i class="fas ${cfg.icon} ${cfg.color} text-sm"></i>
                    <span class="text-sm font-semibold ${cfg.color}">${cfg.label}</span>
                    <span class="text-xs font-bold ${cfg.color}">(${pctLabel})</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-100">
                    <i class="fas fa-brain text-indigo-500 text-xs"></i>
                    <span class="text-xs font-semibold text-indigo-600">Confidence: ${conf}%</span>
                </div>
            </div>
        `;
    }

    formatValue(value, type) {
        if (type === 'number') {
            return new Intl.NumberFormat().format(value ?? 0);
        }
        return value ?? 0;
    }

    notify(message, type = 'info') {
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            console.log(`[${type}]`, message);
        }
    }
}

window.addEventListener('DOMContentLoaded', () => {
    new ForecastingPage();
});
