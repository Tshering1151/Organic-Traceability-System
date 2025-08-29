<?php
require_once __DIR__ . '/database/db.php';

if (!isset($_POST['dzo_id'])) {
    echo '<option value="">-- Select Gewog --</option>';
    exit;
}

$dzo_id = (int) $_POST['dzo_id'];

$sql = "SELECT gewog_id, gewog_name FROM gewog_tbl WHERE dzo_id = ? ORDER BY gewog_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dzo_id);
$stmt->execute();
$res = $stmt->get_result();

echo '<option value="">-- Select Gewog --</option>';
while ($row = $res->fetch_assoc()) {
    // we use the name as the form value since your user_tbl stores gewog_name
    echo '<option value="'.htmlspecialchars($row['gewog_name']).'">'.htmlspecialchars($row['gewog_name']).'</option>';
}
