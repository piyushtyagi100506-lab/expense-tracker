<?php
/**
 * Savings Goals Tracker View & Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Empowers users to set financial targets and track progressive deposits over time.
 * - Dynamically updates goal progress percentage and remaining target deficits.
 * - Interactive deposit modal for incrementally allocating savings toward specific goals.
 * - Automatically flags goals as 'completed' once the target is met or exceeded.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Savings Goals';
$activePage = 'goals';
$userId = get_user_id();

// Fetch all savings goals for the user
$stmt = $pdo->prepare("
    SELECT * FROM savings_goals 
    WHERE user_id = :user_id 
    ORDER BY status ASC, target_date ASC, id DESC
");
$stmt->execute([':user_id' => $userId]);
$goals = $stmt->fetchAll();

// Aggregates
$totalTarget = array_sum(array_column($goals, 'target_amount'));
$totalSaved  = array_sum(array_column($goals, 'saved_amount'));
$overallPct  = ($totalTarget > 0) ? round(($totalSaved / $totalTarget) * 100, 1) : 0;

include __DIR__ . '/../includes/header.php';
?>

<div class="goals-page">
    <div class="page-intro-header flex-between flex-wrap gap-3">
        <div>
            <h2>Savings Goals Tracker</h2>
            <p class="text-muted">Set financial milestones, track your incremental deposits, and reach your targets.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openCreateGoalModal();">
            <i class="fa-solid fa-plus"></i> New Savings Goal
        </button>
    </div>

    <!-- Overall Savings Summary Widget -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="budget-stat-row">
                <div class="budget-stat-item">
                    <span class="label">Total Cumulative Goals</span>
                    <span class="val"><?= format_currency($totalTarget) ?></span>
                </div>
                <div class="budget-stat-item">
                    <span class="label">Total Accumulated Savings</span>
                    <span class="val text-success"><?= format_currency($totalSaved) ?></span>
                </div>
                <div class="budget-stat-item">
                    <span class="label">Total Still Needed</span>
                    <span class="val text-teal"><?= format_currency(max(0, $totalTarget - $totalSaved)) ?></span>
                </div>
                <div class="budget-stat-item">
                    <span class="label">Aggregate Completion</span>
                    <span class="val"><?= e($overallPct) ?>%</span>
                </div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-track">
                    <div class="progress-bar-fill progress-teal" style="width: <?= min(100, (float)$overallPct) ?>%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Goals Grid -->
    <?php if (!empty($goals)): ?>
        <div class="goals-grid">
            <?php foreach ($goals as $g): 
                $targetAmt = (float)$g['target_amount'];
                $savedAmt  = (float)$g['saved_amount'];
                $diff      = max(0, $targetAmt - $savedAmt);
                $pct       = ($targetAmt > 0) ? min(100, round(($savedAmt / $targetAmt) * 100, 1)) : 0;
                $isDone    = ($savedAmt >= $targetAmt) || $g['status'] === 'completed';
            ?>
                <div class="card goal-card <?= $isDone ? 'goal-completed-card' : '' ?>">
                    <div class="card-header flex-between">
                        <div>
                            <h3 class="card-title text-base font-semibold">
                                <i class="fa-solid <?= $isDone ? 'fa-circle-check text-success' : 'fa-piggy-bank text-teal' ?>"></i>
                                <?= e($g['title']) ?>
                            </h3>
                            <?php if (!empty($g['target_date'])): ?>
                                <span class="text-muted text-xs">
                                    <i class="fa-regular fa-calendar"></i> Target: <?= e(format_date($g['target_date'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-<?= $isDone ? 'success' : 'info' ?>">
                            <?= $isDone ? 'Completed' : 'In Progress' ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <?php if (!empty($g['notes'])): ?>
                            <p class="goal-notes text-sm text-muted mb-3"><?= e($g['notes']) ?></p>
                        <?php endif; ?>

                        <div class="goal-amounts-row flex-between mb-2">
                            <div>
                                <span class="text-xs text-muted block">Saved</span>
                                <span class="font-bold text-success text-lg"><?= format_currency($savedAmt) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-muted block">Target</span>
                                <span class="font-bold text-lg"><?= format_currency($targetAmt) ?></span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-bar-container mb-2">
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill <?= $isDone ? 'progress-success' : 'progress-teal' ?>" 
                                     style="width: <?= e($pct) ?>%;">
                                </div>
                            </div>
                        </div>

                        <div class="flex-between text-xs text-muted mb-4">
                            <span><?= e($pct) ?>% Achieved</span>
                            <span><?= ($diff > 0) ? format_currency($diff) . ' remaining' : 'Target reached!' ?></span>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="goal-actions-row flex-between">
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="openDepositModal(<?= (int)$g['id'] ?>, '<?= e(addslashes($g['title'])) ?>', <?= $savedAmt ?>, <?= $targetAmt ?>);">
                                <i class="fa-solid fa-hand-holding-dollar"></i> Deposit Funds
                            </button>

                            <form action="<?= e(app_url('actions/delete_goal.php')) ?>" method="POST" 
                                  onsubmit="return confirm('Delete this savings goal?');" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                <button type="submit" class="btn-icon text-danger" title="Delete Goal">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="fa-solid fa-piggy-bank empty-icon"></i>
                    <h3>No Savings Goals Configured</h3>
                    <p class="text-muted">Create a goal for an emergency fund, travel, or a gadget upgrade to begin tracking your savings.</p>
                    <button type="button" class="btn btn-primary btn-sm mt-3" onclick="openCreateGoalModal();">
                        <i class="fa-solid fa-plus"></i> Create Your First Goal
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal 1: Create New Savings Goal -->
<div class="modal" id="createGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fa-solid fa-bullseye text-teal"></i> Create Savings Goal</h4>
                <button type="button" class="modal-close" onclick="closeCreateGoalModal();">&times;</button>
            </div>
            <form action="<?= e(app_url('actions/save_goal.php')) ?>" method="POST" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="goalTitle" class="form-label">Goal Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="goalTitle" class="form-control" 
                               placeholder="e.g. Emergency Fund, Japan Trip, New Laptop" required>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="goalTarget" class="form-label">Target Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="1.00" name="target_amount" id="goalTarget" 
                                       class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="goalSaved" class="form-label">Starting Amount (<?= get_currency_symbol() ?>)</label>
                                <input type="number" step="0.01" min="0.00" name="saved_amount" id="goalSaved" 
                                       class="form-control" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="goalDate" class="form-label">Target Completion Date</label>
                        <input type="date" name="target_date" id="goalDate" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="goalNotes" class="form-label">Notes / Motivation</label>
                        <input type="text" name="notes" id="goalNotes" class="form-control" 
                               placeholder="e.g. Save $200 per month from freelance earnings">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateGoalModal();">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Deposit Funds into Goal -->
<div class="modal" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fa-solid fa-hand-holding-dollar text-success"></i> Deposit into Goal</h4>
                <button type="button" class="modal-close" onclick="closeDepositModal();">&times;</button>
            </div>
            <form action="<?= e(app_url('actions/deposit_goal.php')) ?>" method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="goal_id" id="depositGoalId">
                <div class="modal-body">
                    <p class="mb-2">Goal: <strong id="depositGoalTitle" class="text-teal">-</strong></p>
                    <div class="form-group">
                        <label for="depositAmount" class="form-label">Deposit Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <span class="currency-prefix"><?= get_currency_symbol() ?></span>
                            <input type="number" step="0.01" min="0.01" name="amount" id="depositAmount" 
                                   class="form-control" placeholder="0.00" required autofocus>
                        </div>
                        <span class="field-hint">This adds to your accumulated savings for this target.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDepositModal();">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateGoalModal() {
        document.getElementById('createGoalModal').classList.add('show');
    }
    function closeCreateGoalModal() {
        document.getElementById('createGoalModal').classList.remove('show');
    }
    function openDepositModal(id, title, saved, target) {
        document.getElementById('depositGoalId').value = id;
        document.getElementById('depositGoalTitle').textContent = title + ` (Current: $${saved.toFixed(2)} / $${target.toFixed(2)})`;
        document.getElementById('depositModal').classList.add('show');
    }
    function closeDepositModal() {
        document.getElementById('depositModal').classList.remove('show');
    }
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
