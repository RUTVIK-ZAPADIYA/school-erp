<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>

  <body style="margin:0; font-family:Arial, sans-serif; background:linear-gradient(to right,#4e73df,#1cc88a);">

    <div style="width:60%; margin:40px auto; background:white; padding:30px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
      
      <h1 style="text-align:center; margin-bottom:30px; font-weight:bold; color:#4e73df;">
        Welcome, Please Register Yourself Here
      </h1>

      <form action="success.php" method="post">

        <div style="padding:15px;">

            <label for="fn" style="font-size:20px;">Enter Your First Name:</label>
            <input type="text" name="fn" id="fn" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="mn" style="font-size:20px;">Enter Your Middle Name:</label>
            <input type="text" name="mn" id="mn" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="ln" style="font-size:20px;">Enter Your Last Name:</label>
            <input type="text" name="ln" id="ln" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="roll" style="font-size:20px;">Enter Your Roll No:</label>
            <input type="text" name="roll" id="roll" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="class" style="font-size:20px;">Enter Your Class:</label>
            <input type="text" name="class" id="class" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="age" style="font-size:20px;">Enter Your Date of Birth:</label>
            <input type="date" name="age" id="age" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="phone" style="font-size:20px;">Enter Your Phone No:</label>
            <input type="tel" name="phone" id="phone" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="em" style="font-size:20px;">Enter Your Email Id:</label>
            <input type="email" name="em" id="em" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label style="font-size:20px;">Gender:</label><br>
            <input type="radio" name="gender" value="Male"> Male
            <input type="radio" name="gender" value="Female" style="margin-left:15px;"> Female
            <input type="radio" name="gender" value="Other" style="margin-left:15px;"> Other
            <br><br>

            <label for="addr" style="font-size:20px;">Enter Your Address:</label>
            <textarea name="addr" id="addr" rows="3" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;"></textarea>

            <label for="pss" style="font-size:20px;">Password:</label>
            <input type="password" name="pss" id="pss" style="width:100%; padding:8px; font-size:18px; margin-bottom:10px; border-radius:8px; border:1px solid gray;">

            <label for="cpss" style="font-size:20px;">Confirm Password:</label>
            <input type="password" name="cpss" id="cpss" style="width:100%; padding:8px; font-size:18px; margin-bottom:20px; border-radius:8px; border:1px solid gray;">

            <div style="text-align:center;">
              <button type="submit" style="background:#4e73df; color:white; padding:10px 30px; font-size:18px; border:none; border-radius:8px;">
                Register
              </button>


              <button type="reset" style="background:#e74a3b; color:white; padding:10px 30px; font-size:18px; border:none; border-radius:8px; margin-left:10px;">
                Reset
              </button>
            </div>

        </div>

      </form>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
