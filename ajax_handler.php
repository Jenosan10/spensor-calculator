<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

// LKR currency formatter
function formatLKR($amount) {
    return number_format($amount, 2);
}

switch($action) {
    case 'add_transaction':
    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    
    if(!in_array($type, ['income', 'expense']) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    // Validate category_id if provided
    if($category_id) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$category_id, $user_id]);
        if(!$stmt->fetch()) {
            // Category doesn't exist or doesn't belong to user
            $category_id = null;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category_id, description, date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $amount, $category_id, $description, $date]);
        
        echo json_encode(['success' => true, 'message' => 'Transaction added successfully']);
    } catch(Exception $e) {
        // If still getting constraint error, set category_id to null
        if(strpos($e->getMessage(), 'foreign key constraint') !== false) {
            try {
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category_id, description, date) VALUES (?, ?, ?, NULL, ?, ?)");
                $stmt->execute([$user_id, $type, $amount, $description, $date]);
                echo json_encode(['success' => true, 'message' => 'Transaction added (category set to null due to constraint)']);
            } catch(Exception $e2) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e2->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    break;
        
    case 'get_transaction':
        $transaction_id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT t.*, c.name as category_name, c.color as category_color 
                              FROM transactions t 
                              LEFT JOIN categories c ON t.category_id = c.id 
                              WHERE t.id = ? AND t.user_id = ?");
        $stmt->execute([$transaction_id, $user_id]);
        $transaction = $stmt->fetch();
        
        if($transaction) {
            echo json_encode(['success' => true, 'transaction' => $transaction]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        }
        break;
        
    case 'update_transaction':
        $transaction_id = $_POST['id'] ?? 0;
        $type = $_POST['type'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $category_id = $_POST['category_id'] ?? null;
        $description = $_POST['description'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');
        
        try {
            $stmt = $pdo->prepare("UPDATE transactions SET type=?, amount=?, category_id=?, description=?, date=? WHERE id=? AND user_id=?");
            $stmt->execute([$type, $amount, $category_id, $description, $date, $transaction_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete_transaction':
        $transaction_id = $_GET['id'] ?? 0;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
            $stmt->execute([$transaction_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'recent_transactions':
        $limit = $_GET['limit'] ?? 10;
        
        $stmt = $pdo->prepare("SELECT t.*, c.name as category_name, c.color as category_color 
                              FROM transactions t 
                              LEFT JOIN categories c ON t.category_id = c.id 
                              WHERE t.user_id = ? 
                              ORDER BY t.date DESC, t.created_at DESC 
                              LIMIT ?");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'transactions' => $transactions]);
        break;
        
    case 'all_transactions':
        $date = $_GET['date'] ?? '';
        $type = $_GET['type'] ?? '';
        
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM transactions t 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = ?";
        
        $params = [$user_id];
        
        if($date) {
            $sql .= " AND t.date = ?";
            $params[] = $date;
        }
        
        if($type) {
            $sql .= " AND t.type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY t.date DESC, t.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'transactions' => $transactions]);
        break;
        
    case 'get_categories':
        $type = $_GET['type'] ?? '';
        
        $sql = "SELECT * FROM categories WHERE user_id = ?";
        $params = [$user_id];
        
        if($type) {
            $sql .= " AND (type = ? OR type = 'both')";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;
        
    case 'get_all_categories':
        $stmt = $pdo->prepare("SELECT c.*, COUNT(t.id) as transaction_count 
                              FROM categories c 
                              LEFT JOIN transactions t ON c.id = t.category_id 
                              WHERE c.user_id = ? 
                              GROUP BY c.id 
                              ORDER BY c.type, c.name");
        $stmt->execute([$user_id]);
        $categories = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;
        
    case 'get_category':
        $category_id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$category_id, $user_id]);
        $category = $stmt->fetch();
        
        if($category) {
            echo json_encode(['success' => true, 'category' => $category]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
        }
        break;
        
    case 'save_category':
        $category_id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'expense';
        $color = $_POST['color'] ?? '#4361ee';
        
        if(empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            exit();
        }
        
        try {
            if($category_id > 0) {
                // Update existing category
                $stmt = $pdo->prepare("UPDATE categories SET name=?, type=?, color=? WHERE id=? AND user_id=?");
                $stmt->execute([$name, $type, $color, $category_id, $user_id]);
                $message = 'Category updated successfully';
            } else {
                // Insert new category
                $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $name, $type, $color]);
                $message = 'Category added successfully';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
        } catch(Exception $e) {
            if(strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'Category name already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'delete_category':
        $category_id = $_GET['id'] ?? 0;
        
        // Check if category has transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE category_id = ? AND user_id = ?");
        $stmt->execute([$category_id, $user_id]);
        $result = $stmt->fetch();
        
        if($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete category with existing transactions']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$category_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'today_report':
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as count,
            COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END), 0) as income,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) as expense
            FROM transactions WHERE user_id = ? AND date = ?");
        $stmt->execute([$user_id, $today]);
        $result = $stmt->fetch();
        
        $result['balance'] = $result['income'] - $result['expense'];
        
        echo json_encode(['success' => true, 'today' => $today] + $result);
        break;
        
    case 'month_summary':
        $current_month = date('Y-m');
        
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END), 0) as income,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) as expense,
            COUNT(*) as days
            FROM transactions 
            WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
        $stmt->execute([$user_id, $current_month]);
        $result = $stmt->fetch();
        
        $result['balance'] = $result['income'] - $result['expense'];
        $result['avg_daily'] = $result['days'] > 0 ? $result['expense'] / $result['days'] : 0;
        
        echo json_encode(['success' => true] + $result);
        break;
        
    case 'category_summary':
        $stmt = $pdo->prepare("SELECT 
            c.name, c.color,
            COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END), 0) as total
            FROM categories c
            LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ? AND MONTH(t.date) = MONTH(CURRENT_DATE())
            WHERE c.user_id = ? AND c.type IN ('expense', 'both')
            GROUP BY c.id
            ORDER BY total DESC
            LIMIT 5");
        $stmt->execute([$user_id, $user_id]);
        $categories = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;
        
    case 'daily_report':
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT t.*, c.name as category_name 
                              FROM transactions t 
                              LEFT JOIN categories c ON t.category_id = c.id 
                              WHERE t.user_id = ? AND t.date = ? 
                              ORDER BY t.created_at DESC");
        $stmt->execute([$user_id, $date]);
        $transactions = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END), 0) as total_income,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) as total_expense
            FROM transactions WHERE user_id = ? AND date = ?");
        $stmt->execute([$user_id, $date]);
        $totals = $stmt->fetch();
        
        $totals['balance'] = $totals['total_income'] - $totals['total_expense'];
        
        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
            'total_income' => $totals['total_income'],
            'total_expense' => $totals['total_expense'],
            'balance' => $totals['balance']
        ]);
        break;
        
    case 'monthly_report':
        $month = $_GET['month'] ?? date('Y-m');
        
        // Get monthly totals
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END), 0) as total_income,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) as total_expense
            FROM transactions WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
        $stmt->execute([$user_id, $month]);
        $month_totals = $stmt->fetch();
        
        // Get daily summary
        $stmt = $pdo->prepare("SELECT 
            date,
            COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END), 0) as income,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) as expense
            FROM transactions 
            WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
            GROUP BY date ORDER BY date DESC");
        $stmt->execute([$user_id, $month]);
        $daily_summary = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'total_income' => $month_totals['total_income'],
            'total_expense' => $month_totals['total_expense'],
            'balance' => $month_totals['total_income'] - $month_totals['total_expense'],
            'daily_summary' => $daily_summary
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>