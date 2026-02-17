<?php session_start(); $_SESSION['admin_id'] = 1; $_SESSION['admin_name'] = 'Admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fee Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .summary-item { text-align: center; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .summary-value { font-size: 2rem; font-weight: 700; color: #3498db; }
    .summary-label { color: #7f8c8d; margin-top: 5px; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-rupee-sign"></i> Fee Management</h2>
      <button class="btn-add" onclick="window.location.href='add-fee.php'" style="background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;"><i class="fas fa-plus"></i> Add Fee Record</button>
    </div>
    <div class="stats-grid">
      <div class="summary-item"><div class="summary-value">₹500K</div><div class="summary-label">Total Collected</div></div>
      <div class="summary-item"><div class="summary-value">₹125K</div><div class="summary-label">Pending</div></div>
      <div class="summary-item"><div class="summary-value">₹25K</div><div class="summary-label">Overdue</div></div>
      <div class="summary-item"><div class="summary-value">80%</div><div class="summary-label">Collection Rate</div></div>
    </div>
    <div class="content-card">
      <h5 class="mb-4">Recent Transactions</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr><th>Receipt No</th><th>Student Name</th><th>Class</th><th>Amount</th><th>Date</th><th>Status</th></tr>
          </thead>
          <tbody>
            <tr><td>#FEE001</td><td>Rahul Sharma</td><td>Grade 10A</td><td>₹1000</td><td>Dec 10, 2024</td><td><span class="badge bg-success">Paid</span></td></tr>
            <tr><td>#FEE002</td><td>Priya Verma</td><td>Grade 10B</td><td>₹1000</td><td>Dec 09, 2024</td><td><span class="badge bg-success">Paid</span></td></tr>
            <tr><td>#FEE003</td><td>Amit Kumar</td><td>Grade 12</td><td>₹1200</td><td>Dec 08, 2024</td><td><span class="badge bg-warning">Pending</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
