<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database (Sesuaikan jika di-hosting)
$host = 'localhost';
$db   = 'hanzstore';
$user = 'root'; // Username database Anda
$pass = '';     // Password database Anda

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Gagal koneksi ke database']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'register') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // Di sistem real gunakan password_hash, kita gunakan plain agar sesuai script asli.

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username atau Email sudah terdaftar!']);
        exit;
    }

    $defaultPic = "https://i.ibb.co.com/HfFpSK5Z/Proyek-Baru-17-76139-AC.png";
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, balance, pic) VALUES (?, ?, ?, 0, ?)");
    $stmt->execute([$username, $email, $password, $defaultPic]);

    echo json_encode(['success' => true]);
} 
elseif ($action === 'login') {
    $userOrEmail = trim($_POST['userOrEmail']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND password = ?");
    $stmt->execute([$userOrEmail, $userOrEmail, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false]);
    }
} 
elseif ($action === 'get_balance') {
    $username = trim($_GET['username']);
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo json_encode(['success' => true, 'balance' => (int)$user['balance']]);
    } else {
        echo json_encode(['success' => false]);
    }
} 
elseif ($action === 'update_pic') {
    $username = trim($_POST['username']);
    $pic = trim($_POST['pic']);
    $stmt = $pdo->prepare("UPDATE users SET pic = ? WHERE username = ?");
    $stmt->execute([$pic, $username]);
    echo json_encode(['success' => true]);
} 
elseif ($action === 'add_history') {
    $username = trim($_POST['username']);
    $item_name = trim($_POST['item_name']);
    $price = (int)$_POST['price'];
    $status = trim($_POST['status']);
    $date = trim($_POST['date']);

    $stmt = $pdo->prepare("INSERT INTO transactions (username, item_name, price, status, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $item_name, $price, $status, $date]);
    echo json_encode(['success' => true]);
} 
elseif ($action === 'get_history') {
    $username = trim($_GET['username']);
    $stmt = $pdo->prepare("SELECT item_name as itemName, price, status, date FROM transactions WHERE username = ? ORDER BY id DESC");
    $stmt->execute([$username]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'history' => $history]);
} 
elseif ($action === 'deduct_balance') {
    $username = trim($_POST['username']);
    $amount = (int)$_POST['amount'];

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE username = ? FOR UPDATE");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['balance'] >= $amount) {
        $newBalance = $user['balance'] - $amount;
        $update = $pdo->prepare("UPDATE users SET balance = ? WHERE username = ?");
        $update->execute([$newBalance, $username]);
        $pdo->commit();
        echo json_encode(['success' => true, 'new_balance' => $newBalance]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Saldo Anda tidak mencukupi!']);
    }
}
?>