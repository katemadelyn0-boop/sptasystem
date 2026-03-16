<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Accessible by staff, officer, admin, and parent
if (!isset($_SESSION['user_id'])) {
    header('Location: /spta-system/login.php'); exit;
}

$db  = getDB();
$rid = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];

if (!$rid) { echo "Invalid receipt."; exit; }

// Fetch receipt + payment details
$s = $db->prepare("
    SELECT r.*, p.amount_paid, p.payment_method, p.payment_date, p.status,
           p.reference_no, p.remarks, p.requirement_id,
           CONCAT(s.first_name,' ',s.last_name) student_name,
           s.lrn, g.grade_name, sy.sy_label,
           pc.category_name, pc.managed_by,
           pr.amount AS required_amount,
           CONCAT(u.first_name,' ',u.last_name) issued_by_name
    FROM receipts r
    JOIN payments p ON r.payment_id = p.payment_id
    JOIN students s ON p.student_id = s.student_id
    JOIN grade_levels g ON s.grade_id = g.grade_id
    JOIN school_years sy ON s.sy_id = sy.sy_id
    JOIN payment_requirements pr ON p.requirement_id = pr.requirement_id
    JOIN payment_categories pc ON pr.category_id = pc.category_id
    JOIN users u ON r.issued_by = u.user_id
    WHERE r.receipt_id = ?
");
$s->bind_param('i', $rid);
$s->execute();
$rec = $s->get_result()->fetch_assoc();
$s->close();

if (!$rec) { echo "Receipt not found."; exit; }

// Security: parent can only view their own child's receipt
if ($role === 'parent') {
    $chk = $db->prepare("SELECT id FROM parent_student ps JOIN payments p ON ps.student_id = p.student_id JOIN receipts r ON p.payment_id = r.payment_id WHERE r.receipt_id = ? AND ps.parent_id = ?");
    $chk->bind_param('ii', $rid, $_SESSION['user_id']);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows === 0) { echo "Access denied."; exit; }
    $chk->close();
}

