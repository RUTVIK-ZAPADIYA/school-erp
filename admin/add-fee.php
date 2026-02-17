<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Fee Record</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fcfbfb; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom: 3px solid #f7d794; }
    .header h2 { color: #192a56; margin: 0; font-weight: 700; }
    .form-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 3px solid #f7d794; }
    .form-label { color: #192a56; font-weight: 600; margin-bottom: 8px; }
    .form-control, .form-select { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .form-control:focus, .form-select:focus { border-color: #f7d794; box-shadow: 0 0 0 3px rgba(247,215,148,0.25); }
    .btn-submit { background: #f7d794; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-submit:hover { background: #e5c682; }
    .btn-cancel { background: #e2e8f0; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; margin-left: 10px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-rupee-sign"></i> Add Fee Record</h2>
    </div>
    
    <div class="form-card">
      <form method="POST" action="">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Student *</label>
            <select class="form-select" name="student_id" data-validation="required,select">
              <option value="">Select Student</option>
              <option value="1">Rahul Sharma - Grade 10A</option>
              <option value="2">Priya Verma - Grade 10B</option>
              <option value="3">Amit Kumar - Grade 12</option>
            </select>
            <div id="student_id_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Fee Type *</label>
            <select class="form-select" name="fee_type" data-validation="required,select">
              <option value="">Select Fee Type</option>
              <option value="Tuition Fee">Tuition Fee</option>
              <option value="Exam Fee">Exam Fee</option>
              <option value="Library Fee">Library Fee</option>
              <option value="Transport Fee">Transport Fee</option>
              <option value="Other">Other</option>
            </select>
            <div id="fee_type_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Amount *</label>
            <input type="text" class="form-control" name="amount" placeholder="Enter amount" data-validation="required,number,min" data-min="1">
            <div id="amount_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Due Date *</label>
            <input type="date" class="form-control" name="due_date" data-validation="required">
            <div id="due_date_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Payment Status *</label>
            <select class="form-select" name="payment_status" data-validation="required,select">
              <option value="">Select Status</option>
              <option value="Pending">Pending</option>
              <option value="Paid">Paid</option>
              <option value="Partial">Partial</option>
              <option value="Overdue">Overdue</option>
            </select>
            <div id="payment_status_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Payment Method</label>
            <select class="form-select" name="payment_method" data-validation="select">
              <option value="">Select Method</option>
              <option value="Cash">Cash</option>
              <option value="Online">Online</option>
              <option value="Cheque">Cheque</option>
              <option value="Card">Card</option>
            </select>
            <div id="payment_method_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Remarks</label>
          <textarea class="form-control" name="remarks" rows="3" placeholder="Additional notes" data-validation="max" data-max="500"></textarea>
          <div id="remarks_error" class="invalid-feedback"></div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Fee Record</button>
          <a href="fees.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>
