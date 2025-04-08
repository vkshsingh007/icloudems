<?php

$config = require('./config.php');
require './Database.php';
$db = new Database($config['database']);

$conn = new mysqli($config['database']['host'], 'root', '', $config['database']['dbname']);
$conn->options(MYSQLI_OPT_LOCAL_INFILE, true);;
// At the start of your script
ini_set('memory_limit', '1024M'); // Set to 1GB (use cautiously)

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

    // Better encoding detection and conversion
    $encodings_to_try = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'];
    $detected_encoding = mb_detect_encoding($content, $encodings_to_try, true);

    if ($detected_encoding) {
        $utf8_content = mb_convert_encoding($content, 'UTF-8', $detected_encoding);
    } else {
        // Fallback method when detection fails
        $utf8_content = iconv('UTF-8', 'UTF-8//IGNORE', $content);
    }

    file_put_contents($filename, $utf8_content);

    // Create temporary table if not exists
    $table = 'temp_table';

    $createTable = "CREATE TABLE `temp_table` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`sr` INT(10) NOT NULL,
	`date` DATE NOT NULL,
	`academic` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`session` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`alloted_category` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`voucher_type` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb4_0900_ai_ci',
	`voucher_no` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb4_0900_ai_ci',
	`roll_no` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb4_0900_ai_ci',
	`admn_no_unique_id` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb4_0900_ai_ci',
	`status` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`fee_status` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`faculty` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`program` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`department` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb4_0900_ai_ci',
	`batch` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`receipt_no` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`fee_head` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb4_0900_ai_ci',
	`due_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`paid_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`concession_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`scholarship_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`reverse_concession_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`write_off_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`adjusted_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`refund_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`fund_tranCfer_amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
	`remarks` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
	PRIMARY KEY (`id`, `admn_no_unique_id`) USING BTREE,
	INDEX `id` (`id`) USING BTREE,
	INDEX `sr` (`sr`) USING BTREE,
	INDEX `admn_no_unique_id` (`admn_no_unique_id`) USING BTREE,
	INDEX `roll_no` (`roll_no`) USING BTREE
    )
    COLLATE='utf8mb4_0900_ai_ci'
    ENGINE=InnoDB
    AUTO_INCREMENT=917491";

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
    // $query = "LOAD DATA LOCAL INFILE '" . $conn->real_escape_string($filename) . "' 
    //             INTO TABLE $table
    //             FIELDS TERMINATED BY ',' 
    //             OPTIONALLY ENCLOSED BY '\"' 
    //             LINES TERMINATED BY '\\r\\n'
    //             IGNORE 6 LINES
    //             (sr, @date_var,academic,session,alloted_category,voucher_type,voucher_no,roll_no,admn_no_unique_id,status,fee_status,faculty,program,department,batch,receipt_no,fee_head,due_amount,paid_amount,concession_amount,scholarship_amount,reverse_concession_amount,write_off_amount,adjusted_amount,refund_amount,fund_tranCfer_amount,remarks)
    //             SET date = STR_TO_DATE(@date_var, '%d-%m-%Y')" $conn->query($query);

    if (true) {

        $branchs = $conn->query("SELECT DISTINCT(t.faculty) FROM temp_table t WHERE t.faculty != ''");
        $fee_categorys = $conn->query("SELECT DISTINCT(t.fee_status) FROM temp_table t WHERE t.fee_status != ''");
        $fee_collection_types = ['academic', 'academicmisc', 'hostel', 'hostelmisc', 'transport', 'transportmisc'];
        $fee_heads = $conn->query("SELECT DISTINCT(t.fee_head) FROM temp_table t");

        $entry_modes = [['entry_modename' => 'due', 'crdr' => 'D', 'entrymodeno' => 0], ['entry_modename' => 'REVDUE', 'crdr' => 'C', 'entrymodeno' => 12]];

        $modules = [['module_name' => 'academic', 'module_id' => 1]];

        while ($branch = $branchs->fetch_assoc()) {
            // Insert into branches table
            $stmt = $conn->prepare("INSERT INTO `branches` (`branch_name`) VALUES (?)");
            $stmt->bind_param("s", $branch['faculty']);
            $stmt->execute();

            $br_id = $conn->insert_id; // Get last inserted ID from branches
            $stmt->close();

            // Loop through fee categories and insert them
            $fee_categorys->data_seek(0); // Reset result pointer to reuse the result set
            while ($fee_category = $fee_categorys->fetch_assoc()) {
                $stmt2 = $conn->prepare("INSERT INTO `fee_category` (`fee_category`, `br_id`) VALUES (?, ?)");
                $stmt2->bind_param("si", $fee_category['fee_status'], $br_id);
                $stmt2->execute();
                $stmt2->close();
            }

            foreach ($fee_collection_types as $type) {
                $stmt2 = $conn->prepare("INSERT INTO `fee_collection_type` (`collection_head`, `collection_desc`, `br_id`) VALUES (?,?,?)");
                $stmt2->bind_param("ssi", $type, $type, $br_id);
                $stmt2->execute();
                $stmt2->close();
            }

            foreach ($fee_collection_types as $type) {
                $stmt2 = $conn->prepare("INSERT INTO `fee_collection_type` (`collection_head`, `collection_desc`, `br_id`) VALUES (?,?,?)");
                $stmt2->bind_param("ssi", $type, $type, $br_id);
                $stmt2->execute();
                $stmt2->close();
            }

            $sq = 0;
            foreach ($fee_heads as $fee_head) {
                ++$sq;

                $fee_category = 1;
                $f_name = $fee_head['fee_head'];
                $collection_id = 1;
                $seq_id = $sq;
                $fee_type_ledger = $fee_head['fee_head'];
                $fee_headtype = 1;

                $stmt2 = $conn->prepare("INSERT INTO `fee_types` (`fee_category`, `f_name`, `collection_id`, `br_id`, `seq_id`, `fee_type_ledger`, `fee_headtype`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isiiisi", $fee_category, $f_name, $collection_id, $br_id, $seq_id, $fee_type_ledger, $fee_headtype);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        foreach ($entry_modes as $entry_mode) {
            $stmt2 = $conn->prepare("INSERT INTO `entry_mode` (`entry_modename`, `crdr`, `entrymodeno`) VALUES (?,?,?)");
            $stmt2->bind_param("ssi", $entry_mode['entry_modename'], $entry_mode['crdr'], $entry_mode['entrymodeno']);
            $stmt2->execute();
            $stmt2->close();
        }

        foreach ($modules as $module) {
            $stmt2 = $conn->prepare("INSERT INTO `module` (`module_name`, `module_id`) VALUES (?,?)");
            $stmt2->bind_param("si", $module['module_name'], $module['module_id']);
            $stmt2->execute();
            $stmt2->close();
        }


//         SELECT SUM(t.due_amount + t.write_off_amount) AS `amount`, t.admn_no_unique_id AS 'admn_no',t.`date` AS 'trans_date',t.voucher_no,b.id AS 'branch_id',GROUP_CONCAT(t.sr) AS `row`
// FROM temp_table t 
// JOIN branches b ON t.faculty = b.branch_name
// JOIN entry_mode e ON LOWER(e.entry_modename)=LOWER(t.remarks)
// WHERE t.faculty !='' 
// GROUP BY t.admn_no_unique_id,t.voucher_no


        echo "Successfully imported " . $conn->affected_rows . " records.";
    } else {
        echo "Error: " . $conn->error;
    }

    // Delete the uploaded file after import
    unlink($filename);
}
$conn->close();
