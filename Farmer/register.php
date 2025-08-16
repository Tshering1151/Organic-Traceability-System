<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tshering_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $cid = $_POST['cid'];
    $name = $_POST['name'];
    $phone_no = $_POST['phone_no'];
    $dzo_name = $_POST['dzo_name'];
    $gewog_name = $_POST['gewog_name'];
    $village = $_POST['village'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // File upload
    $certificate = $_FILES['certificate']['name'];
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($certificate);
    move_uploaded_file($_FILES['certificate']['tmp_name'], $target_file);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO user_tbl (cid, name, phone_no, dzo_name, gewog_name, village, certificate, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $cid, $name, $phone_no, $dzo_name, $gewog_name, $village, $target_file, $password);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

// Fetch Dzongkhags
$dzo_result = $conn->query("SELECT dzo_id, dzo_name FROM dzo_tbl");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Farmer Registration</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<h2>Farmer Registration</h2>
<form method="POST" enctype="multipart/form-data">
    <label>CID:</label>
    <input type="text" name="cid" required><br>

    <label>Name:</label>
    <input type="text" name="name" required><br>

    <label>Phone No:</label>
    <input type="text" name="phone_no" required><br>

    <label>Dzongkhag:</label>
    <select name="dzo_name" id="dzo" required>
        <option value="">Select Dzongkhag</option>
        <?php while($row = $dzo_result->fetch_assoc()): ?>
            <option value="<?= $row['dzo_name'] ?>" data-id="<?= $row['dzo_id'] ?>"><?= $row['dzo_name'] ?></option>
        <?php endwhile; ?>
    </select><br>

    <label>Gewog:</label>
    <select name="gewog_name" id="gewog" required>
        <option value="">Select Gewog</option>
    </select><br>

    <label>Village:</label>
    <input type="text" name="village" required><br>

    <label>Organic Certificate:</label>
    <input type="file" name="certificate" accept=".pdf,.jpg,.png" required><br>

    <label>Password:</label>
    <input type="password" name="password" required><br>

    <button type="submit">Register</button>
</form>

<script>
$(document).ready(function(){
    $("#dzo").change(function(){
        var dzo_id = $(this).find(":selected").data("id");
        $.ajax({
            url: "get_gewogs.php",
            type: "POST",
            data: {dzo_id: dzo_id},
            success: function(data){
                $("#gewog").html(data);
            }
        });
    });
});
</script>
</body>
</html>
