<?php
$link = mysqli_connect("localhost", "root", "root", "projects");
if (!$link) { echo "err1:" . mysqli_connect_error(); exit(1); }
$email = 'kaouthar@gmail.com';
$stmt = mysqli_prepare($link, 'SELECT * FROM users WHERE EMAIL = ? LIMIT 1');
if (!$stmt) { echo "prep_err:" . mysqli_error($link); exit(1); }
mysqli_stmt_bind_param($stmt, 's', $email);
if (!mysqli_stmt_execute($stmt)) { echo "exec_err:" . mysqli_stmt_error($stmt); exit(1); }
$res = mysqli_stmt_get_result($stmt);
if ($res !== false) {
    $row = mysqli_fetch_assoc($res);
    var_export($row);
} else {
    mysqli_stmt_store_result($stmt);
    echo "rows:" . mysqli_stmt_num_rows($stmt) . "\n";
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $id, $nom, $prenom, $dbEmail, $dbPass, $dbRole);
        mysqli_stmt_fetch($stmt);
        var_export(['ID' => $id, 'NOM' => $nom, 'PRENOM' => $prenom, 'EMAIL' => $dbEmail, 'PASSWORD' => $dbPass, 'role' => $dbRole]);
    }
}
mysqli_close($link);
?>
