<?php
/**
 * Global Footer Component
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Closes main container wrappers and page markup.
 * - Injects Floating Action Button (FAB) quick menu for fast navigation.
 * - Sets global client-side currency metadata for JavaScript calculators.
 * - Loads vanilla JavaScript modules at the end of the body to optimize rendering performance.
 */
?>
            </main> <!-- /.content-area -->

            <!-- Application Bottom Footer -->
            <footer class="app-footer">
                <div class="footer-content">
                    <p class="footer-text">
                        &copy; <?= date('Y') ?> <strong>Personal Expense Tracker</strong> &bull; Web Application Development (WAD) Course Project
                    </p>
                    <p class="footer-meta">
                        Designed with HTML5, CSS3, Vanilla JS &amp; PHP 8 PDO
                    </p>
                </div>
            </footer>
        </div> <!-- /.main-wrapper -->
    </div> <!-- /.app-layout -->

    <!-- Floating Action Button (FAB) Quick Dock -->
    <div class="fab-container">
        <button type="button" class="fab-main-btn" aria-label="Quick Actions">
            <i class="fa-solid fa-plus"></i>
        </button>
        <div class="fab-menu">
            <a href="<?= e(app_url('pages/add_transaction.php?type=income')) ?>" class="fab-item">
                <span class="fab-item-label">Add Income</span>
                <span class="fab-item-btn text-success"><i class="fa-solid fa-arrow-down"></i></span>
            </a>
            <a href="<?= e(app_url('pages/add_transaction.php?type=expense')) ?>" class="fab-item">
                <span class="fab-item-label">Add Expense</span>
                <span class="fab-item-btn text-danger"><i class="fa-solid fa-arrow-up"></i></span>
            </a>
            <a href="<?= e(app_url('pages/budget.php')) ?>" class="fab-item">
                <span class="fab-item-label">Budget Simulator</span>
                <span class="fab-item-btn text-teal"><i class="fa-solid fa-sliders"></i></span>
            </a>
            <a href="<?= e(app_url('pages/goals.php')) ?>" class="fab-item">
                <span class="fab-item-label">Savings Goals</span>
                <span class="fab-item-btn text-warning"><i class="fa-solid fa-piggy-bank"></i></span>
            </a>
        </div>
    </div>

    <!-- Client-side Currency Metadata -->
    <script>
        window.CURRENCY_SYMBOL = "<?= defined('CURRENCY_SYMBOL') ? e(CURRENCY_SYMBOL) : '$' ?>";
    </script>

    <!-- Core Application Scripts -->
    <script src="<?= e(app_url('assets/js/main.js')) ?>"></script>
    <script src="<?= e(app_url('assets/js/validation.js')) ?>"></script>
    <script src="<?= e(app_url('assets/js/history_filter.js')) ?>"></script>
    <script src="<?= e(app_url('assets/js/budget_simulator.js')) ?>"></script>
    
    <!-- Optional Page-Specific Script Injection -->
    <?php if (!empty($pageScript)): ?>
        <script src="<?= e(app_url($pageScript)) ?>"></script>
    <?php endif; ?>
</body>
</html>
