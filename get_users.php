<?php
$conn = mysqli_connect('localhost', 'root', 'root', 'projects');
$result = mysqli_query($conn, 'SELECT ID, EMAIL, PASSWORD, NOM FROM users WHERE ID IN (2, 3, 11, 12) LIMIT 10');
echo "Available test users:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo 'ID: ' . $row['ID'] . ' | Email: ' . $row['EMAIL'] . ' | Pass: ' . $row['PASSWORD'] . ' | Name: ' . $row['NOM'] . "\n";
}
?>
