<?php
function connectMaBasi(){
$basi=mysqli_connect("localhost","root","","projects");

if (!$basi) {
    die("Connection failed: " . mysqli_connect_error());
}
// echo "Connected successfully";

return $basi;



}


?>