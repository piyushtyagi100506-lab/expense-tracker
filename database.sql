-- ============================================================================
-- Personal Expense Tracker: A Web-Based Income and Expense Management System
-- Course Project: Web Application Development (WAD)
-- Database Name: expense_tracker
-- Features: Auth, Incomes, Expenses, Budgets, Custom Categories, Goals, Subscriptions
-- Compatible with: MySQL 5.7+ / MySQL 8.0+ / MariaDB (XAMPP & WAMP phpMyAdmin)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `expense_tracker` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `expense_tracker`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Table: users
-- Stores registered user accounts with securely hashed passwords & preferences
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `savings_goals`;
DROP TABLE IF EXISTS `recurring_transactions`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT '$',
  `theme` VARCHAR(10) NOT NULL DEFAULT 'light',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: categories
-- Default system categories (user_id IS NULL) + user-created custom categories
-- ----------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL DEFAULT NULL,
  `name` VARCHAR(60) NOT NULL,
  `type` ENUM('income', 'expense') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: transactions
-- Income and Expense financial ledger records
-- ----------------------------------------------------------------------------
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `type` ENUM('income', 'expense') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` VARCHAR(255) NULL,
  `transaction_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) 
    REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: budgets
-- Monthly spending limit per user, month, and year
-- ----------------------------------------------------------------------------
CREATE TABLE `budgets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `month` TINYINT NOT NULL COMMENT '1 to 12',
  `year` SMALLINT NOT NULL COMMENT 'e.g. 2026',
  `amount` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_month_year` (`user_id`, `month`, `year`),
  CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: savings_goals
-- Targets for savings goals with progress tracking and deposits
-- ----------------------------------------------------------------------------
CREATE TABLE `savings_goals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `target_amount` DECIMAL(10,2) NOT NULL,
  `saved_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `target_date` DATE NULL,
  `notes` VARCHAR(255) NULL,
  `status` ENUM('active', 'completed') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: recurring_transactions
-- Subscriptions, recurring bills, and repeat incomes
-- ----------------------------------------------------------------------------
CREATE TABLE `recurring_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `type` ENUM('income', 'expense') NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `frequency` ENUM('weekly', 'monthly', 'yearly') NOT NULL DEFAULT 'monthly',
  `next_due_date` DATE NOT NULL,
  `status` ENUM('active', 'paused') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_recurring_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recurring_category` FOREIGN KEY (`category_id`) 
    REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- SEED DATA: Default System Categories
-- ----------------------------------------------------------------------------
INSERT INTO `categories` (`id`, `user_id`, `name`, `type`) VALUES
-- Income categories
(1, NULL, 'Salary', 'income'),
(2, NULL, 'Freelance', 'income'),
(3, NULL, 'Investments', 'income'),
(4, NULL, 'Bonus & Awards', 'income'),
(5, NULL, 'Other Income', 'income'),

-- Expense categories
(6, NULL, 'Food & Dining', 'expense'),
(7, NULL, 'Travel & Transport', 'expense'),
(8, NULL, 'Shopping', 'expense'),
(9, NULL, 'Education', 'expense'),
(10, NULL, 'Bills & Utilities', 'expense'),
(11, NULL, 'Health & Medical', 'expense'),
(12, NULL, 'Entertainment', 'expense'),
(13, NULL, 'Housing & Rent', 'expense'),
(14, NULL, 'Other Expense', 'expense');

-- ----------------------------------------------------------------------------
-- SEED DATA: Demo User
-- Credentials: demo@example.com / password123
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `currency`, `theme`, `created_at`) VALUES
(1, 'Alex Morgan', 'demo@example.com', '$2y$12$5aAZlFyC/ZsJujx1P1ZRYuU2FtJSuEIjvhHEw.2D8MXsBcGjwGKVi', '$', 'light', NOW());

-- Custom categories created by demo user
INSERT INTO `categories` (`id`, `user_id`, `name`, `type`) VALUES
(15, 1, 'Gym & Fitness', 'expense'),
(16, 1, 'Side Hustle', 'income');

-- Sample Budgets
INSERT INTO `budgets` (`user_id`, `month`, `year`, `amount`) VALUES
(1, MONTH(CURRENT_DATE()), YEAR(CURRENT_DATE()), 2800.00),
(1, IF(MONTH(CURRENT_DATE()) = 1, 12, MONTH(CURRENT_DATE()) - 1), IF(MONTH(CURRENT_DATE()) = 1, YEAR(CURRENT_DATE()) - 1, YEAR(CURRENT_DATE())), 2500.00);