$balance = $rec['required_amount'] - $rec['amount_paid'];
$balance = max(0, $balance);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Receipt <?= htmlspecialchars($rec['receipt_no']) ?> — SPTA System</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:#f3f4f6;padding:32px 16px;color:#1f2937;}

    .receipt-wrap{max-width:600px;margin:0 auto;}

    /* Action bar - hidden on print */
    .action-bar{display:flex;gap:10px;justify-content:flex-end;margin-bottom:20px;}
    .btn{padding:10px 20px;border-radius:10px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
    .btn-print{background:#0f2342;color:#fff;}
    .btn-back{background:#fff;color:#374151;border:1.5px solid #d1d5db;}

    /* Receipt card */
    .receipt{background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);}

    /* Header */
    .r-header{background:#0f2342;padding:32px 40px;text-align:center;}
    .r-header .school{color:rgba(255,255,255,0.7);font-size:13px;font-weight:500;margin-bottom:4px;letter-spacing:0.5px;text-transform:uppercase;}
    .r-header h1{color:#fff;font-size:22px;font-weight:800;margin-bottom:4px;}
    .r-header .receipt-no{color:#e8a020;font-size:15px;font-weight:700;letter-spacing:1px;}

    /* Status banner */
    .status-banner{padding:14px 40px;text-align:center;font-weight:700;font-size:14px;letter-spacing:0.5px;text-transform:uppercase;}
    .status-banner.paid{background:#dcfce7;color:#16a34a;}
    .status-banner.partial{background:#fef3c7;color:#d97706;}
    .status-banner.unpaid{background:#fee2e2;color:#dc2626;}

    /* Body */
    .r-body{padding:32px 40px;}

    /* Section title */
    .section-label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;margin-top:24px;}
    .section-label:first-child{margin-top:0;}

    /* Info rows */
    .info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f3f4f6;}
    .info-row:last-child{border-bottom:none;}
    .info-label{font-size:13px;color:#6b7280;}
    .info-value{font-size:14px;font-weight:600;color:#0f2342;text-align:right;}

    /* Amount highlight */
    .amount-box{background:#f8faff;border:2px solid #dbeafe;border-radius:14px;padding:20px 24px;margin:20px 0;display:flex;justify-content:space-between;align-items:center;}
    .amount-box .label{font-size:13px;color:#6b7280;}
    .amount-box .value{font-size:28px;font-weight:800;color:#0f2342;}
    .amount-box.balance .value{color:#dc2626;}
    .amount-box.balance{border-color:#fecaca;background:#fff5f5;}

    /* Divider */
    .divider{border:none;border-top:2px dashed #e5e7eb;margin:24px 0;}

    /* Footer */
    .r-footer{background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;}
    .r-footer .issued{font-size:12px;color:#9ca3af;}
    .r-footer .issued strong{color:#6b7280;}
    .official-stamp{border:2px solid #0f2342;border-radius:10px;padding:8px 16px;text-align:center;}
    .official-stamp div:first-child{font-size:9px;font-weight:700;color:#9ca3af;letter-spacing:1px;text-transform:uppercase;}
    .official-stamp div:last-child{font-size:11px;font-weight:800;color:#0f2342;}

    /* Print styles */
    @media print {
      body{background:#fff;padding:0;}
      .action-bar{display:none!important;}
      .receipt{box-shadow:none;border-radius:0;}
      @page{margin:0.5cm;}
    }
  </style>
</head>
<body>

<div class="receipt-wrap">

  <!-- Action Buttons -->
  <div class="action-bar no-print">
    <?php
      $back = match($role) {
        'staff'        => '/spta-system/staff/payments.php',
        'spta_officer' => '/spta-system/officer/payments.php',
        'admin'        => '/spta-system/admin/reports.php',
        'parent'       => '/spta-system/parent/payments.php',
        default        => '/spta-system/'
      };
    ?>
    <a href="<?= $back ?>" class="btn btn-back">
      <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
      Back
    </a>
    <button onclick="window.print()" class="btn btn-print">
      <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
      Print / Save as PDF
    </button>
  </div>

  <!-- Receipt -->
  <div class="receipt">

    <!-- Header -->
    <div class="r-header">
      <div class="school">Pawing Central School</div>
      <h1>Official Payment Receipt</h1>
      <div class="receipt-no"><?= htmlspecialchars($rec['receipt_no']) ?></div>
    </div>

    <!-- Status Banner -->
    <div class="status-banner <?= $rec['status'] ?>">
      <?php
        $statusLabels = ['paid' => '✓ Fully Paid', 'partial' => '◑ Partial Payment', 'unpaid' => '✗ Unpaid'];
        echo $statusLabels[$rec['status']] ?? ucfirst($rec['status']);
      ?>
    </div>

    <!-- Body -->
    <div class="r-body">

      <!-- Amount Paid -->
      <div class="amount-box">
        <div>
          <div class="label">Amount Paid</div>
          <div style="font-size:12px;color:#9ca3af;margin-top:2px;"><?= htmlspecialchars($rec['category_name']) ?></div>
        </div>
        <div class="value">&#8369;<?= number_format($rec['amount_paid'], 2) ?></div>
      </div>

      <?php if ($balance > 0): ?>
      <div class="amount-box balance">
        <div>
          <div class="label">Remaining Balance</div>
          <div style="font-size:12px;color:#9ca3af;margin-top:2px;">Required: &#8369;<?= number_format($rec['required_amount'], 2) ?></div>
        </div>
        <div class="value">&#8369;<?= number_format($balance, 2) ?></div>
      </div>
      <?php endif; ?>

      <hr class="divider"/>

      <!-- Student Info -->
      <div class="section-label">Student Information</div>
      <div class="info-row">
        <span class="info-label">Student Name</span>
        <span class="info-value"><?= htmlspecialchars($rec['student_name']) ?></span>
      </div>
      <?php if ($rec['lrn']): ?>
      <div class="info-row">
        <span class="info-label">LRN</span>
        <span class="info-value"><?= htmlspecialchars($rec['lrn']) ?></span>
      </div>
      <?php endif; ?>
      <div class="info-row">
        <span class="info-label">Grade Level</span>
        <span class="info-value"><?= htmlspecialchars($rec['grade_name']) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">School Year</span>
        <span class="info-value"><?= htmlspecialchars($rec['sy_label']) ?></span>
      </div>

      <hr class="divider"/>

      <!-- Payment Info -->
      <div class="section-label">Payment Details</div>
      <div class="info-row">
        <span class="info-label">Payment For</span>
        <span class="info-value"><?= htmlspecialchars($rec['category_name']) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Payment Method</span>
        <span class="info-value"><?= ucfirst(str_replace('_', ' ', $rec['payment_method'])) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Payment Date</span>
        <span class="info-value"><?= date('F d, Y', strtotime($rec['payment_date'])) ?></span>
      </div>
      <?php if ($rec['reference_no']): ?>
      <div class="info-row">
        <span class="info-label">Reference No.</span>
        <span class="info-value"><?= htmlspecialchars($rec['reference_no']) ?></span>
      </div>
      <?php endif; ?>
      <div class="info-row">
        <span class="info-label">Received From</span>
        <span class="info-value"><?= htmlspecialchars($rec['issued_to']) ?></span>
      </div>
      <?php if ($rec['remarks']): ?>
      <div class="info-row">
        <span class="info-label">Remarks</span>
        <span class="info-value" style="max-width:55%;text-align:right;"><?= htmlspecialchars($rec['remarks']) ?></span>
      </div>
      <?php endif; ?>

    </div><!-- end r-body -->

    <!-- Footer -->
    <div class="r-footer">
      <div class="issued">
        Issued on <strong><?= date('F d, Y \a\t g:i A', strtotime($rec['issued_at'])) ?></strong><br/>
        Issued by <strong><?= htmlspecialchars($rec['issued_by_name']) ?></strong>
      </div>
      <div class="official-stamp">
        <div>Pawing Central School</div>
        <div>OFFICIAL RECEIPT</div>
      </div>
    </div>

  </div><!-- end receipt -->

  <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:16px;">
    This is an official digital receipt generated by the SPTA Payment Management System.
  </p>

</div>

</body>
</html>