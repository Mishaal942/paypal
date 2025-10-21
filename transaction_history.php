<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit();
}
include "db.php";
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT t.*, 
           s.email AS sender_email, s.full_name AS sender_name,
           r.email AS receiver_email, r.full_name AS receiver_name
    FROM transactions t
    LEFT JOIN users s ON t.sender_id = s.id
    LEFT JOIN users r ON t.receiver_id = r.id
    WHERE t.sender_id = :uid OR t.receiver_id = :uid
    ORDER BY t.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transactions - PayNow</title>
    <style>
        *{box-sizing:border-box}
        body{ margin:0; font-family:Inter, Arial, sans-serif; background:#f6f9fc; padding:26px;}
        .wrap{ max-width:1000px; margin:0 auto; }
        .head { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
        .head h1 { margin:0; color:#0f172a; font-size:20px; }
        .btn { background:#0070ba; color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer; }
        .card { background:#fff; padding:18px; border-radius:12px; box-shadow:0 6px 18px rgba(17,24,39,0.06); }

        table { width:100%; border-collapse:collapse; font-size:14px; margin-top:8px; }
        thead th {
            text-align:left; padding:12px; background:linear-gradient(90deg,#0070ba,#005fa3); color:white;
            border-bottom: none; border-radius:8px;
        }
        tbody tr { border-bottom:1px solid #eef2f7; }
        td { padding:12px; vertical-align:middle; color:#0f172a; }
        .name { font-weight:600; }
        .sub { color:#6b7280; font-size:13px; }
        .amt { font-weight:700; }
        .green { color:#065f46; }
        .red { color:#9f1239; }

        @media (max-width:700px){
            thead { display:none; }
            table, tbody, tr, td { display:block; width:100%; }
            tr { margin-bottom:12px; }
            td { padding:10px; border-radius:8px; background:#fff; box-shadow:0 6px 18px rgba(17,24,39,0.03); }
            td .label { font-size:12px; color:#6b7280; display:block; margin-bottom:6px; }
        }
    </style>
    <script>
        function goDash(){ window.location='dashboard.php'; }
    </script>
</head>
<body>
    <div class="wrap">
        <div class="head">
            <h1>Your Transactions</h1>
            <div>
                <button class="btn" onclick="goDash()">Back to Dashboard</button>
            </div>
        </div>

        <div class="card">
            <?php if (count($transactions) == 0): ?>
                <div class="sub">No transactions yet. Try sending or adding funds to see activity.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Details</th>
                            <th>Counterparty</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $t): 
                            $isSender = ($t['sender_id'] == $userId);
                            $counterEmail = $isSender ? $t['receiver_email'] : $t['sender_email'];
                            $counterName = $isSender ? $t['receiver_name'] : $t['sender_name'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['id']); ?></td>
                            <td>
                                <div class="name"><?php echo $isSender ? 'Sent' : 'Received'; ?></div>
                                <div class="sub"><?php echo htmlspecialchars($isSender ? $t['sender_email'] : $t['receiver_email']); ?></div>
                            </td>
                            <td>
                                <div class="name"><?php echo htmlspecialchars($counterName ?: $counterEmail); ?></div>
                                <div class="sub"><?php echo htmlspecialchars($counterEmail); ?></div>
                            </td>
                            <td class="amt <?php echo $isSender ? 'red' : 'green'; ?>">
                                <?php echo $isSender ? '- ' : '+ '; ?>$<?php echo number_format($t['amount'],2); ?>
                            </td>
                            <td><?php echo date("M d, Y H:i", strtotime($t['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
