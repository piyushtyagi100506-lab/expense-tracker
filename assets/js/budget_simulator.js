/**
 * Personal Expense Tracker - Interactive Budget & Savings Simulator
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Moves what-if financial calculations from server roundtrips to client-side JavaScript.
 * - Interactive range slider for testing spending adjustments with instant visual feedback.
 * - Dynamic color transitions: Safe (<70% green), Moderate (70-90% yellow), Over-Budget (>=100% red pulse).
 * - Client-side daily budget burn-rate calculations based on remaining calendar days.
 * - Real-time milestone goal projections: Calculates required weekly and monthly savings in browser.
 */

document.addEventListener('DOMContentLoaded', function () {
    // =========================================================================
    // 1. What-If Monthly Budget Scenario Simulator
    // =========================================================================
    const simSlider = document.getElementById('budgetSimSlider');
    const simSliderVal = document.getElementById('budgetSimVal');
    const simRemainingEl = document.getElementById('simRemainingVal');
    const simPercentEl = document.getElementById('simPercentVal');
    const simDailyEl = document.getElementById('simDailyVal');
    const simProgressBar = document.getElementById('simProgressBar');
    const simStatusBanner = document.getElementById('simStatusBanner');

    if (simSlider && simRemainingEl) {
        // Base financial parameters passed from PHP via data attributes
        const baseBudget = parseFloat(simSlider.getAttribute('data-budget')) || 2800.00;
        const currentSpent = parseFloat(simSlider.getAttribute('data-spent')) || 1586.19;
        const currencySymbol = window.CURRENCY_SYMBOL || '$';

        // Calculate days remaining in the current month
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth();
        const totalDaysInMonth = new Date(year, month + 1, 0).getDate();
        const currentDay = today.getDate();
        const daysRemaining = Math.max(1, totalDaysInMonth - currentDay + 1);

        function updateBudgetSimulation() {
            const simulatedExtraSpend = parseFloat(simSlider.value) || 0;
            const simulatedTotalSpent = currentSpent + simulatedExtraSpend;
            const remainingBudget = baseBudget - simulatedTotalSpent;
            const percentUsed = ((simulatedTotalSpent / baseBudget) * 100);

            // Update UI elements
            if (simSliderVal) {
                simSliderVal.textContent = (simulatedExtraSpend >= 0 ? '+' : '') + currencySymbol + simulatedExtraSpend.toFixed(2);
            }

            if (simRemainingEl) {
                simRemainingEl.textContent = (remainingBudget >= 0 ? '' : '-') + currencySymbol + Math.abs(remainingBudget).toFixed(2);
                simRemainingEl.className = 'sim-metric-val ' + (remainingBudget >= 0 ? 'text-success' : 'text-danger');
            }

            if (simPercentEl) {
                simPercentEl.textContent = percentUsed.toFixed(1) + '%';
                simPercentEl.className = 'sim-metric-val ' + (percentUsed <= 80 ? 'text-success' : (percentUsed <= 100 ? 'text-warning' : 'text-danger'));
            }

            // Daily allowance calculation
            if (simDailyEl) {
                const dailyAllowance = Math.max(0, remainingBudget / daysRemaining);
                simDailyEl.textContent = currencySymbol + dailyAllowance.toFixed(2) + '/day';
            }

            // Update progress bar width and color class
            if (simProgressBar) {
                simProgressBar.style.width = Math.min(100, Math.max(0, percentUsed)) + '%';
                simProgressBar.className = 'progress-bar-fill ' + 
                    (percentUsed < 70 ? 'progress-success' : 
                    (percentUsed <= 95 ? 'progress-warning' : 'progress-danger'));
            }

            // Dynamic status banner updates
            if (simStatusBanner) {
                if (percentUsed < 70) {
                    simStatusBanner.className = 'sim-status-banner sim-status-safe';
                    simStatusBanner.innerHTML = `
                        <i class="fa-solid fa-circle-check"></i>
                        <span><strong>Optimal Financial Health!</strong> You have plenty of budget headroom for discretionary spending.</span>
                    `;
                } else if (percentUsed <= 95) {
                    simStatusBanner.className = 'sim-status-banner sim-status-warning';
                    simStatusBanner.innerHTML = `
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><strong>Caution: Nearing Spending Limit!</strong> You have consumed over 70% of your allocated monthly cap.</span>
                    `;
                } else {
                    simStatusBanner.className = 'sim-status-banner sim-status-danger';
                    simStatusBanner.innerHTML = `
                        <i class="fa-solid fa-circle-radiation"></i>
                        <span><strong>Budget Breach Alert!</strong> This simulated spending exceeds your established budget limit.</span>
                    `;
                }
            }
        }

        simSlider.addEventListener('input', updateBudgetSimulation);
        updateBudgetSimulation();
    }

    // =========================================================================
    // 2. Interactive Client-Side Savings Goals Milestone Estimator
    // =========================================================================
    const goalTargetInput = document.getElementById('calcGoalTarget');
    const goalSavedInput = document.getElementById('calcGoalSaved');
    const goalDateInput = document.getElementById('calcGoalDate');
    const calcDailyOutput = document.getElementById('calcGoalDaily');
    const calcWeeklyOutput = document.getElementById('calcGoalWeekly');
    const calcMonthlyOutput = document.getElementById('calcGoalMonthly');
    const calcDaysOutput = document.getElementById('calcGoalDaysRemaining');

    if (goalTargetInput && goalDateInput) {
        function calculateGoalRequirements() {
            const target = parseFloat(goalTargetInput.value) || 0;
            const saved = parseFloat(goalSavedInput ? goalSavedInput.value : 0) || 0;
            const remaining = Math.max(0, target - saved);
            const targetDateStr = goalDateInput.value;
            const currencySymbol = window.CURRENCY_SYMBOL || '$';

            if (!targetDateStr) return;

            const targetTime = Date.parse(targetDateStr);
            const nowTime = new Date().getTime();
            const diffTime = targetTime - nowTime;
            const diffDays = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
            const diffWeeks = Math.max(1, diffDays / 7);
            const diffMonths = Math.max(1, diffDays / 30.41);

            const dailyNeeded = remaining / diffDays;
            const weeklyNeeded = remaining / diffWeeks;
            const monthlyNeeded = remaining / diffMonths;

            if (calcDaysOutput) calcDaysOutput.textContent = diffDays + ' days left';
            if (calcDailyOutput) calcDailyOutput.textContent = currencySymbol + dailyNeeded.toFixed(2);
            if (calcWeeklyOutput) calcWeeklyOutput.textContent = currencySymbol + weeklyNeeded.toFixed(2);
            if (calcMonthlyOutput) calcMonthlyOutput.textContent = currencySymbol + monthlyNeeded.toFixed(2);
        }

        goalTargetInput.addEventListener('input', calculateGoalRequirements);
        if (goalSavedInput) goalSavedInput.addEventListener('input', calculateGoalRequirements);
        goalDateInput.addEventListener('change', calculateGoalRequirements);
        calculateGoalRequirements();
    }
});
