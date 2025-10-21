<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit();
}
include "db.php";

$userId = $_SESSION['user_id'];
$message = "";
$error = "";

// fetch current user info
$stmtU = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
$stmtU->execute([$userId]);
$currentUser = $stmtU->fetch(PDO::FETCH_ASSOC);

// show last 8 transactions involving this user
$recentStmt = $conn->prepare("
    SELECT t.*, s.email AS sender_email, r.email AS receiver_email, s.full_name AS sender_name, r.full_name AS receiver_name
    FROM transactions t
    LEFT JOIN users s ON t.sender_id = s.id
    LEFT JOIN users r ON t.receiver_id = r.id
    WHERE t.sender_id = :uid OR t.receiver_id = :uid
    ORDER BY t.created_at DESC
    LIMIT 8
");
$recentStmt->execute([':uid' => $userId]);
$recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiverEmail = strtolower(trim($_POST['receiver']));
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $error = "Please enter a valid amount.";
    } elseif (!filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid receiver email.";
    } else {
        try {
            // start DB transaction
            $conn->beginTransaction();

            // get sender wallet and lock it (for update style)
            $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $senderWallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$senderWallet || $senderWallet['balance'] < $amount) {
                $error = "Insufficient balance.";
                $conn->rollBack();
            } else {
                // find or create receiver user
                $stmtR = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmtR->execute([$receiverEmail]);
                $receiver = $stmtR->fetch(PDO::FETCH_ASSOC);

                if (!$receiver) {
                    // create receiver account automatically (placeholder)
                    $placeholderName = explode('@', $receiverEmail)[0];
                    $pwd = password_hash(bin2hex(random_bytes(6)), PASSWORD_BCRYPT);
                    $insR = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                    $insR->execute([$placeholderName, $receiverEmail, $pwd]);
                    $receiverId = $conn->lastInsertId();

                    // create wallet for new receiver
                    $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$receiverId]);
                } else {
                    $receiverId = $receiver['id'];
                }

                // deduct from sender
                $upd1 = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
                $upd1->execute([$amount, $userId]);

                // add to receiver
                $upd2 = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                $upd2->execute([$amount, $receiverId]);

                // insert transaction
                $insT = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, status) VALUES (?, ?, ?, ?)");
                $insT->execute([$userId, $receiverId, $amount, 'completed']);

                $conn->commit();

                $message = "Payment of $" . number_format($amount, 2) . " sent to {$receiverEmail} successfully.";
                // refresh recent transactions
                $recentStmt->execute([':uid' => $userId]);
                $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Send Money - PayNow</title>
    <style>
        :root{
            --primary:#0070ba;
            --accent:#e8f4ff;
            --card-bg:#ffffff;
            --muted:#6b7280;
            --glass: rgba(255,255,255,0.8);
        }
        *{box-sizing:border-box}
        body{
            margin:0; font-family: Inter, Arial, sans-serif;
            background: linear-gradient(180deg,#f3f6fb 0%, #eef3f7 100%);
            padding:30px;
        }
        .wrap{
            max-width:1000px; margin:0 auto; display:grid;
            grid-template-columns: 1fr 380px; gap:24px;
        }
        .card{
            background:var(--card-bg); border-radius:12px;
            padding:22px; box-shadow: 0 6px 20px rgba(19,24,31,0.06);
        }
        .hero{
            display:flex; align-items:center; justify-content:space-between;
            margin-bottom:14px;
        }
        .hero h1{ margin:0; color:var(--primary); font-size:20px; }
        .small{ color:var(--muted); font-size:13px; }

        /* Form */
        .send-form { max-width:540px; }
        label{ display:block; font-size:13px; color:#374151; margin-top:12px; margin-bottom:8px; }
        input[type="email"], input[type="number"]{
            width:100%; padding:12px 14px; border-radius:10px;
            border:1px solid #e6e9ee; font-size:15px; outline:none;
            background:linear-gradient(180deg,#fff,#fbfdff);
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button{ -webkit-appearance: none; margin: 0; }
        .row{ display:flex; gap:12px; align-items:center; margin-top:14px; }
        .btn{
            background:var(--primary); color:white; border:none; padding:12px 18px;
            border-radius:10px; cursor:pointer; font-weight:600;
        }
        .btn:active{ transform:translateY(1px); }
        .note { font-size:13px; color:#0f172a; margin-top:10px; }

        /* Right column - recent */
        .recent-list { padding:0; margin:0; list-style:none; }
        .tx {
            display:flex; justify-content:space-between; align-items:center;
            padding:12px; border-radius:8px; margin-bottom:10px;
            background:linear-gradient(180deg,#fbfdff,#ffffff);
            border:1px solid #f1f5f9;
        }
        .tx .meta{ display:flex; gap:10px; align-items:center; }
        .avatar{
            width:44px; height:44px; border-radius:10px; display:flex;
            align-items:center; justify-content:center; background:#f1f5f9; color:var(--primary);
            font-weight:700;
        }
        .tx .info{ font-size:14px; color:#0f172a; }
        .tx .sub{ font-size:12px; color:var(--muted); }
        .amount { font-weight:700; color:#0f172a; }

        .msg { padding:10px 12px; border-radius:8px; margin-bottom:12px; font-weight:600; }
        .success { background:#ecfdf5; color:#065f46; border:1px solid #bbf7d0; }
        .err { background:#fff1f2; color:#9f1239; border:1px solid #fecaca; }

        @media (max-width:900px){
            .wrap{ grid-template-columns: 1fr; padding:10px; }
        }
    </style>
    <script>
        function goDash(){ window.location.href='dashboard.php'; }
    </script>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <div>
                    <h1>Send Money</h1>
                    <div class="small">From: <?php echo htmlspecialchars($currentUser['email']); ?></div>
                </div>
                <div>
                    <button class="btn" onclick="goDash()">Back to Dashboard</button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="msg success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg err"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="send-form" autocomplete="off">
                <label>Receiver Email</label>
                <input type="email" name="receiver" placeholder="receiver@example.com" required>

                <label>Amount (USD)</label>
                <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00" required>

                <div class="row">
                    <button type="submit" class="btn">Send Money</button>
                    <div class="note">All transfers are instant within the system.</div>
                </div>
            </form>

            <hr style="margin:18px 0;border:none;border-top:1px solid #eef2f7;">

            <h3 style="margin:0 0 10px 0;color:#111827">Recent activity</h3>
            <?php if (count($recent) == 0): ?>
                <div class="small">No recent activity yet.</div>
            <?php else: ?>
                <?php foreach($recent as $r): ?>
                    <div class="tx">
                        <div class="meta">
                            <div class="avatar"><?php
                                $initial = substr(($r['receiver_name']?:$r['receiver_email']),0,1);
                                echo strtoupper(htmlspecialchars($initial));
                            ?></div>
                            <div>
                                <div class="info">
                                    <?php
                                        $isSent = ($r['sender_id'] == $userId);
                                        if ($isSent) {
                                            echo "To " . htmlspecialchars($r['receiver_email']);
                                        } else {
                                            echo "From " . htmlspecialchars($r['sender_email']);
                                        }
                                    ?>
                                </div>
                                <div class="sub"><?php echo date("M d, Y H:i", strtotime($r['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="amount"><?php echo ($r['sender_id']==$userId?'- ':'+ '); ?>$<?php echo number_format($r['amount'],2); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <aside class="card">
            <h3 style="margin-top:0;color:var(--primary)">Quick Summary</h3>
            <?php
                // fetch balance
                $b = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
                $b->execute([$userId]);
                $bal = $b->fetch(PDO::FETCH_ASSOC);
                $balance = $bal ? number_format($bal['balance'],2) : "0.00";
            ?>
            <div style="font-size:28px; font-weight:700; margin:10px 0;">$<?php echo $balance; ?></div>
            <div class="small">Available balance in your PayNow wallet</div>

            <hr style="margin:18px 0;border:none;border-top:1px solid #eef2f7;">

            <h4 style="margin:0 0 8px 0;">Helpful tips</h4>
            <ul style="padding-left:18px;color:var(--muted);font-size:14px;">
                <li>Use a valid email address for receiver.</li>
                <li>If receiver doesn't have an account, one will be created automatically.</li>
                <li>All amounts are in USD for demo.</li>
            </ul>
        </aside>
    </div>
</body>
</html>
