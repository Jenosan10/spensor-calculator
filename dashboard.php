<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$current_month = date('Y-m');

// Get user's name for greeting
$user_name = $_SESSION['username'];

// Get categories for dropdown
$categories_stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
$categories_stmt->execute([$user_id]);
$categories = $categories_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Expense Tracker LKR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --lkr-color: #005c29;
            --card-bg: #ffffff;
            --sidebar-bg: #1e293b;
            --border: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        
        .app-container {
            display: grid;
            grid-template-columns: 1fr;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            color: var(--primary);
            font-size: 1.8rem;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 700;
        }
        
        .lkr-badge {
            background: var(--lkr-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #d91a6d;
            transform: translateY(-2px);
        }
        
        /* Main Content */
        .main-content {
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            background: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .action-btn i {
            font-size: 1.5rem;
        }
        
        .action-btn.add-transaction {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
        }
        
        .action-btn.manage-categories {
            background: linear-gradient(135deg, #2196F3, #0D47A1);
            color: white;
        }
        
        .action-btn.view-reports {
            background: linear-gradient(135deg, #9C27B0, #4A148C);
            color: white;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: var(--primary);
            color: white;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Sections */
        .form-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--primary);
        }
        
        .transaction-form, .category-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #FF9800, #EF6C00);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #F44336, #C62828);
            color: white;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-btn {
            background: #2196F3;
            color: white;
        }
        
        .delete-btn {
            background: #F44336;
            color: white;
        }
        
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        /* Color Picker */
        .color-picker {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .color-option {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
        }
        
        .color-option.selected {
            border-color: #333;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .user-menu {
                width: 100%;
                justify-content: space-between;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .transaction-form, .category-form {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <i class="fas fa-wallet"></i>
                <h1>Daily Expense Tracker <span class="lkr-badge">LKR</span></h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name">Hello, <?php echo htmlspecialchars($user_name); ?>!</div>
                    <small>Last login: <?php echo date('h:i A'); ?></small>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="action-btn add-transaction" onclick="showTab('transactions')">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Transaction</span>
                </button>
                <button class="action-btn manage-categories" onclick="showTab('categories')">
                    <i class="fas fa-tags"></i>
                    <span>Manage Categories</span>
                </button>
                <button class="action-btn view-reports" onclick="showTab('reports')">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Reports</span>
                </button>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="showTab('transactions')">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </button>
                <button class="tab" onclick="showTab('categories')">
                    <i class="fas fa-tags"></i> Categories
                </button>
                <button class="tab" onclick="showTab('reports')">
                    <i class="fas fa-chart-pie"></i> Reports
                </button>
                <button class="tab" onclick="showTab('recent')">
                    <i class="fas fa-history"></i> Recent
                </button>
            </div>
            
            <!-- Transactions Tab -->
            <div id="transactionsTab" class="tab-content active">
                <!-- Add Transaction Form -->
                <div class="form-section animate-fade-in">
                    <h2 class="section-title">
                        <i class="fas fa-plus-circle"></i> Add New Transaction
                    </h2>
                    
                    <form id="transactionForm" class="transaction-form">
                        <div class="form-group">
                            <label><i class="fas fa-exchange-alt"></i> Transaction Type</label>
                            <select name="type" class="form-control" required onchange="filterCategoriesByType(this.value)">
                                <option value="">Select Type</option>
                                <option value="income">➕ Income</option>
                                <option value="expense">➖ Expense</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-coins"></i> Amount (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" 
                                   placeholder="රු 0.00" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Category</label>
                            <select name="category_id" id="categorySelect" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            data-type="<?php echo $cat['type']; ?>"
                                            style="color: <?php echo $cat['color']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Or <a href="#" onclick="showAddCategoryModal()">add new category</a></small>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date</label>
                            <input type="date" name="date" value="<?php echo $today; ?>" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-sticky-note"></i> Description (Optional)</label>
                            <input type="text" name="description" class="form-control" 
                                   placeholder="Enter description...">
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Add Transaction
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Recent Transactions -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i> Recent Transactions
                        <button class="btn btn-primary" onclick="loadRecentTransactions()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </h2>
                    
                    <div id="recentTransactions">
                        Loading recent transactions...
                    </div>
                </div>
            </div>
            
            <!-- Categories Tab -->
            <div id="categoriesTab" class="tab-content">
                <div class="form-section animate-fade-in">
                    <h2 class="section-title">
                        <i class="fas fa-tags"></i> Manage Categories
                        <button class="btn btn-success" onclick="showAddCategoryModal()">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </h2>
                    
                    <div id="categoriesList">
                        Loading categories...
                    </div>
                </div>
            </div>
            
            <!-- Reports Tab -->
            <div id="reportsTab" class="tab-content">
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-chart-pie"></i> Financial Reports
                    </h2>
                    
                    <div class="stats-grid">
                        <!-- Today's Report -->
                        <div class="stat-card">
                            <h3 class="stat-title">Today's Summary</h3>
                            <div id="todayReport">Loading...</div>
                        </div>
                        
                        <!-- This Month -->
                        <div class="stat-card">
                            <h3 class="stat-title">This Month</h3>
                            <div id="monthReport">Loading...</div>
                        </div>
                        
                        <!-- Category Breakdown -->
                        <div class="stat-card">
                            <h3 class="stat-title">Category Breakdown</h3>
                            <div id="categoryReport">Loading...</div>
                        </div>
                    </div>
                    
                    <!-- Report Controls -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 2rem;">
                        <div class="form-group">
                            <label>Daily Report</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="date" id="dailyReportDate" value="<?php echo $today; ?>" class="form-control">
                                <button onclick="loadDailyReport()" class="btn btn-primary">Load</button>
                            </div>
                            <div id="dailyReportDetails" style="margin-top: 1rem;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Monthly Report</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="month" id="monthlyReportDate" value="<?php echo $current_month; ?>" class="form-control">
                                <button onclick="loadMonthlyReport()" class="btn btn-primary">Generate</button>
                            </div>
                            <div id="monthlyReportDetails" style="margin-top: 1rem;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Tab -->
            <div id="recentTab" class="tab-content">
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i> All Transactions
                    </h2>
                    
                    <div style="margin-bottom: 1rem;">
                        <input type="date" id="filterDate" class="form-control" style="width: auto; display: inline-block;">
                        <select id="filterType" class="form-control" style="width: auto; display: inline-block; margin-left: 10px;">
                            <option value="">All Types</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                        <button onclick="loadAllTransactions()" class="btn btn-primary">Filter</button>
                    </div>
                    
                    <div id="allTransactions">
                        Loading all transactions...
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Edit Transaction Modal -->
        <div id="editTransactionModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-edit"></i> Edit Transaction</h3>
                    <button class="close-modal" onclick="closeEditTransactionModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editTransactionForm">
                        <input type="hidden" id="editTransactionId" name="id">
                        <div class="form-group">
                            <label>Type</label>
                            <select id="editType" name="type" class="form-control" required onchange="filterEditCategories(this.value)">
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount (LKR)</label>
                            <input type="number" step="0.01" id="editAmount" name="amount" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select id="editCategoryId" name="category_id" class="form-control" required></select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" id="editDate" name="date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" id="editDescription" name="description" class="form-control">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" onclick="updateTransaction()">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <button class="btn btn-danger" onclick="deleteTransaction()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="btn btn-secondary" onclick="closeEditTransactionModal()">Cancel</button>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Category Modal -->
        <div id="categoryModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-tag"></i> <span id="categoryModalTitle">Add Category</span></h3>
                    <button class="close-modal" onclick="closeCategoryModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        <input type="hidden" id="categoryId" name="id">
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" id="categoryName" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select id="categoryType" name="type" class="form-control" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="both">Both Income & Expense</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Color</label>
                            <div class="color-picker">
                                <div class="color-option selected" style="background: #4361ee;" onclick="selectColor('#4361ee')"></div>
                                <div class="color-option" style="background: #4CAF50;" onclick="selectColor('#4CAF50')"></div>
                                <div class="color-option" style="background: #2196F3;" onclick="selectColor('#2196F3')"></div>
                                <div class="color-option" style="background: #9C27B0;" onclick="selectColor('#9C27B0')"></div>
                                <div class="color-option" style="background: #FF9800;" onclick="selectColor('#FF9800')"></div>
                                <div class="color-option" style="background: #E91E63;" onclick="selectColor('#E91E63')"></div>
                                <div class="color-option" style="background: #00BCD4;" onclick="selectColor('#00BCD4')"></div>
                                <div class="color-option" style="background: #8BC34A;" onclick="selectColor('#8BC34A')"></div>
                            </div>
                            <input type="hidden" id="categoryColor" name="color" value="#4361ee">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" onclick="saveCategory()">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Global variables
    let currentTransactionId = null;
    let selectedColor = '#4361ee';
    
    // Currency formatter for LKR
    function formatLKR(amount) {
        return 'රු ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    // Tab Navigation
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + 'Tab').classList.add('active');
        
        // Update tab buttons
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Find and activate the clicked tab
        const tabs = document.querySelectorAll('.tab');
        for(let tab of tabs) {
            if(tab.textContent.includes(tabName.charAt(0).toUpperCase() + tabName.slice(1))) {
                tab.classList.add('active');
                break;
            }
        }
        
        // Load data for the tab
        if(tabName === 'categories') {
            loadCategories();
        } else if(tabName === 'recent') {
            loadAllTransactions();
        } else if(tabName === 'reports') {
            loadReports();
        } else if(tabName === 'transactions') {
            loadRecentTransactions();
        }
    }
    
    // Filter categories by type
    function filterCategoriesByType(type) {
        const categorySelect = document.getElementById('categorySelect');
        const options = categorySelect.querySelectorAll('option');
        
        options.forEach(option => {
            if(option.value === '') return;
            
            const optionType = option.dataset.type;
            if(type === '' || optionType === type || optionType === 'both') {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Reset to first visible option
        categorySelect.value = '';
        for(let option of options) {
            if(option.style.display !== 'none' && option.value !== '') {
                categorySelect.value = option.value;
                break;
            }
        }
    }
    
    // AJAX for adding transaction
    document.getElementById('transactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add_transaction');
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        submitBtn.disabled = true;
        
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Transaction added successfully!',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Reset form
                this.reset();
                document.querySelector('input[name="date"]').value = '<?php echo $today; ?>';
                
                // Reload data
                loadRecentTransactions();
                if(document.getElementById('allTransactions').innerHTML !== 'Loading all transactions...') {
                    loadAllTransactions();
                }
                loadReports();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Network error occurred'
            });
            console.error('Error:', error);
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Load recent transactions
    function loadRecentTransactions() {
        const container = document.getElementById('recentTransactions');
        container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        fetch('ajax_handler.php?action=recent_transactions')
            .then(response => response.json())
            .then(data => {
                if(data.success && data.transactions.length > 0) {
                    let html = '<table class="data-table">';
                    html += '<thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Amount</th><th>Description</th><th>Actions</th></tr></thead><tbody>';
                    
                    data.transactions.forEach(transaction => {
                        const typeClass = transaction.type === 'income' ? 'type-income' : 'type-expense';
                        const typeIcon = transaction.type === 'income' ? '📈' : '📉';
                        
                        html += `
                        <tr>
                            <td>${transaction.date}</td>
                            <td><span class="transaction-type ${typeClass}">${typeIcon} ${transaction.type}</span></td>
                            <td style="color: ${transaction.category_color || '#4361ee'}">${transaction.category_name || 'Uncategorized'}</td>
                            <td style="color: ${transaction.type === 'income' ? '#4CAF50' : '#F44336'}; font-weight: bold;">
                                ${transaction.type === 'income' ? '+' : '-'}${formatLKR(transaction.amount)}
                            </td>
                            <td>${transaction.description || '-'}</td>
                            <td class="action-buttons">
                                <button class="action-btn-small edit-btn" onclick="editTransaction(${transaction.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn-small delete-btn" onclick="confirmDeleteTransaction(${transaction.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No recent transactions found.</p>';
                }
            })
            .catch(error => {
                container.innerHTML = '<p style="color: #F44336; text-align: center;">Error loading transactions</p>';
                console.error('Error:', error);
            });
    }
    
    // Load all transactions
    function loadAllTransactions() {
        const date = document.getElementById('filterDate').value;
        const type = document.getElementById('filterType').value;
        const container = document.getElementById('allTransactions');
        
        container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        let url = `ajax_handler.php?action=all_transactions`;
        if(date) url += `&date=${date}`;
        if(type) url += `&type=${type}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if(data.success && data.transactions.length > 0) {
                    let html = '<table class="data-table">';
                    html += '<thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Amount</th><th>Description</th><th>Actions</th></tr></thead><tbody>';
                    
                    data.transactions.forEach(transaction => {
                        const typeClass = transaction.type === 'income' ? 'type-income' : 'type-expense';
                        const typeIcon = transaction.type === 'income' ? '📈' : '📉';
                        
                        html += `
                        <tr>
                            <td>${transaction.date}</td>
                            <td><span class="transaction-type ${typeClass}">${typeIcon} ${transaction.type}</span></td>
                            <td style="color: ${transaction.category_color || '#4361ee'}">${transaction.category_name || 'Uncategorized'}</td>
                            <td style="color: ${transaction.type === 'income' ? '#4CAF50' : '#F44336'}; font-weight: bold;">
                                ${transaction.type === 'income' ? '+' : '-'}${formatLKR(transaction.amount)}
                            </td>
                            <td>${transaction.description || '-'}</td>
                            <td class="action-buttons">
                                <button class="action-btn-small edit-btn" onclick="editTransaction(${transaction.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn-small delete-btn" onclick="confirmDeleteTransaction(${transaction.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No transactions found.</p>';
                }
            })
            .catch(error => {
                container.innerHTML = '<p style="color: #F44336; text-align: center;">Error loading transactions</p>';
                console.error('Error:', error);
            });
    }
    
    // Edit transaction
    function editTransaction(id) {
        currentTransactionId = id;
        
        fetch(`ajax_handler.php?action=get_transaction&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const transaction = data.transaction;
                    
                    document.getElementById('editTransactionId').value = transaction.id;
                    document.getElementById('editType').value = transaction.type;
                    document.getElementById('editAmount').value = transaction.amount;
                    document.getElementById('editDate').value = transaction.date;
                    document.getElementById('editDescription').value = transaction.description || '';
                    
                    // Load categories for the type
                    filterEditCategories(transaction.type);
                    
                    // Set category after categories are loaded
                    setTimeout(() => {
                        document.getElementById('editCategoryId').value = transaction.category_id || '';
                    }, 100);
                    
                    // Show modal
                    document.getElementById('editTransactionModal').style.display = 'flex';
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to load transaction', 'error');
                console.error('Error:', error);
            });
    }
    
    function filterEditCategories(type) {
        const select = document.getElementById('editCategoryId');
        select.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`ajax_handler.php?action=get_categories&type=${type}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    select.innerHTML = '<option value="">Select Category</option>';
                    data.categories.forEach(category => {
                        select.innerHTML += `<option value="${category.id}" style="color: ${category.color}">${category.name}</option>`;
                    });
                }
            });
    }
    
    function closeEditTransactionModal() {
        document.getElementById('editTransactionModal').style.display = 'none';
        currentTransactionId = null;
    }
    
    function updateTransaction() {
        const formData = new FormData(document.getElementById('editTransactionForm'));
        formData.append('action', 'update_transaction');
        formData.append('id', currentTransactionId);
        
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Transaction updated successfully',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                closeEditTransactionModal();
                loadRecentTransactions();
                loadAllTransactions();
                loadReports();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Failed to update transaction', 'error');
            console.error('Error:', error);
        });
    }
    
    function confirmDeleteTransaction(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteTransactionById(id);
            }
        });
    }
    
    function deleteTransaction() {
        if(!currentTransactionId) return;
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteTransactionById(currentTransactionId);
            }
        });
    }
    
    function deleteTransactionById(id) {
        fetch(`ajax_handler.php?action=delete_transaction&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Transaction deleted successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    closeEditTransactionModal();
                    loadRecentTransactions();
                    loadAllTransactions();
                    loadReports();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to delete transaction', 'error');
                console.error('Error:', error);
            });
    }
    
    // Category Management
    function loadCategories() {
        const container = document.getElementById('categoriesList');
        container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        fetch('ajax_handler.php?action=get_all_categories')
            .then(response => response.json())
            .then(data => {
                if(data.success && data.categories.length > 0) {
                    let html = '<table class="data-table">';
                    html += '<thead><tr><th>Name</th><th>Type</th><th>Color</th><th>Transaction Count</th><th>Actions</th></tr></thead><tbody>';
                    
                    data.categories.forEach(category => {
                        const typeText = category.type === 'income' ? 'Income' : 
                                        category.type === 'expense' ? 'Expense' : 'Both';
                        const typeColor = category.type === 'income' ? '#4CAF50' : 
                                         category.type === 'expense' ? '#F44336' : '#9C27B0';
                        
                        html += `
                        <tr>
                            <td style="color: ${category.color}; font-weight: bold;">${category.name}</td>
                            <td><span style="color: ${typeColor}">${typeText}</span></td>
                            <td><div style="width: 20px; height: 20px; background: ${category.color}; border-radius: 3px;"></div></td>
                            <td>${category.transaction_count || 0}</td>
                            <td class="action-buttons">
                                <button class="action-btn-small edit-btn" onclick="editCategory(${category.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn-small delete-btn" onclick="confirmDeleteCategory(${category.id})" ${category.transaction_count > 0 ? 'disabled' : ''}>
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No categories found. Add your first category!</p>';
                }
            })
            .catch(error => {
                container.innerHTML = '<p style="color: #F44336; text-align: center;">Error loading categories</p>';
                console.error('Error:', error);
            });
    }
    
    function showAddCategoryModal() {
        document.getElementById('categoryModalTitle').textContent = 'Add Category';
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryName').value = '';
        document.getElementById('categoryType').value = 'expense';
        document.getElementById('categoryColor').value = '#4361ee';
        
        // Reset color picker
        document.querySelectorAll('.color-option').forEach(option => {
            option.classList.remove('selected');
        });
        document.querySelector('.color-option[style*="#4361ee"]').classList.add('selected');
        selectedColor = '#4361ee';
        
        document.getElementById('categoryModal').style.display = 'flex';
    }
    
    function editCategory(id) {
        fetch(`ajax_handler.php?action=get_category&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const category = data.category;
                    
                    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
                    document.getElementById('categoryId').value = category.id;
                    document.getElementById('categoryName').value = category.name;
                    document.getElementById('categoryType').value = category.type;
                    document.getElementById('categoryColor').value = category.color;
                    
                    // Update color picker
                    document.querySelectorAll('.color-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    const colorOption = document.querySelector(`.color-option[style*="${category.color}"]`);
                    if(colorOption) {
                        colorOption.classList.add('selected');
                    }
                    selectedColor = category.color;
                    
                    document.getElementById('categoryModal').style.display = 'flex';
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to load category', 'error');
                console.error('Error:', error);
            });
    }
    
    function selectColor(color) {
        selectedColor = color;
        document.getElementById('categoryColor').value = color;
        
        document.querySelectorAll('.color-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.target.classList.add('selected');
    }
    
    function closeCategoryModal() {
        document.getElementById('categoryModal').style.display = 'none';
    }
    
    function saveCategory() {
        const formData = new FormData();
        formData.append('action', 'save_category');
        formData.append('id', document.getElementById('categoryId').value);
        formData.append('name', document.getElementById('categoryName').value);
        formData.append('type', document.getElementById('categoryType').value);
        formData.append('color', selectedColor);
        
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Category saved successfully',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                closeCategoryModal();
                loadCategories();
                
                // Reload category dropdowns
                filterCategoriesByType(document.querySelector('select[name="type"]').value);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Failed to save category', 'error');
            console.error('Error:', error);
        });
    }
    
    function confirmDeleteCategory(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the category. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteCategory(id);
            }
        });
    }
    
    function deleteCategory(id) {
        fetch(`ajax_handler.php?action=delete_category&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Category deleted successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    loadCategories();
                    filterCategoriesByType(document.querySelector('select[name="type"]').value);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to delete category', 'error');
                console.error('Error:', error);
            });
    }
    
    // Reports
    function loadReports() {
        loadTodayReport();
        loadMonthReport();
        loadCategoryReport();
    }
    
    function loadTodayReport() {
        const container = document.getElementById('todayReport');
        
        fetch('ajax_handler.php?action=today_report')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    container.innerHTML = `
                        <p><strong>Income:</strong> <span style="color: #4CAF50">${formatLKR(data.income)}</span></p>
                        <p><strong>Expenses:</strong> <span style="color: #F44336">${formatLKR(data.expense)}</span></p>
                        <p><strong>Balance:</strong> <span style="color: ${data.balance >= 0 ? '#4CAF50' : '#F44336'}">${formatLKR(data.balance)}</span></p>
                        <p><strong>Transactions:</strong> ${data.count}</p>
                    `;
                }
            });
    }
    
    function loadMonthReport() {
        const container = document.getElementById('monthReport');
        
        fetch('ajax_handler.php?action=month_summary')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    container.innerHTML = `
                        <p><strong>Income:</strong> <span style="color: #4CAF50">${formatLKR(data.income)}</span></p>
                        <p><strong>Expenses:</strong> <span style="color: #F44336">${formatLKR(data.expense)}</span></p>
                        <p><strong>Balance:</strong> <span style="color: ${data.balance >= 0 ? '#4CAF50' : '#F44336'}">${formatLKR(data.balance)}</span></p>
                        <p><strong>Avg Daily:</strong> ${formatLKR(data.avg_daily)}</p>
                    `;
                }
            });
    }
    
    function loadCategoryReport() {
        const container = document.getElementById('categoryReport');
        
        fetch('ajax_handler.php?action=category_summary')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    let html = '';
                    data.categories.forEach(cat => {
                        html += `<p><span style="color: ${cat.color}">${cat.name}:</span> ${formatLKR(cat.total)}</p>`;
                    });
                    container.innerHTML = html;
                }
            });
    }
    
    function loadDailyReport() {
        const date = document.getElementById('dailyReportDate').value;
        const container = document.getElementById('dailyReportDetails');
        
        container.innerHTML = '<div style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        fetch(`ajax_handler.php?action=daily_report&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    let html = '<h4>Daily Transactions</h4>';
                    
                    if(data.transactions.length > 0) {
                        data.transactions.forEach(transaction => {
                            html += `
                            <div style="padding: 10px; border-bottom: 1px solid #eee;">
                                <strong>${transaction.category_name}</strong> 
                                <span style="color: ${transaction.type === 'income' ? '#4CAF50' : '#F44336'}">
                                    ${transaction.type === 'income' ? '+' : '-'}${formatLKR(transaction.amount)}
                                </span>
                                <br>
                                <small>${transaction.description || ''}</small>
                            </div>`;
                        });
                        
                        html += `
                        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Total Income:</strong> <span style="color: #4CAF50">${formatLKR(data.total_income)}</span><br>
                            <strong>Total Expense:</strong> <span style="color: #F44336">${formatLKR(data.total_expense)}</span><br>
                            <strong>Daily Balance:</strong> <span style="color: ${data.balance >= 0 ? '#4CAF50' : '#F44336'}">${formatLKR(data.balance)}</span>
                        </div>`;
                    } else {
                        html = '<p>No transactions for this date.</p>';
                    }
                    
                    container.innerHTML = html;
                }
            });
    }
    
    function loadMonthlyReport() {
        const month = document.getElementById('monthlyReportDate').value;
        const container = document.getElementById('monthlyReportDetails');
        
        container.innerHTML = '<div style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        fetch(`ajax_handler.php?action=monthly_report&month=${month}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    let html = `<h4>Monthly Summary - ${month}</h4>`;
                    
                    html += `
                    <div style="padding: 15px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 10px; margin-bottom: 15px;">
                        <p><strong>Total Income:</strong> ${formatLKR(data.total_income)}</p>
                        <p><strong>Total Expense:</strong> ${formatLKR(data.total_expense)}</p>
                        <p><strong>Monthly Balance:</strong> ${formatLKR(data.balance)}</p>
                    </div>`;
                    
                    if(data.daily_summary && data.daily_summary.length > 0) {
                        html += '<h5>Daily Breakdown</h5>';
                        data.daily_summary.forEach(day => {
                            const dayBalance = day.income - day.expense;
                            html += `
                            <div style="padding: 8px; border-bottom: 1px solid #eee; background: ${dayBalance >= 0 ? '#f0fff4' : '#fff5f5'}">
                                <strong>${day.date}</strong>: 
                                <span style="color: #4CAF50">+${formatLKR(day.income)}</span> / 
                                <span style="color: #F44336">-${formatLKR(day.expense)}</span>
                                <span style="float: right; color: ${dayBalance >= 0 ? '#4CAF50' : '#F44336'}">
                                    ${dayBalance >= 0 ? '+' : ''}${formatLKR(dayBalance)}
                                </span>
                            </div>`;
                        });
                    }
                    
                    container.innerHTML = html;
                }
            });
    }
    
    // Initialize on load
    window.onload = function() {
        loadRecentTransactions();
        loadReports();
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editTransactionModal');
            const categoryModal = document.getElementById('categoryModal');
            
            if(event.target == editModal) {
                closeEditTransactionModal();
            }
            if(event.target == categoryModal) {
                closeCategoryModal();
            }
        };
    };
    </script>
</body>
</html>