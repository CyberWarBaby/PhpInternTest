<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$conn = new mysqli("localhost", "root", "", "bincomphptest"); // Adjust credentials if needed

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$polling_unit_id = $lga_id = 0;
$polling_unit_results = [];
$total_lga_results = [];
$submitted = false;

// Handle Polling Unit Results (Question 1)
if (isset($_POST['view_polling_unit'])) {
    $polling_unit_id = intval($_POST['polling_unit_id']);
    if ($polling_unit_id > 0) {
        $sql = "SELECT party_abbreviation, party_score 
                FROM announced_pu_results 
                WHERE polling_unit_uniqueid = $polling_unit_id";
        $polling_unit_results = $conn->query($sql);
    }
}

// Handle LGA Summed Results (Question 2)
if (isset($_POST['view_lga'])) {
    $lga_id = intval($_POST['lga_id']);
    if ($lga_id > 0) {
        $sql = "SELECT party_abbreviation, SUM(party_score) AS total_score 
                FROM announced_pu_results 
                JOIN polling_unit ON announced_pu_results.polling_unit_uniqueid = polling_unit.uniqueid 
                WHERE polling_unit.lga_id = $lga_id 
                GROUP BY party_abbreviation";
        $total_lga_results = $conn->query($sql);
    }
}

// Handle New Polling Unit Results Submission (Question 3)
if (isset($_POST['submit_results'])) {
    $polling_unit_id = intval($_POST['new_polling_unit_id']);

    if ($polling_unit_id > 0 && isset($_POST['party_scores'])) {
        foreach ($_POST['party_scores'] as $party => $score) {
            $party = $conn->real_escape_string($party);
            $score = intval($score);
            $conn->query("INSERT INTO announced_pu_results 
                          (polling_unit_uniqueid, party_abbreviation, party_score) 
                          VALUES ($polling_unit_id, '$party', $score)");
        }
        $submitted = true;
    }
}

// Fetch LGAs for dropdown
$lga_sql = "SELECT lga_id, lga_name FROM lga";
$lga_result = $conn->query($lga_sql);

// Fetch parties for dynamic input
$party_sql = "SELECT partyid, partyname, partyabbreviation FROM party";
$party_result = $conn->query($party_sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Polling System</title>
</head>
<body>

<h1>Polling System</h1>

<!-- Question 1: Polling Unit Results -->
<h2>View Polling Unit Results</h2>
<form method="POST">
    <label>Enter Polling Unit ID:</label>
    <input type="number" name="polling_unit_id" required>
    <input type="submit" name="view_polling_unit" value="Get Results">
</form>

<?php if ($polling_unit_results && $polling_unit_results->num_rows > 0) { ?>
    <h3>Results for Polling Unit ID: <?php echo $polling_unit_id; ?></h3>
    <table border="1">
        <tr><th>Party</th><th>Score</th></tr>
        <?php while ($row = $polling_unit_results->fetch_assoc()) { ?>
            <tr><td><?php echo $row['party_abbreviation']; ?></td><td><?php echo $row['party_score']; ?></td></tr>
        <?php } ?>
    </table>
<?php } ?>

<!-- Question 2: Summed LGA Results -->
<h2>View Total Results for an LGA</h2>
<form method="POST">
    <label>Select LGA:</label>
    <select name="lga_id" required>
        <option value="">Select LGA</option>
        <?php while ($row = $lga_result->fetch_assoc()) { ?>
            <option value="<?php echo $row['lga_id']; ?>"><?php echo $row['lga_name']; ?></option>
        <?php } ?>
    </select>
    <input type="submit" name="view_lga" value="Get LGA Results">
</form>

<?php if ($total_lga_results && $total_lga_results->num_rows > 0) { ?>
    <h3>Total Results for LGA</h3>
    <table border="1">
        <tr><th>Party</th><th>Total Score</th></tr>
        <?php while ($row = $total_lga_results->fetch_assoc()) { ?>
            <tr><td><?php echo $row['party_abbreviation']; ?></td><td><?php echo $row['total_score']; ?></td></tr>
        <?php } ?>
    </table>
<?php } ?>

<!-- Question 3: Add New Polling Unit Results -->
<h2>Add New Polling Unit Results</h2>
<form method="POST">
    <label>Polling Unit ID:</label>
    <input type="number" name="new_polling_unit_id" required><br><br>

    <?php if ($party_result && $party_result->num_rows > 0) { ?>
        <?php while ($row = $party_result->fetch_assoc()) { ?>
            <label><?php echo $row['partyname']; ?> (<?php echo $row['partyabbreviation']; ?>) Score:</label>
            <input type="number" name="party_scores[<?php echo $row['partyabbreviation']; ?>]" value="0" required><br>
        <?php } ?>
    <?php } ?>

    <br>
    <input type="submit" name="submit_results" value="Submit Results">
</form>

<?php if ($submitted) { echo "<p>New results submitted successfully!</p>"; } ?>

</body>
</html>

<?php
$conn->close();
?>
