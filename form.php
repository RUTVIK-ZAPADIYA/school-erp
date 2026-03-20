<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth-pages.css">
  </head>

  <body class="auth-layout">
    <div class="auth-card">
      <div class="logo-section">
        <div class="logo-icon"><i class="fas fa-user-graduate"></i></div>
        <h1 class="auth-title">Student Registration</h1>
        <p class="auth-subtitle">Please fill in your details to create your account</p>
      </div>

      <form action="success.php" method="post">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="fn" class="form-label">First Name</label>
            <input type="text" name="fn" id="fn" class="form-control" required>
          </div>
          <div class="col-md-4 mb-3">
            <label for="mn" class="form-label">Middle Name</label>
            <input type="text" name="mn" id="mn" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label for="ln" class="form-label">Last Name</label>
            <input type="text" name="ln" id="ln" class="form-control" required>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="roll" class="form-label">Roll No</label>
            <input type="text" name="roll" id="roll" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="class" class="form-label">Class</label>
            <input type="text" name="class" id="class" class="form-control" required>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="age" class="form-label">Date of Birth</label>
            <input type="date" name="age" id="age" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="tel" name="phone" id="phone" class="form-control" required>
          </div>
        </div>

        <div class="mb-3">
          <label for="em" class="form-label">Email Address</label>
          <input type="email" name="em" id="em" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label d-block">Gender</label>
          <div class="d-flex flex-wrap gap-3">
            <label class="remember-me"><input type="radio" name="gender" value="Male" required> Male</label>
            <label class="remember-me"><input type="radio" name="gender" value="Female"> Female</label>
            <label class="remember-me"><input type="radio" name="gender" value="Other"> Other</label>
          </div>
        </div>

        <div class="mb-3">
          <label for="addr" class="form-label">Address</label>
          <textarea name="addr" id="addr" rows="3" class="form-control" required></textarea>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="pss" class="form-label">Password</label>
            <input type="password" name="pss" id="pss" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="cpss" class="form-label">Confirm Password</label>
            <input type="password" name="cpss" id="cpss" class="form-control" required>
          </div>
        </div>

        <div class="auth-btn-row mt-3">
          <button type="submit" class="auth-btn">
            <i class="fas fa-user-plus me-1"></i> Register
          </button>
          <button type="reset" class="auth-btn-secondary">
            <i class="fas fa-rotate-left me-1"></i> Reset
          </button>
        </div>
      </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
