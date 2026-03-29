<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schedule New Exam</title>
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
      <h2><i class="fas fa-file-alt"></i> Schedule New Exam</h2>
    </div>
    
    <div class="form-card">
      <form method="POST" action="">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Exam Name *</label>
            <input type="text" class="form-control" name="exam_name" placeholder="e.g., Mid-term Exam" data-validation="required,min" data-min="3">
            <div id="exam_name_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Exam Type *</label>
            <select class="form-select" name="exam_type" data-validation="required,select">
              <option value="">Select Type</option>
              <option value="Mid-term">Mid-term</option>
              <option value="Final">Final</option>
              <option value="Unit Test">Unit Test</option>
              <option value="Quiz">Quiz</option>
            </select>
            <div id="exam_type_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Class *</label>
            <select class="form-select" name="class" data-validation="required,select">
              <option value="">Select Class</option>
              <option value="Grade 1">Grade 1</option>
              <option value="Grade 10">Grade 10</option>
              <option value="Grade 12">Grade 12</option>
            </select>
            <div id="class_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Subject *</label>
            <select class="form-select" name="subject" data-validation="required,select">
              <option value="">Select Subject</option>
              <option value="Mathematics">Mathematics</option>
              <option value="Physics">Physics</option>
              <option value="Chemistry">Chemistry</option>
              <option value="English">English</option>
            </select>
            <div id="subject_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Exam Date *</label>
            <input type="date" class="form-control" name="exam_date" data-validation="required">
            <div id="exam_date_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Start Time *</label>
            <input type="time" class="form-control" name="start_time" data-validation="required">
            <div id="start_time_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Duration (Minutes) *</label>
            <input type="text" class="form-control" name="duration" placeholder="e.g., 120" data-validation="required,number,min" data-min="1">
            <div id="duration_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Total Marks *</label>
            <input type="text" class="form-control" name="total_marks" placeholder="e.g., 100" data-validation="required,number,min" data-min="1">
            <div id="total_marks_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Room Number *</label>
            <input type="text" class="form-control" name="room_number" placeholder="e.g., 101" data-validation="required,number,min" data-min="1">
            <div id="room_number_error" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Invigilator</label>
            <select class="form-select" name="invigilator" data-validation="select">
              <option value="">Select Teacher</option>
              <option value="1">Prof. Priya Patel</option>
              <option value="2">Dr. Rajesh Kumar</option>
              <option value="3">Ms. Anjali Gupta</option>
            </select>
            <div id="invigilator_error" class="invalid-feedback"></div>
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Instructions</label>
          <textarea class="form-control" name="instructions" rows="3" placeholder="Exam instructions for students" data-validation="max" data-max="1000"></textarea>
          <div id="instructions_error" class="invalid-feedback"></div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Schedule Exam</button>
          <a href="exams.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>
