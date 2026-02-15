<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>School ERP System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
   <style>.custom-bg {
  background: url('https://images.pexels.com/photos/22741668/pexels-photo-22741668.jpeg') no-repeat center center;
  background-size: cover;
  padding: 50px; /* adds spacing inside */
  border-radius: 10px; /* optional rounded corners */
  color: white; /* makes text readable */
  text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
}
</style>

<style>
/* General body background and text */
body {
  background-color: #DFE6E9; /* light grey background */
  color: #2D3436;           /* dark grey text */
  font-family: Arial, sans-serif;
}

/* Navbar styling */
.navbar {
  background-color: #2D3436 !important; /* dark grey navbar */
}
.navbar .nav-link,
.navbar .navbar-brand,
.navbar h1 {
  color: #FFFFFF !important; /* white text in navbar */
}
.navbar .nav-link:hover {
  color: #DFE6E9 !important; /* hover effect */
}

/* Welcome section with background image */
.custom-bg {
  background: url('https://images.pexels.com/photos/22741668/pexels-photo-22741668.jpeg') no-repeat center center;
  background-size: cover;
  padding: 50px;
  border-radius: 10px;
  color: #FFFFFF; /* white text for readability */
  text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
}

/* Card styling */
.card {
  background-color: #FFFFFF; /* white card background */
  border: 1px solid #DFE6E9; /* subtle light grey border */
}
.card-title {
  color: #2D3436; /* dark grey titles */
}
.card-text {
  color: #2D3436; /* dark grey text */
}

/* Buttons */
.btn-primary {
  background-color: #2D3436;
  color: #FFFFFF;
}
.btn-primary:hover {
  background-color: #DFE6E9;
  color: #2D3436;
}

.btn-success {
  background-color: #2D3436;
  color: #FFFFFF;
}
.btn-success:hover {
  background-color: #DFE6E9;
  color: #2D3436;
}

.btn-danger {
  background-color: #2D3436;
  color: #FFFFFF;
}
.btn-danger:hover {
  background-color: #DFE6E9;
  color: #2D3436;
}
</style>




<nav class="navbar navbar-expand-lg ">
  <div class="container-fluid  ">
    <a class="navbar-brand" href="#">
      <img src="https://tse4.mm.bing.net/th/id/OIP.rmbEKrgSADO24qJxorAiOgHaHa?cb=defcachec2&w=2000&h=2000&rs=1&pid=ImgDetMain&o=7&rm=3" width="100px" height="90px" alt="">
    </a>
    
    
    <div class="collapse navbar-collapse text-dark " id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li>
          <h1>School ERP System</h1>
        </li>
</div>
        <div class="collapse navbar-collapse ">
          
<ul class="nav justify-content-end">
        <li class="nav-item">
          <a class="nav-link active text-dark " aria-current="page" href="#">Home</a>
        </li>
         <a class="nav-link text-dark " href="login.php">Features</a>
         
        </li>
         </li>
         <a class="nav-link text-dark " href="login.php">About Us</a>
         
        </li>
        
         </li>
         <a class="nav-link text-dark " href="login.php">Contacts</a>
         
        </li>
        <ul>
        </div>
        
        
     
</nav>



  <div class="container text-center custom-bg">
    
    <h1 class="">Welcome to School ERP System</h1>
    <p class="">Manage Students, Teachers, and Administration Efficiently</p>
    <div class="mt-3">
      <a href="#" class="btn btn-success me-2">Login</a>
      <a href="#" class="btn btn-primary">Register</a>
    </div>
  </div>






  <!-- Portal Section -->
  <div class="container my-5">
    <div class="row">

      <!-- Student Portal -->
      <div class="col-md-4">
        <div class="card shadow text-center">
          <!-- Image at top -->
          <img src="https://images.pexels.com/photos/8382388/pexels-photo-8382388.jpeg" class="card-img-top" alt="Student Portal">
          <div class="card-body">
            <h5 class="card-title">Student Portal</h5>
            <p class="card-text">Access assignments, grades, and updates easily.</p>
            <a href="#" class="btn btn-primary">Go to Student Portal</a>
          </div>
        </div>
      </div>

      <!-- Teacher Portal -->
      <div class="col-md-4">
        <div class="card shadow text-center">
          <img src="https://images.pexels.com/photos/5212345/pexels-photo-5212345.jpeg" class="card-img-top" alt="Teacher Portal">
          <div class="card-body">
            <h5 class="card-title">Teacher Portal</h5>
            <p class="card-text">Manage classes, attendance, and student progress.</p>
            <a href="#" class="btn btn-success">Go to Teacher Portal</a>
          </div>
        </div>
      </div>

      <!-- Admin Dashboard -->
      <div class="col-md-4">
        <div class="card shadow text-center">
          <img src="https://images.pexels.com/photos/7310202/pexels-photo-7310202.jpeg" class="card-img-top" alt="Admin Dashboard">
          <div class="card-body">
            <h5 class="card-title">Admin Dashboard</h5>
            <p class="card-text">Oversee school operations and manage records.</p>
            <a href="#" class="btn btn-danger">Go to Admin Dashboard</a>
          </div>
        </div>
      </div>

    </div>
  </div>

 
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
</body>
</html>