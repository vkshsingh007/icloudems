<?php

$config = require('./config.php');

$conn = new mysqli($config['database']['host'], 'root', '', $config['database']['dbname']);
$conn->options(MYSQLI_OPT_LOCAL_INFILE, true);


if (isset($_FILES['csv_file'])) {
    // Check for upload errors
    if ($_FILES['csv_file']['error'] != UPLOAD_ERR_OK) {
        die("Upload error: " . $_FILES['csv_file']['error']);
    }

    // Verify file extension
    $ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);

    if (strtolower($ext) != 'csv') {
        die("Error: Only CSV files are allowed.");
    }

    // Move uploaded file to permanent location
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = $uploadDir . basename($_FILES['csv_file']['name']);
    if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $filename)) {
        die("Error moving uploaded file.");
    }

    $content = file_get_contents($filename);
    $utf8_content = mb_convert_encoding($content, 'UTF-8', 'auto');
    file_put_contents($filename, $utf8_content);

    // Create temporary table if not exists
    $table = 'temp_table';

    $createTable = "CREATE TABLE `temp_table` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`sr` INT NOT NULL,
	`date` DATE NOT NULL,
	`academic` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`session` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`alloted_category` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`voucher_type` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`voucher_no` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`roll_no` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`admn_no_unique_id` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`status` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fee_status` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`faculty` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`program` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`department` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`batch` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`receipt_no` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`fee_head` TEXT NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`due_amount` INT NOT NULL,
	`paid_amount` INT NOT NULL,
	`concession_amount` INT NOT NULL,
	`scholarship_amount` INT NOT NULL,
	`reverse_concession_amount` INT NOT NULL,
	`write_off_amount` INT NOT NULL,
	`adjusted_amount` INT NOT NULL,
	`refund_amount` INT NOT NULL,
	`fund_tranCfer_amount` INT NOT NULL,
	`remarks` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
	PRIMARY KEY (`id`) USING BTREE
        )
        COLLATE='utf8mb4_0900_ai_ci'
        ENGINE=InnoDB";

    // First check if the table exists
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");

    if ($result === false) {
        throw new Exception("Error checking for table existence: " . $conn->error);
    }

    // If table doesn't exist, create it
    if ($result->num_rows === 0) {
        $createResult = $conn->query($createTable);

        if ($createResult === false) {
            throw new Exception("Failed to create table '{$table}': " . $conn->error);
        }
    }
    // Disable keys for faster import
    $conn->query("ALTER TABLE $table DISABLE KEYS");

    // Prepare and execute LOAD DATA INFILE command
    $query = "LOAD DATA LOCAL INFILE '" . $conn->real_escape_string($filename) . "' 
                INTO TABLE $table
                CHARACTER SET utf8mb4
                FIELDS TERMINATED BY ',' 
                OPTIONALLY ENCLOSED BY '\"' 
                LINES TERMINATED BY '\\r\\n'
                IGNORE 6 LINES
                (sr, @date_var,academic,session,alloted_category,voucher_type,voucher_no,roll_no,admn_no_unique_id,status,fee_status,faculty,program,department,batch,receipt_no,fee_head,due_amount,paid_amount,concession_amount,scholarship_amount,reverse_concession_amount,write_off_amount,adjusted_amount,refund_amount,fund_tranCfer_amount,remarks)
                SET date = STR_TO_DATE(@date_var, '%d-%m-%Y')";

    if ($conn->query($query)) {
        echo "Successfully imported " . $conn->affected_rows . " records.";
    } else {
        echo "Error: " . $conn->error;
    }

    // Delete the uploaded file after import
    unlink($filename);
}
$conn->close();
