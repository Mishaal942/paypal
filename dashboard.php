<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit();
}
include "db.php";

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt2->execute([$userId]);
$wallet = $stmt2->fetch(PDO::FETCH_ASSOC);
$balance = $wallet ? $wallet['balance'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard - PayNow</title>
    <style>
        :root {
            --primary:#0070ba;
            --muted:#6b7280;
            --card:#ffffff;
        }
        *{box-sizing:border-box}
        body{
            margin:0; font-family:Inter, Arial, sans-serif;
            background:linear-gradient(180deg,#f3f6fb,#eef3f7); padding:28px;
        }
        .wrap{ max-width:1100px; margin:0 auto; display:grid; grid-template-columns: 1fr 360px; gap:20px; }
        .header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .greeting h2{ margin:0; color:#0f172a; }
        .greeting p{ margin:4px 0 0 0; color:var(--muted); }

        .card{ background:var(--card); padding:20px; border-radius:12px; box-shadow:0 8px 24px rgba(17,24,39,0.06); }
        .balance-box{ text-align:center; padding:20px; border-radius:10px; background:linear-gradient(180deg,#e8f4ff,#fff); }
        .balance-box h3{ margin:0; color:var(--muted); font-size:14px; }
        .balance-box h1{ margin:6px 0 0 0; font-size:36px; color:#0f172a; }

        .quick { display:flex; gap:12px; margin-top:12px; flex-wrap:wrap; }
        .quick a { text-decoration:none; padding:12px 16px; background:var(--primary); color:#fff; border-radius:10px; font-weight:600; }
        .quick a:hover{ opacity:0.95; }

        .recent { margin-top:16px; }
        .tx { display:flex; justify-content:space-between; padding:12px; border-radius:10px; background:#fff; border:1px solid #eef2f7; margin-bottom:8px; }
        .tx .left{ display:flex; gap:12px; align-items:center; }
        .avatar{ width:44px; height:44px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:var(--primary); font-weight:700; }
        .tx .meta .name{ font-weight:700; }
        .tx .meta .sub{ color:var(--muted); font-size:13px; }
        .amt{ font-weight:800; }

        @media (max-width:900px){
            .wrap{ grid-template-columns: 1fr; padding:12px; }
        }
    </style>
    <script>
        function go(page){ window.location.href = page; }
    </script>
</head>
<body>
    <div class="header">
        <div class="greeting">
            <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div>
            <button onclick="go('logout.php')" style="padding:10px 14px;border-radius:10px;border:none;background:#ef4444;color:#fff;cursor:pointer">Logout</button>
        </div>
    </div>

    <div class="wrap">
        <div>
            <div class="card balance-box">
                <h3>Available Balance</h3>
                <h1>$<?php echo number_format($balance,2); ?></h1>
                <div style="margin-top:12px;" class="quick">
                    <a onclick="go('send_money.php')">Send</a>
                    <a onclick="go('add_funds.php')">Add Funds</a>
                    <a onclick="go('transaction_history.php')">Transactions</a>
                </div>
            </div>

            <div class="card recent" style="margin-top:18px;">
                <h3 style="margin:0 0 12px 0;color:#0f172a">Recent Activity</h3>
                <?php
                    $r = $conn->prepare("SELECT t.*, s.email AS sender_email, r.email AS receiver_email FROM transactions t LEFT JOIN users s ON t.sender_id=s.id LEFT JOIN users r ON t.receiver_id=r.id WHERE t.sender_id=? OR t.receiver_id=? ORDER BY t.created_at DESC LIMIT 6");
                    $r->execute([$userId, $userId]);
                    $recent = $r->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if (count($recent) == 0): ?>
                    <div class="sub">No activity yet. Send or add funds to get started.</div>
                <?php else: ?>
                    <?php foreach($recent as $tx): 
                        $isSender = ($tx['sender_id'] == $userId);
                    ?>
                        <div class="tx">
                            <div class="left">
                                <div class="avatar"><?php echo strtoupper(substr(($isSender ? $tx['receiver_email'] : $tx['sender_email']),0,1)); ?></div>
                                <div class="meta">
                                    <div class="name"><?php echo $isSender ? 'Sent to '.$tx['receiver_email'] : 'Received from '.$tx['sender_email']; ?></div>
                                    <div class="sub"><?php echo date("M d, Y H:i", strtotime($tx['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="amt"><?php echo $isSender?'- ':'+ '; ?>$<?php echo number_format($tx['amount'],2); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <aside>
            <div class="card">
                <h3 style="margin-top:0;color:var(--primary)">Account Summary</h3>
                <p style="font-size:14px;color:var(--muted)">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                <p style="margin-top:10px;color:var(--muted)">Manage your wallet, send money, and view detailed transactions.</p>
                <div style="margin-top:16px;">
                    <a onclick="go('send_money.php')" class="btn" style="display:inline-block;text-decoration:none;">Send Money</a>
                    <a onclick="go('add_funds.php')" class="btn" style="display:inline-block;margin-left:8px;text-decoration:none;background:#10b981;">Add Funds</a>
                </div>
            </div>
        </aside>
    </div>
</body>
</html>
