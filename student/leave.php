<?php
session_start();
$_SESSION['student_id'] = 1;
$_SESSION['student_name'] = 'Rahul Sharma';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Application</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .form-label { color: #2d3748; font-weight: 500; margin-bottom: 8px; }
    .form-control, .form-select { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .form-control:focus, .form-select:focus { border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
    .btn-submit { background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-submit:hover { background: #2980b9; }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-file-alt"></i> Leave Application</h2>
    </div>
    
    <div class="content-card">
      <h5 class="mb-4">Apply for Leave</h5>
      <form method="POST" action="">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Leave Type</label>
            <select class="form-select" name="leave_type" required>
              <option value="">Select Type</option>
              <option value="sick">Sick Leave</option>
              <option value="casual">Casual Leave</option>
              <option value="emergency">Emergency Leave</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Number of Days</label>
            <input type="number" class="form-control" name="days" min="1" required>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" name="from_date" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" name="to_date" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Reason</label>
          <textarea class="form-control" name="reason" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Application</button>
      </form>
    </div>
    
    <div class="content-card">
      <h5 class="mb-4">Leave Application History</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Application ID</th>
              <th>Leave Type</th>
              <th>From Date</th>
              <th>To Date</th>
              <th>Days</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#LA001</td>
              <td>Sick Leave</td>
              <td>Dec 09, 2024</td>
              <td>Dec 10, 2024</td>
              <td>2</td>
              <td><span class="badge bg-success">Approved</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>#LA002</td>
              <td>Casual Leave</td>
              <td>Nov 20, 2024</td>
              <td>Nov 22, 2024</td>
              <td>3</td>
              <td><span class="badge bg-success">Approved</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
            </tr>
            <tr>
              <td>#LA003</td>
              <td>Emergency Leave</td>
              <td>Dec 15, 2024</td>
              <td>Dec 16, 2024</td>
              <td>2</td>
              <td><span class="badge bg-warning">Pending</span></td>
              <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</button></td>
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
