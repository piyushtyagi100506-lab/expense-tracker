/**
 * Personal Expense Tracker - Form Validation & Dynamic Category Filter
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Dynamic Category Filtering: Listens to transaction type radio changes and hides non-matching categories.
 * - Comprehensive client-side validation that checks types, positive values, and required fields.
 * - Provides immediate visual feedback to the user before submitting to server-side scripts.
 */

document.addEventListener('DOMContentLoaded', function () {
    // =========================================================================
    // 1. Transaction Form: Dynamic Category Filter & Type Switcher
    // =========================================================================
    const transactionForm = document.getElementById('transactionForm');
    const typeExpense = document.getElementById('typeExpense');
    const typeIncome = document.getElementById('typeIncome');
    const labelExpense = document.getElementById('labelTypeExpense');
    const labelIncome = document.getElementById('labelTypeIncome');
    const categorySelect = document.getElementById('category_id');

    if (categorySelect && (typeExpense || typeIncome)) {
        /**
         * Filters the category dropdown options based on the selected type ('expense' or 'income').
         */
        function filterCategories(selectedType) {
            // Update visual active classes on the toggle buttons
            if (labelExpense && labelIncome) {
                if (selectedType === 'expense') {
                    labelExpense.classList.add('active-expense');
                    labelIncome.classList.remove('active-income');
                } else {
                    labelIncome.classList.add('active-income');
                    labelExpense.classList.remove('active-expense');
                }
            }

            let currentSelectionValid = false;
            const currentSelectedValue = categorySelect.value;

            // Iterate through each category option
            for (let i = 0; i < categorySelect.options.length; i++) {
                const opt = categorySelect.options[i];
                const optType = opt.getAttribute('data-type');

                if (!optType) {
                    // Placeholder option "-- Select a Category --"
                    opt.hidden = false;
                    continue;
                }

                if (optType === selectedType) {
                    opt.hidden = false;
                    opt.disabled = false;
                    if (opt.value === currentSelectedValue) {
                        currentSelectionValid = true;
                    }
                } else {
                    opt.hidden = true;
                    opt.disabled = true;
                }
            }

            // If previously selected category does not match new type, reset selection
            if (!currentSelectionValid && currentSelectedValue !== '') {
                categorySelect.value = '';
            }
        }

        // Event listeners for radio buttons
        if (typeExpense) {
            typeExpense.addEventListener('change', function () {
                if (this.checked) filterCategories('expense');
            });
        }

        if (typeIncome) {
            typeIncome.addEventListener('change', function () {
                if (this.checked) filterCategories('income');
            });
        }

        // Initialize category filtering on page load
        const initialType = typeIncome && typeIncome.checked ? 'income' : 'expense';
        filterCategories(initialType);
    }

    // =========================================================================
    // 2. Transaction Form: Client-Side Validation
    // =========================================================================
    if (transactionForm) {
        const amountInput = document.getElementById('amount');
        const dateInput = document.getElementById('transaction_date');
        const catSelect = document.getElementById('category_id');

        function setFieldInvalid(inputEl, errorElId, message) {
            if (inputEl) inputEl.classList.add('is-invalid');
            const errorEl = document.getElementById(errorElId);
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add('show');
            }
        }

        function clearFieldInvalid(inputEl, errorElId) {
            if (inputEl) inputEl.classList.remove('is-invalid');
            const errorEl = document.getElementById(errorElId);
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.remove('show');
            }
        }

        // Live input listeners for real-time validation feedback
        if (amountInput) {
            amountInput.addEventListener('input', function () {
                const val = parseFloat(this.value);
                if (isNaN(val) || val <= 0) {
                    setFieldInvalid(this, 'amountError', 'Please enter an amount greater than 0.00');
                } else {
                    clearFieldInvalid(this, 'amountError');
                }
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', function () {
                if (!this.value) {
                    setFieldInvalid(this, 'dateError', 'Please select a valid transaction date.');
                } else {
                    clearFieldInvalid(this, 'dateError');
                }
            });
        }

        if (catSelect) {
            catSelect.addEventListener('change', function () {
                if (!this.value) {
                    setFieldInvalid(this, 'categoryError', 'Please choose a category from the list.');
                } else {
                    clearFieldInvalid(this, 'categoryError');
                }
            });
        }

        // On Submit Validation Check
        transactionForm.addEventListener('submit', function (e) {
            let isValid = true;

            // Validate Amount
            const amtVal = parseFloat(amountInput ? amountInput.value : 0);
            if (isNaN(amtVal) || amtVal <= 0) {
                setFieldInvalid(amountInput, 'amountError', 'Please enter an amount greater than 0.00');
                isValid = false;
            } else {
                clearFieldInvalid(amountInput, 'amountError');
            }

            // Validate Date
            if (dateInput && !dateInput.value) {
                setFieldInvalid(dateInput, 'dateError', 'Please select a valid transaction date.');
                isValid = false;
            } else {
                clearFieldInvalid(dateInput, 'dateError');
            }

            // Validate Category
            if (catSelect && (!catSelect.value || catSelect.value === '')) {
                setFieldInvalid(catSelect, 'categoryError', 'Please select a category for this transaction.');
                isValid = false;
            } else {
                clearFieldInvalid(catSelect, 'categoryError');
            }

            if (!isValid) {
                e.preventDefault(); // Stop form submission
            }
        });
    }

    // =========================================================================
    // 3. Monthly Budget Form Validation
    // =========================================================================
    const budgetForm = document.getElementById('budgetForm');
    if (budgetForm) {
        const budgetInput = document.getElementById('budgetAmountInput');
        budgetForm.addEventListener('submit', function (e) {
            const val = parseFloat(budgetInput ? budgetInput.value : 0);
            if (isNaN(val) || val <= 0) {
                e.preventDefault();
                if (budgetInput) budgetInput.classList.add('is-invalid');
                const err = document.getElementById('budgetAmountError');
                if (err) {
                    err.textContent = 'Please enter a budget target greater than 0.00';
                    err.classList.add('show');
                }
            }
        });
    }

    // =========================================================================
    // 4. Registration Form Validation
    // =========================================================================
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');

        registerForm.addEventListener('submit', function (e) {
            let valid = true;

            if (nameInput && nameInput.value.trim().length < 2) {
                document.getElementById('nameError').textContent = 'Name must be at least 2 characters.';
                nameInput.classList.add('is-invalid');
                valid = false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput && !emailRegex.test(emailInput.value.trim())) {
                document.getElementById('emailError').textContent = 'Please enter a valid email address.';
                emailInput.classList.add('is-invalid');
                valid = false;
            }

            if (passInput && passInput.value.length < 6) {
                document.getElementById('passwordError').textContent = 'Password must be at least 6 characters.';
                passInput.classList.add('is-invalid');
                valid = false;
            }

            if (confirmInput && passInput && confirmInput.value !== passInput.value) {
                document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
                confirmInput.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    }
});

