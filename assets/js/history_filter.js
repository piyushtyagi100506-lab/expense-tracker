/**
 * Personal Expense Tracker - Live Transaction Filter, Search & Client-Side Exporter
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Converted from server-only round-trip filtering to instant client-side DOM processing.
 * - Real-time client-side search across Description, Category, Date, and Amount without page reload.
 * - Dynamic recalculation of Financial Aggregates (Filtered Income, Expenses, Net Balance) directly in JavaScript.
 * - Instant in-browser CSV and JSON export using JavaScript Blob and URL.createObjectURL().
 * - Digital receipt modal viewer with interactive print triggering.
 * - Fully progressive: Preserves native PHP GET query handling as a robust fallback.
 */

document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('.data-table tbody');
    if (!tableBody) return;

    const allRows = Array.from(tableBody.querySelectorAll('tr')).filter(row => !row.classList.contains('empty-row'));
    if (allRows.length === 0) return;

    // Filter controls
    const searchInput = document.getElementById('q');
    const typeSelect = document.getElementById('type');
    const categorySelect = document.getElementById('category');
    const fromDateInput = document.getElementById('from_date');
    const toDateInput = document.getElementById('to_date');
    const sortSelect = document.getElementById('sort');
    const filterForm = document.querySelector('.filter-card form');

    // Live KPI display elements
    const liveIncomeEl = document.getElementById('filteredIncomeVal');
    const liveExpenseEl = document.getElementById('filteredExpenseVal');
    const liveBalanceEl = document.getElementById('filteredBalanceVal');
    const liveCountBadge = document.getElementById('filteredCountBadge');

    // Parse transaction row data into client-side array
    const transactionsData = allRows.map((row, index) => {
        const typeBadge = row.querySelector('.badge-success, .badge-danger');
        const isIncome = typeBadge ? typeBadge.classList.contains('badge-success') : false;
        const type = isIncome ? 'income' : 'expense';
        
        const dateCell = row.cells[0]?.textContent.trim() || '';
        const categoryCell = row.querySelector('.category-tag')?.textContent.trim() || '';
        const descCell = row.cells[3]?.textContent.trim() || '';
        
        const amountText = row.querySelector('.text-right')?.textContent.replace(/[^0-9.-]/g, '') || '0';
        const amount = Math.abs(parseFloat(amountText)) || 0;
        
        // Extract raw date if available (or convert Nov 14, 2026 to comparable timestamp)
        const parsedTimestamp = Date.parse(dateCell) || (new Date()).getTime();

        return {
            element: row,
            index: index,
            type: type,
            date: dateCell,
            timestamp: parsedTimestamp,
            category: categoryCell.toLowerCase(),
            categoryRaw: categoryCell,
            description: descCell.toLowerCase(),
            descriptionRaw: descCell,
            amount: amount
        };
    });

    /**
     * Client-side instant filter execution
     */
    function applyLiveFilters() {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const selectedType = typeSelect ? typeSelect.value : 'all';
        const selectedCategoryText = categorySelect && categorySelect.selectedIndex > 0 
            ? categorySelect.options[categorySelect.selectedIndex].text.trim().toLowerCase() 
            : '';
        const fromDate = fromDateInput && fromDateInput.value ? Date.parse(fromDateInput.value) : null;
        const toDate = toDateInput && toDateInput.value ? Date.parse(toDateInput.value) + 86400000 : null; // include full day

        let visibleCount = 0;
        let sumIncome = 0;
        let sumExpense = 0;

        transactionsData.forEach(tx => {
            let matchesQuery = true;
            if (query !== '') {
                matchesQuery = tx.description.includes(query) || 
                               tx.category.includes(query) || 
                               tx.date.toLowerCase().includes(query) ||
                               tx.amount.toString().includes(query);
            }

            let matchesType = true;
            if (selectedType && selectedType !== 'all') {
                matchesType = (tx.type === selectedType);
            }

            let matchesCategory = true;
            if (selectedCategoryText !== '') {
                matchesCategory = (tx.category === selectedCategoryText);
            }

            let matchesDate = true;
            if (fromDate && tx.timestamp < fromDate) {
                matchesDate = false;
            }
            if (toDate && tx.timestamp > toDate) {
                matchesDate = false;
            }

            const isVisible = matchesQuery && matchesType && matchesCategory && matchesDate;

            if (isVisible) {
                tx.element.style.display = '';
                visibleCount++;
                if (tx.type === 'income') {
                    sumIncome += tx.amount;
                } else {
                    sumExpense += tx.amount;
                }
            } else {
                tx.element.style.display = 'none';
            }
        });

        // Update live aggregate KPI cards in DOM
        const currencySymbol = window.CURRENCY_SYMBOL || '$';
        const netBalance = sumIncome - sumExpense;

        if (liveIncomeEl) {
            liveIncomeEl.textContent = '+' + currencySymbol + sumIncome.toFixed(2);
        }
        if (liveExpenseEl) {
            liveExpenseEl.textContent = '-' + currencySymbol + sumExpense.toFixed(2);
        }
        if (liveBalanceEl) {
            liveBalanceEl.textContent = (netBalance >= 0 ? '+' : '-') + currencySymbol + Math.abs(netBalance).toFixed(2);
            liveBalanceEl.className = 'val ' + (netBalance >= 0 ? 'text-success' : 'text-danger');
        }
        if (liveCountBadge) {
            liveCountBadge.textContent = 'Showing ' + visibleCount + ' of ' + transactionsData.length + ' records';
        }

        // Show empty placeholder row if zero records match
        let emptyNotice = tableBody.querySelector('.live-filter-empty-row');
        if (visibleCount === 0) {
            if (!emptyNotice) {
                emptyNotice = document.createElement('tr');
                emptyNotice.className = 'live-filter-empty-row';
                emptyNotice.innerHTML = `
                    <td colspan="6" class="text-center py-5">
                        <div class="empty-state">
                            <i class="fa-solid fa-filter-circle-xmark empty-icon"></i>
                            <h4>No matching records found</h4>
                            <p class="text-muted text-sm">No transactions match your active instant search criteria.</p>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="resetLiveFilterBtn">
                                <i class="fa-solid fa-rotate-left"></i> Reset Instant Filters
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(emptyNotice);
                document.getElementById('resetLiveFilterBtn')?.addEventListener('click', resetAllFilters);
            } else {
                emptyNotice.style.display = '';
            }
        } else if (emptyNotice) {
            emptyNotice.style.display = 'none';
        }
    }

    /**
     * Reset all active filters
     */
    function resetAllFilters() {
        if (searchInput) searchInput.value = '';
        if (typeSelect) typeSelect.value = 'all';
        if (categorySelect) categorySelect.value = '';
        if (fromDateInput) fromDateInput.value = '';
        if (toDateInput) toDateInput.value = '';
        if (sortSelect) sortSelect.value = 'date_desc';
        applyLiveFilters();
        if (window.showToast) {
            window.showToast('All transaction filters have been reset to default.', 'info', 'Filters Cleared');
        }
    }

    // Attach instant event listeners for real-time reactivity
    if (searchInput) {
        searchInput.addEventListener('input', applyLiveFilters);
        // Clear search input icon trigger
        const clearBtn = searchInput.parentElement?.querySelector('.live-search-clear');
        if (clearBtn) {
            searchInput.addEventListener('input', function () {
                clearBtn.style.display = this.value ? 'block' : 'none';
            });
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                clearBtn.style.display = 'none';
                applyLiveFilters();
            });
        }
    }

    if (typeSelect) typeSelect.addEventListener('change', applyLiveFilters);
    if (categorySelect) categorySelect.addEventListener('change', applyLiveFilters);
    if (fromDateInput) fromDateInput.addEventListener('change', applyLiveFilters);
    if (toDateInput) toDateInput.addEventListener('change', applyLiveFilters);

    // If user clicks Reset in form
    const resetFormBtn = filterForm?.querySelector('a.btn-secondary, button[type=\"reset\"]');
    if (resetFormBtn) {
        resetFormBtn.addEventListener('click', function (e) {
            e.preventDefault();
            resetAllFilters();
        });
    }

    // =========================================================================
    // Client-Side In-Browser CSV & JSON Exporters (Zero Server Roundtrip)
    // =========================================================================
    window.exportTransactionsToCSV = function () {
        const visibleTx = transactionsData.filter(tx => tx.element.style.display !== 'none');
        if (visibleTx.length === 0) {
            alert('No records currently visible to export!');
            return;
        }

        const headers = ['Date', 'Type', 'Category', 'Description', 'Amount'];
        const csvRows = [headers.join(',')];

        visibleTx.forEach(tx => {
            const escapedDesc = '"' + tx.descriptionRaw.replace(/"/g, '""') + '"';
            const escapedCat = '"' + tx.categoryRaw.replace(/"/g, '""') + '"';
            const row = [tx.date, tx.type.toUpperCase(), escapedCat, escapedDesc, tx.amount.toFixed(2)];
            csvRows.push(row.join(','));
        });

        const csvString = csvRows.join('\r\n');
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', `Expense_Tracker_Export_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        if (window.showToast) {
            window.showToast(`Successfully exported ${visibleTx.length} transactions to CSV.`, 'success', 'Export Complete');
        }
    };

    window.exportTransactionsToJSON = function () {
        const visibleTx = transactionsData.filter(tx => tx.element.style.display !== 'none').map(tx => ({
            date: tx.date,
            type: tx.type,
            category: tx.categoryRaw,
            description: tx.descriptionRaw,
            amount: tx.amount
        }));

        const jsonString = JSON.stringify(visibleTx, null, 2);
        const blob = new Blob([jsonString], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', `Expense_Tracker_Data_${new Date().toISOString().slice(0, 10)}.json`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        if (window.showToast) {
            window.showToast(`Exported ${visibleTx.length} transactions as structured JSON.`, 'success', 'JSON Exported');
        }
    };

    // =========================================================================
    // Client-Side Interactive Digital Receipt Generator
    // =========================================================================
    window.showTransactionReceipt = function (date, type, category, desc, amount) {
        let modalContainer = document.getElementById('receiptModalContainer');
        if (!modalContainer) {
            modalContainer = document.createElement('div');
            modalContainer.id = 'receiptModalContainer';
            modalContainer.className = 'modal';
            document.body.appendChild(modalContainer);
        }

        const currencySymbol = window.CURRENCY_SYMBOL || '$';
        const formattedAmount = parseFloat(amount).toFixed(2);
        const isIncome = (type.toLowerCase() === 'income');
        const receiptId = 'RCPT-' + Math.floor(100000 + Math.random() * 900000);

        modalContainer.innerHTML = `
            <div class="modal-dialog">
                <div class="receipt-modal-card">
                    <div class="receipt-header">
                        <div class="receipt-logo"><i class="fa-solid fa-wallet"></i></div>
                        <div class="receipt-store-title">Personal Expense Tracker</div>
                        <div class="receipt-subtitle">Official Transaction Voucher &bull; WAD Project</div>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Receipt ID:</span>
                        <span class="receipt-value font-mono">${receiptId}</span>
                    </div>
                    <div class="receipt-row receipt-row-border">
                        <span class="receipt-label">Transaction Date:</span>
                        <span class="receipt-value">${date}</span>
                    </div>
                    <div class="receipt-row receipt-row-border">
                        <span class="receipt-label">Flow Type:</span>
                        <span class="receipt-value ${isIncome ? 'text-success' : 'text-danger'} font-bold">
                            ${type.toUpperCase()}
                        </span>
                    </div>
                    <div class="receipt-row receipt-row-border">
                        <span class="receipt-label">Category:</span>
                        <span class="receipt-value">${category}</span>
                    </div>
                    <div class="receipt-row receipt-row-border">
                        <span class="receipt-label">Description:</span>
                        <span class="receipt-value">${desc}</span>
                    </div>
                    <div class="receipt-total-row">
                        <span class="receipt-total-label">Total Amount:</span>
                        <span class="receipt-total-value ${isIncome ? 'text-success' : 'text-danger'}">
                            ${isIncome ? '+' : '-'}${currencySymbol}${formattedAmount}
                        </span>
                    </div>
                    <div class="receipt-barcode">
                        <div class="barcode-lines"></div>
                        <div class="barcode-text">* ${receiptId} *</div>
                    </div>
                    <div class="modal-footer mt-4" style="border:none; padding:0; display:flex; gap:10px;">
                        <button type="button" class="btn btn-secondary btn-block" onclick="document.getElementById('receiptModalContainer').classList.remove('active');">
                            Close
                        </button>
                        <button type="button" class="btn btn-primary btn-block" onclick="window.print();">
                            <i class="fa-solid fa-print"></i> Print Voucher
                        </button>
                    </div>
                </div>
            </div>
        `;

        modalContainer.classList.add('active');
    };
});
