<?php
// setup.php - Run this file ONCE to set up everything

$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Expense Tracker Setup</h2>";
    
    // Drop and create database
    echo "Creating database...<br>";
    $pdo->exec("DROP DATABASE IF EXISTS expense_tracker");
    $pdo->exec("CREATE DATABASE expense_tracker");
    $pdo->exec("USE expense_tracker");
    
    echo "Creating tables...<br>";
    
    // Users table
    $pdo->exec("
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Categories table
    $pdo->exec("
        CREATE TABLE categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            type ENUM('income', 'expense', 'both') DEFAULT 'expense',
            color VARCHAR(7) DEFAULT '#4361ee',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_category (user_id, name)
        )
    ");
    
    // Transactions table
    $pdo->exec("
        CREATE TABLE transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            type ENUM('income', 'expense') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            category_id INT NULL,
            description VARCHAR(255),
            date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating default user...<br>";
    // Create REAL hashed password for 'jeno'
    $hashed_password = password_hash('jeno', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute(['jeno', $hashed_password]);
    $user_id = $pdo->lastInsertId();
    
    echo "Creating default categories...<br>";
    // Default categories
    $default_categories = [
        ['Salary', 'income', '#4CAF50'],
        ['Freelance', 'income', '#2196F3'],
        ['Food', 'expense', '#FF9800'],
        ['Transport', 'expense', '#9C27B0'],
        ['Shopping', 'expense', '#E91E63'],
        ['Entertainment', 'expense', '#00BCD4'],
        ['Bills', 'expense', '#FF5722'],
        ['Healthcare', 'expense', '#8BC34A'],
        ['Education', 'expense', '#3F51B5'],
        ['Other', 'both', '#9E9E9E']
    ];
    
    foreach($default_categories as $category) {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $category[0], $category[1], $category[2]]);
    }
    
    // Get category IDs for sample transactions
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [name => id]
    
    echo "Adding sample transactions...<br>";
    // Add sample transactions with correct category IDs
    $sample_transactions = [
        ['income', 50000, $categories['Salary'], 'Monthly salary', date('Y-m-d')],
        ['expense', 1500, $categories['Food'], 'Lunch at restaurant', date('Y-m-d')],
        ['expense', 500, $categories['Transport'], 'Bus fare', date('Y-m-d')],
        ['income', 10000, $categories['Freelance'], 'Freelance project', date('Y-m-d', strtotime('-1 day'))],
        ['expense', 7500, $categories['Shopping'], 'Clothes shopping', date('Y-m-d', strtotime('-2 days'))],
        ['expense', 3500, $categories['Entertainment'], 'Movie tickets', date('Y-m-d', strtotime('-3 days'))]
    ];
    
    foreach($sample_transactions as $transaction) {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category_id, description, date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $transaction[0], $transaction[1], $transaction[2], $transaction[3], $transaction[4]]);
    }
    
    echo "<h3 style='color: green;'>✅ Setup Complete!</h3>";
    echo "<p><strong>Database:</strong> expense_tracker</p>";
    echo "<p><strong>Username:</strong> <span style='color: blue;'>jeno</span></p>";
    echo "<p><strong>Password:</strong> <span style='color: blue;'>jeno</span></p>";
    echo "<p><strong>Default categories created:</strong> 10 categories with different colors</p>";
    echo "<p><strong>Sample transactions added:</strong> 6 sample transactions</p>";
    echo "<p><strong>Important:</strong> Foreign key constraints are disabled for simplicity</p>";
    echo "<p><a href='index.php' style='color: white; background: #4361ee; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 20px;'>Click here to go to Login Page</a></p>";
    
} catch(PDOException $e) {
    die("<div style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</div>");
}
?>