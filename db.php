<?php

$con = mysqli_connect("localhost", "root", "",);

mysqli_select_db($con, "school_erp");
echo "Database Connected";

// $registration_table = "CREATE TABLE registration(
//     name VARCHAR(50) NOT NULL,
//     email VARCHAR(50) NOT NULL,
//     password VARCHAR(20) NOT NULL
// )";

// if (mysqli_query($con, $registration_table)) {
//     echo "Table Created";
// } else {
//     echo "Error Creating Table: " . mysqli_error($con);
// }

// if ($con){
//     echo "Connection Successful";
// } else {
//     echo "No Connection";
// }

// //$create_db = "CREATE DATABASE school_erp";

?>