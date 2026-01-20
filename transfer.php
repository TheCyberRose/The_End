
<?php

$host = 'localhost';
$db   = 'banking_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    } 
    catch (PDOException $e) 
    {
        die("
            <div style='text-align:center;color:red;font-family:Arial;margin-top:50px'>
                ❌ خطأ في الاتصال بقاعدة البيانات<br>
                تأكدي أن قاعدة البيانات <b>banking_db</b> موجودة
            </div>
    ");
}


$pdo->exec("
    CREATE TABLE IF NOT EXISTS accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        balance DECIMAL(15,2) NOT NULL
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_account INT NOT NULL,
        to_account INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        fee DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");


$count = $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();

if ($count == 0) {
    $pdo->exec("
        INSERT INTO accounts (balance) VALUES
        (15000000),
        (10000000),
        (7000000),
        (4000000),
        (2000000);
    ");
}



if (isset($_GET['action']) && $_GET['action'] === 'check_balance') {
    $id = intval($_GET['check_balance']);

    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        echo "
        <div style='text-align:center;font-family:Arial;margin-top:50px'>
            <h2>💰 رصيد الحساب رقم {$id}</h2>
            <p>{$row['balance']} ر.ي</p>
            <a href='index.html'>رجوع</a>
        </div>
        ";
    } else {
        echo "
        <div style='text-align:center;color:red;margin-top:50px'>
            ❌ الحساب غير موجود
        </div>
        ";
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.html");
    exit;
}


$from   = intval($_POST['from']);
$to     = intval($_POST['to']);
$amount = floatval($_POST['amount']);

if ($from === $to) {
    die("<div style='text-align:center;color:red'>❌ لا يمكن التحويل لنفس الحساب</div>");
}

if ($amount <= 0 || $amount > 50000000) {
    die("<div style='text-align:center;color:red'>❌ مبلغ غير صحيح</div>");
}

try {
    $pdo->beginTransaction();


    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
    $stmt->execute([$from]);
    $sender = $stmt->fetch();


    $stmt->execute([$to]);
    $receiver = $stmt->fetch();

    if (!$sender || !$receiver) {
        throw new Exception("❌ أحد الحسابات غير موجود");
    }

    $sender_balance = floatval($sender['balance']);

    
    $fee = ($amount <= 100000) ? 500 : 1000;
    $total = $amount + $fee;

    if ($sender_balance < $total) {
        throw new Exception("❌ رصيد غير كافٍ");
    }

    
    $pdo->prepare("
        UPDATE accounts SET balance = balance - ? WHERE id = ?
    ")->execute([$total, $from]);


    $pdo->prepare("
        UPDATE accounts SET balance = balance + ? WHERE id = ?
    ")->execute([$amount, $to]);


    $pdo->prepare("
        INSERT INTO transactions (from_account, to_account, amount, fee)
        VALUES (?, ?, ?, ?)
    ")->execute([$from, $to, $amount, $fee]);

    $pdo->commit();

    $remaining = $sender_balance - $total;

    echo "
    <script>
        alert('✅ تم التحويل بنجاح\\nالمبلغ: {$amount} ر.ي\\nالرسوم: {$fee} ر.ي');
    </script>

    <div style='text-align:center;color:green;font-family:Arial;margin-top:50px'>
        ✅ تمت عملية التحويل بنجاح
        <br>📤 المبلغ المحوَّل: {$amount} ر.ي
        <br>💸 رسوم التحويل: {$fee} ر.ي
        <br>➖ إجمالي الخصم: {$total} ر.ي
        <br>💰 الرصيد المتبقي: {$remaining} ر.ي
        <br><a href='index.html'>رجوع</a>
    </div>
    ";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "
    <div style='text-align:center;color:red;margin-top:50px'>
        {$e->getMessage()}
        <br><a href='index.html'>رجوع</a>
    </div>
    ";
}
?>
