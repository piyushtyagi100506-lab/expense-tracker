/**
 * Personal Expense Tracker - Chart.js Visualizations
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Initializes responsive Canvas charts using Chart.js library via CDN.
 * - Category Doughnut Chart: Illustrates percentage distribution of expenses across categories.
 * - Cashflow Comparison Bar Chart: Juxtaposes monthly Income vs. Expenses over time.
 * - Customizes tooltips and scale formatters to render localized currency values.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js library is not loaded.');
        return;
    }

    const data = window.chartData || {};

    // =========================================================================
    // 1. Category Expense Breakdown (Doughnut Chart)
    // =========================================================================
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && data.categories && data.categories.values && data.categories.values.length > 0) {
        // Modern curated color palette for categorical visualization
        const palette = [
            '#0d9488', // Teal
            '#10b981', // Emerald
            '#06b6d4', // Cyan
            '#0284c7', // Sky Blue
            '#6366f1', // Indigo
            '#8b5cf6', // Violet
            '#ec4899', // Pink
            '#f43f5e', // Rose
            '#f59e0b', // Amber
            '#84cc16', // Lime
            '#14b8a6', // Light Teal
            '#64748b'  // Slate
        ];

        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: data.categories.labels,
                datasets: [{
                    data: data.categories.values,
                    backgroundColor: palette.slice(0, data.categories.values.length),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: "'Inter', sans-serif", size: 13, weight: 'bold' },
                        bodyFont: { family: "'Inter', sans-serif", size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return ` ${label}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // =========================================================================
    // 2. Income vs. Expense Comparison (Grouped Bar Chart)
    // =========================================================================
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx && data.trends) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: data.trends.labels,
                datasets: [
                    {
                        label: 'Income',
                        data: data.trends.incomes,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 32
                    },
                    {
                        label: 'Expenses',
                        data: data.trends.expenses,
                        backgroundColor: 'rgba(239, 68, 68, 0.85)',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 32
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { family: "'Inter', sans-serif", size: 12 },
                            color: '#64748b'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            font: { family: "'Inter', sans-serif", size: 11 },
                            color: '#64748b',
                            callback: function (val) {
                                return '$' + Number(val).toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 12,
                            padding: 12,
                            font: { family: "'Inter', sans-serif", size: 12 },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: "'Inter', sans-serif", size: 13, weight: 'bold' },
                        bodyFont: { family: "'Inter', sans-serif", size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const datasetLabel = context.dataset.label || '';
                                const value = context.parsed.y || 0;
                                return ` ${datasetLabel}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            }
                        }
                    }
                }
            }
        });
    }
});

