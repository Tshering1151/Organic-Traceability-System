<?php
include '../database/db.php';

if(isset($_POST['dzo_id'])){
    $dzo_id = $_POST['dzo_id'];

    $query = $conn->prepare("SELECT gewog_name FROM gewog_tbl WHERE dzo_id = ? ORDER BY gewog_name ASC");
    $query->bind_param("i", $dzo_id);
    $query->execute();
    $result = $query->get_result();

    echo '<option value="">Select Gewog</option>';
    while($row = $result->fetch_assoc()){
        echo '<option value="'.$row['gewog_name'].'">'.$row['gewog_name'].'</option>';
    }
}
?>