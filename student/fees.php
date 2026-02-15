<?php
session_start();
$_SESSION['student_id'] = 1;
$_SESSION['student_name'] = 'John Doe';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fees</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .fee-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .summary-item { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; }
    .summary-value { font-size: 2rem; font-weight: 700; color: #3498db; }
    .summary-label { color: #7f8c8d; margin-top: 5px; }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .btn-pay { background: #3498db; color: white; border: none; padding: 8px 20px; border-radius: 5px; }
    .btn-pay:hover { background: #2980b9; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-dollar-sign"></i> Fees Management</h2>
    </div>
    
    <div class="fee-summary">
      <div class="summary-item">
        <div class="summary-value">$2000</div>
        <div class="summary-label">Total Fees</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">$1500</div>
        <div class="summary-label">Paid Amount</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">$500</div>
        <div class="summary-label">Pending Amount</div>
      </div>
      <div class="summary-item">
        <div class="summary-value">75%</div>
        <div class="summary-label">Payment Status</div>
      </div>
    </div>
    
    <div class="content-card">
      <h5 class="mb-4">Fee Payment History</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Receipt No</th>
              <th>Date</th>
              <th>Description</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#FEE001</td>
              <td>Nov 15, 2024</td>
              <td>Tuition Fee - Semester 1</td>
              <td>$1000</td>
              <td><span class="badge bg-success">Paid</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Receipt</button></td>
            </tr>
            <tr>
              <td>#FEE002</td>
              <td>Oct 10, 2024</td>
              <td>Library Fee</td>
              <td>$200</td>
              <td><span class="badge bg-success">Paid</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Receipt</button></td>
            </tr>
            <tr>
              <td>#FEE003</td>
              <td>Sep 05, 2024</td>
              <td>Lab Fee</td>
              <td>$300</td>
              <td><span class="badge bg-success">Paid</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Receipt</button></td>
            </tr>
            <tr>
              <td>#FEE004</td>
              <td>Due: Dec 31, 2024</td>
              <td>Tuition Fee - Semester 2</td>
              <td>$500</td>
              <td><span class="badge bg-warning">Pending</span></td>
              <td><button class="btn btn-sm btn-pay"><i class="fas fa-credit-card"></i> Pay Now</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.nav-item').forEach(item => {
      if (item.href === window.location.href) item.classList.add('active');
    });
  </script>
</body>
</html>