-- Sample Transactions
INSERT INTO `transactions` (`user_id`, `category_id`, `type`, `amount`, `description`, `transaction_date`) VALUES
(1, 1, 'income', 4500.00, 'Monthly Corporate Salary', DATE_SUB(CURRENT_DATE(), INTERVAL 12 DAY)),
(1, 2, 'income', 850.00, 'Web Design Freelance Project', DATE_SUB(CURRENT_DATE(), INTERVAL 4 DAY)),
(1, 13, 'expense', 1100.00, 'Apartment Monthly Rent', DATE_SUB(CURRENT_DATE(), INTERVAL 11 DAY)),
(1, 10, 'expense', 145.50, 'High-Speed Fiber Internet & Electricity', DATE_SUB(CURRENT_DATE(), INTERVAL 9 DAY)),
(1, 6, 'expense', 88.20, 'Weekly Grocery Supermarket Trip', DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)),
(1, 7, 'expense', 45.00, 'Metro Transit Card Recharge', DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY)),
(1, 8, 'expense', 129.99, 'Ergonomic Desk Chair Cushions', DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY)),
(1, 12, 'expense', 35.00, 'Cinema Tickets & Popcorn Combo', DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY)),
(1, 6, 'expense', 42.50, 'Casual Dinner with University Friends', DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)),
(1, 15, 'expense', 50.00, 'Monthly Gym Membership Pass', DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY)),
(1, 1, 'income', 4500.00, 'Previous Month Salary', DATE_SUB(CURRENT_DATE(), INTERVAL 35 DAY)),
(1, 3, 'income', 320.00, 'Quarterly Stock Dividends', DATE_SUB(CURRENT_DATE(), INTERVAL 38 DAY)),
(1, 13, 'expense', 1100.00, 'Apartment Rent Payment', DATE_SUB(CURRENT_DATE(), INTERVAL 34 DAY)),
(1, 6, 'expense', 260.00, 'Bi-Weekly Grocery Stockup', DATE_SUB(CURRENT_DATE(), INTERVAL 32 DAY)),
(1, 9, 'expense', 199.00, 'Online Full-Stack Certification Fee', DATE_SUB(CURRENT_DATE(), INTERVAL 28 DAY));

-- Sample Savings Goals for Demo User
INSERT INTO `savings_goals` (`user_id`, `title`, `target_amount`, `saved_amount`, `target_date`, `notes`, `status`) VALUES
(1, 'Emergency Rain-Day Fund', 5000.00, 3250.00, DATE_ADD(CURRENT_DATE(), INTERVAL 6 MONTH), '6 months of baseline living buffer', 'active'),
(1, 'New M4 MacBook Pro', 2200.00, 1850.00, DATE_ADD(CURRENT_DATE(), INTERVAL 2 MONTH), 'Developer workstation upgrade', 'active'),
(1, 'Summer Japan Trip', 3000.00, 750.00, DATE_ADD(CURRENT_DATE(), INTERVAL 8 MONTH), 'Tokyo and Kyoto vacation budget', 'active');

-- Sample Recurring Subscriptions & Bills
INSERT INTO `recurring_transactions` (`user_id`, `category_id`, `type`, `title`, `amount`, `frequency`, `next_due_date`, `status`) VALUES
(1, 12, 'expense', 'Netflix 4K Premium', 19.99, 'monthly', DATE_ADD(CURRENT_DATE(), INTERVAL 5 DAY), 'active'),
(1, 12, 'expense', 'Spotify Family Plan', 16.99, 'monthly', DATE_ADD(CURRENT_DATE(), INTERVAL 12 DAY), 'active'),
(1, 15, 'expense', 'Anytime Fitness Gym', 45.00, 'monthly', DATE_ADD(CURRENT_DATE(), INTERVAL 18 DAY), 'active'),
(1, 10, 'expense', 'High-Speed Home Fiber Internet', 75.00, 'monthly', DATE_ADD(CURRENT_DATE(), INTERVAL 22 DAY), 'active'),
(1, 1, 'income', 'Corporate Payroll Direct Deposit', 4500.00, 'monthly', DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY), 'active');
