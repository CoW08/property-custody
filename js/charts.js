// Simple Chart Library for Reports
class SimpleChart {
    constructor(canvas, data, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.data = data;
        this.options = {
            type: 'bar',
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'],
            padding: 20,
            showLabels: true,
            showValues: true,
            responsive: true,
            ...options
        };

        this.setupCanvas();
        this.draw();
    }

    setupCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        // Ensure minimum dimensions
        const width = Math.max(200, rect.width);
        const height = Math.max(150, rect.height);

        this.canvas.width = width * dpr;
        this.canvas.height = height * dpr;

        this.ctx.scale(dpr, dpr);
        this.canvas.style.width = width + 'px';
        this.canvas.style.height = height + 'px';

        this.width = width;
        this.height = height;
    }

    draw() {
        this.ctx.clearRect(0, 0, this.width, this.height);

        switch (this.options.type) {
            case 'bar':
                this.drawBarChart();
                break;
            case 'pie':
                this.drawPieChart();
                break;
            case 'line':
                this.drawLineChart();
                break;
            default:
                this.drawBarChart();
        }
    }

    drawBarChart() {
        if (!this.data || this.data.length === 0) return;

        const padding = this.options.padding;
        const chartWidth = this.width - (padding * 2);
        const chartHeight = this.height - (padding * 3);

        // Find max value
        const maxValue = Math.max(...this.data.map(item => item.value));
        const barWidth = chartWidth / this.data.length * 0.8;
        const barSpacing = chartWidth / this.data.length * 0.2;

        this.data.forEach((item, index) => {
            const barHeight = (item.value / maxValue) * chartHeight;
            const x = padding + (index * (barWidth + barSpacing)) + (barSpacing / 2);
            const y = this.height - padding - barHeight;

            // Draw bar
            this.ctx.fillStyle = this.options.colors[index % this.options.colors.length];
            this.ctx.fillRect(x, y, barWidth, barHeight);

            // Draw value label
            if (this.options.showValues) {
                this.ctx.fillStyle = '#374151';
                this.ctx.font = '12px sans-serif';
                this.ctx.textAlign = 'center';
                this.ctx.fillText(item.value.toString(), x + barWidth / 2, y - 5);
            }

            // Draw category label
            if (this.options.showLabels) {
                this.ctx.fillStyle = '#6B7280';
                this.ctx.font = '11px sans-serif';
                this.ctx.textAlign = 'center';

                // Truncate long labels for mobile
                let label = item.label;
                if (this.width < 640 && label.length > 8) {
                    label = label.substring(0, 8) + '...';
                }

                this.ctx.fillText(label, x + barWidth / 2, this.height - 5);
            }
        });
    }

    drawPieChart() {
        if (!this.data || this.data.length === 0) return;

        const centerX = this.width / 2;
        const centerY = this.height / 2;
        const radius = Math.max(20, Math.min(this.width, this.height) / 2 - this.options.padding - 40);

        // Ensure we have a valid radius
        if (radius <= 0) {
            console.warn('Canvas too small for pie chart');
            this.ctx.fillStyle = '#6B7280';
            this.ctx.font = '12px sans-serif';
            this.ctx.textAlign = 'center';
            this.ctx.fillText('Chart too small', centerX, centerY);
            return;
        }

        const total = this.data.reduce((sum, item) => sum + item.value, 0);
        let currentAngle = -Math.PI / 2; // Start from top

        this.data.forEach((item, index) => {
            const sliceAngle = (item.value / total) * 2 * Math.PI;

            // Draw slice
            this.ctx.beginPath();
            this.ctx.moveTo(centerX, centerY);
            this.ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
            this.ctx.closePath();
            this.ctx.fillStyle = this.options.colors[index % this.options.colors.length];
            this.ctx.fill();

            // Draw label if space allows
            if (sliceAngle > 0.2 && this.options.showLabels) {
                const labelAngle = currentAngle + sliceAngle / 2;
                const labelX = centerX + Math.cos(labelAngle) * (radius * 0.7);
                const labelY = centerY + Math.sin(labelAngle) * (radius * 0.7);

                this.ctx.fillStyle = '#FFFFFF';
                this.ctx.font = 'bold 11px sans-serif';
                this.ctx.textAlign = 'center';
                this.ctx.fillText(item.value.toString(), labelX, labelY);
            }

            currentAngle += sliceAngle;
        });

        // Draw legend for mobile
        if (this.width < 640) {
            this.drawLegend();
        }
    }

    drawLineChart() {
        if (!this.data || this.data.length === 0) return;

        const padding = this.options.padding;
        const chartWidth = this.width - (padding * 2);
        const chartHeight = this.height - (padding * 3);

        const maxValue = Math.max(...this.data.map(item => item.value));
        const minValue = Math.min(...this.data.map(item => item.value));
        const valueRange = maxValue - minValue || 1;

        // Draw axes
        this.ctx.strokeStyle = '#E5E7EB';
        this.ctx.lineWidth = 1;
        this.ctx.beginPath();
        this.ctx.moveTo(padding, padding);
        this.ctx.lineTo(padding, this.height - padding);
        this.ctx.lineTo(this.width - padding, this.height - padding);
        this.ctx.stroke();

        // Draw line
        this.ctx.strokeStyle = this.options.colors[0];
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();

        this.data.forEach((item, index) => {
            const x = padding + (index / (this.data.length - 1)) * chartWidth;
            const y = this.height - padding - ((item.value - minValue) / valueRange) * chartHeight;

            if (index === 0) {
                this.ctx.moveTo(x, y);
            } else {
                this.ctx.lineTo(x, y);
            }

            // Draw point
            this.ctx.save();
            this.ctx.fillStyle = this.options.colors[0];
            this.ctx.beginPath();
            this.ctx.arc(x, y, 3, 0, 2 * Math.PI);
            this.ctx.fill();
            this.ctx.restore();

            // Draw label
            if (this.options.showLabels && index % Math.ceil(this.data.length / 5) === 0) {
                this.ctx.fillStyle = '#6B7280';
                this.ctx.font = '10px sans-serif';
                this.ctx.textAlign = 'center';

                let label = item.label;
                if (this.width < 640 && label.length > 6) {
                    label = label.substring(0, 6) + '...';
                }

                this.ctx.fillText(label, x, this.height - 5);
            }
        });

        this.ctx.stroke();
    }

    drawLegend() {
        const legendX = 10;
        let legendY = 10;
        const itemHeight = 20;

        this.data.forEach((item, index) => {
            // Color box
            this.ctx.fillStyle = this.options.colors[index % this.options.colors.length];
            this.ctx.fillRect(legendX, legendY, 15, 15);

            // Label
            this.ctx.fillStyle = '#374151';
            this.ctx.font = '11px sans-serif';
            this.ctx.textAlign = 'left';

            let label = item.label;
            if (label.length > 15) {
                label = label.substring(0, 15) + '...';
            }

            this.ctx.fillText(`${label} (${item.value})`, legendX + 20, legendY + 12);

            legendY += itemHeight;
        });
    }

    // Responsive redraw
    resize() {
        this.setupCanvas();
        this.draw();
    }
}

// Chart factory function
function createChart(canvasId, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.error(`Canvas element with id '${canvasId}' not found`);
        return null;
    }

    // Validate data
    if (!data || !Array.isArray(data) || data.length === 0) {
        console.warn(`No data provided for chart '${canvasId}'`);
        return null;
    }

    try {
        return new SimpleChart(canvas, data, options);
    } catch (error) {
        console.error(`Error creating chart '${canvasId}':`, error);
        return null;
    }
}

// Helper function to format data for charts
function formatChartData(apiData, labelKey, valueKey) {
    if (!apiData || !Array.isArray(apiData)) return [];

    return apiData.map(item => ({
        label: item[labelKey] || 'Unknown',
        value: parseInt(item[valueKey]) || 0
    }));
}

// Responsive chart manager
class ChartManager {
    constructor() {
        this.charts = [];
        this.setupResizeListener();
    }

    addChart(chart) {
        if (chart) {
            this.charts.push(chart);
        }
    }

    setupResizeListener() {
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.charts.forEach(chart => {
                    if (chart && chart.resize) {
                        chart.resize();
                    }
                });
            }, 250);
        });
    }

    clear() {
        this.charts = [];
    }
}

// Global chart manager instance
const chartManager = new ChartManager();