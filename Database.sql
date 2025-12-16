-- Drop database if exists
DROP DATABASE IF EXISTS expense_tracker;
CREATE DATABASE expense_tracker;
USE expense_tracker;

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table (no foreign key)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense', 'both') DEFAULT 'expense',
    color VARCHAR(7) DEFAULT '#4361ee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_category (user_id, name)
);

-- Create transactions table (no foreign key)
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category_id INT NULL,
    description VARCHAR(255),
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default user (jeno/jeno)
INSERT INTO users (username, password) VALUES 
('jeno', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert default categories for user 1
INSERT INTO categories (user_id, name, type, color) VALUES
(1, 'Salary', 'income', '#4CAF50'),
(1, 'Freelance', 'income', '#2196F3'),
(1, 'Food', 'expense', '#FF9800'),
(1, 'Transport', 'expense', '#9C27B0'),
(1, 'Shopping', 'expense', '#E91E63'),
(1, 'Entertainment', 'expense', '#00BCD4'),
(1, 'Bills', 'expense', '#FF5722'),
(1, 'Healthcare', 'expense', '#8BC34A'),
(1, 'Education', 'expense', '#3F51B5'),
(1, 'Other', 'both', '#9E9E9E');

-- Insert sample transactions
INSERT INTO transactions (user_id, type, amount, category_id, description, date) VALUES
(1, 'income', 50000, 1, 'Monthly salary', CURDATE()),
(1, 'expense', 1500, 3, 'Lunch at restaurant', CURDATE()),
(1, 'expense', 500, 4, 'Bus fare', CURDATE()),
(1, 'income', 10000, 2, 'Freelance project', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, 'expense', 7500, 5, 'Clothes shopping', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(1, 'expense', 3500, 6, 'Movie tickets', DATE_SUB(CURDATE(), INTERVAL 3 DAY));