<?php

$config = require('./config.php');
require './Database.php';
$db = new Database($config['database']);

$conn = new mysqli($config['database']['host'], 'root', '', $config['database']['dbname']);
$conn->options(MYSQLI_OPT_LOCAL_INFILE, true);
set_time_limit(0);
ini_set('memory_limit', '2048M');

if (isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] != UPLOAD_ERR_OK) {
        die("Upload error: " . $_FILES['csv_file']['error']);
    }

    $ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);

    if (strtolower($ext) != 'csv') {
        die("Error: Only CSV files are allowed.");
    }

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

    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");

    if ($result === false) {
        throw new Exception("Error checking for table existence: " . $conn->error);
    }

    if ($result->num_rows === 0) {
        $createResult = $conn->query($createTable);

        if ($createResult === false) {
            throw new Exception("Failed to create table '{$table}': " . $conn->error);
        }
    }
    $conn->query("ALTER TABLE $table DISABLE KEYS");

    $startTime = microtime(true);

    $query = "LOAD DATA LOCAL INFILE '" . $conn->real_escape_string($filename) . "' 
                INTO TABLE $table
                FIELDS TERMINATED BY ',' 
                OPTIONALLY ENCLOSED BY '\"' 
                LINES TERMINATED BY '\\r\\n'
                IGNORE 6 LINES
                (sr, @date_var,academic,session,alloted_category,voucher_type,voucher_no,roll_no,admn_no_unique_id,status,fee_status,faculty,program,department,batch,receipt_no,fee_head,due_amount,paid_amount,concession_amount,scholarship_amount,reverse_concession_amount,write_off_amount,adjusted_amount,refund_amount,fund_tranCfer_amount,remarks)
                SET date = STR_TO_DATE(@date_var, '%d-%m-%Y')";

    if ($conn->query($query)) {
        // Disable ONLY_FULL_GROUP_BY mode if enabled
        $conn->query("SET sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

        // Fetch required data
        $branchs = $conn->query("SELECT DISTINCT(t.faculty) FROM temp_table t WHERE t.faculty != ''");
        $fee_categorys = $conn->query("SELECT DISTINCT(t.fee_status) FROM temp_table t WHERE t.fee_status != ''");
        $fee_heads = $conn->query("SELECT DISTINCT(t.fee_head) FROM temp_table t");

        $fee_collection_types = ['academic', 'academicmisc', 'hostel', 'hostelmisc', 'transport', 'transportmisc'];
        $entry_modes = [
            ['entry_modename' => 'due', 'crdr' => 'D', 'entrymodeno' => 0],
            ['entry_modename' => 'REVDUE', 'crdr' => 'C', 'entrymodeno' => 12]
        ];
        $modules = [
            ['module_name' => 'academic', 'module_id' => 1]
        ];

        while ($branch = $branchs->fetch_assoc()) {
            $branch_name = $branch['faculty'];

            // Insert into branches
            $stmt = $conn->prepare("INSERT INTO `branches` (`branch_name`) VALUES (?)");
            $stmt->bind_param("s", $branch_name);
            $stmt->execute();
            $br_id = $conn->insert_id;
            $stmt->close();

            // Insert fee categories for this branch
            $fee_categorys->data_seek(0);
            while ($fee_category = $fee_categorys->fetch_assoc()) {
                $category = $fee_category['fee_status'];
                $stmt2 = $conn->prepare("INSERT INTO `fee_category` (`fee_category`, `br_id`) VALUES (?, ?)");
                $stmt2->bind_param("si", $category, $br_id);
                $stmt2->execute();
                $stmt2->close();
            }

            // Insert fee collection types (once per branch)
            foreach ($fee_collection_types as $type) {
                $stmt3 = $conn->prepare("INSERT INTO `fee_collection_type` (`collection_head`, `collection_desc`, `br_id`) VALUES (?, ?, ?)");
                $stmt3->bind_param("ssi", $type, $type, $br_id);
                $stmt3->execute();
                $stmt3->close();
            }

            // Insert fee heads for this branch
            $fee_heads->data_seek(0);
            $seq_id = 0;

            while ($fee_head = $fee_heads->fetch_assoc()) {
                $seq_id++;
                $f_name = $fee_head['fee_head'];
                $fee_category_id = 1;
                $collection_id = 1;
                $fee_type_ledger = $f_name;
                $fee_headtype = 1;

                $stmt4 = $conn->prepare("INSERT INTO `fee_types` (`fee_category`, `f_name`, `collection_id`, `br_id`, `seq_id`, `fee_type_ledger`, `fee_headtype`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt4->bind_param("isiiisi", $fee_category_id, $f_name, $collection_id, $br_id, $seq_id, $fee_type_ledger, $fee_headtype);
                $stmt4->execute();
                $stmt4->close();
            }
        }

        // Insert entry modes (common)
        foreach ($entry_modes as $entry_mode) {
            $stmt5 = $conn->prepare("INSERT INTO `entry_mode` (`entry_modename`, `crdr`, `entrymodeno`) VALUES (?, ?, ?)");
            $stmt5->bind_param("ssi", $entry_mode['entry_modename'], $entry_mode['crdr'], $entry_mode['entrymodeno']);
            $stmt5->execute();
            $stmt5->close();
        }

        // Insert modules (common)
        foreach ($modules as $module) {
            $stmt6 = $conn->prepare("INSERT INTO `module` (`module_name`, `module_id`) VALUES (?, ?)");
            $stmt6->bind_param("si", $module['module_name'], $module['module_id']);
            $stmt6->execute();
            $stmt6->close();
        }


        // financial_data -----------------------

        // Prepare the connection for batched operations
        $conn->autocommit(FALSE);

        // Get all financial transaction data at once
        $finencial_trans_data = $conn->query("SELECT SUM(t.due_amount + t.write_off_amount) AS `amount`, 
    t.admn_no_unique_id AS 'admn_no', t.`date` AS 'trans_date',
    t.voucher_no, e.crdr, b.id AS 'branch_id', GROUP_CONCAT(t.sr) AS `row`, t.`session`
    FROM temp_table t
    JOIN branches b ON t.faculty = b.branch_name
    JOIN entry_mode e ON LOWER(e.entry_modename) = LOWER(t.voucher_type)
    WHERE t.faculty != ''
    GROUP BY t.voucher_no, t.admn_no_unique_id");

        // Prepare statements for repeated use
        $stmt_trans = $conn->prepare("INSERT INTO `financial_trans` 
    (`module_id`, `trans_id`, `admn_no`, `amount`, `crdr`, `trans_date`, `acad_year`, `entry_mode`, `voucher_no`, `br_id`) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt_details = $conn->prepare("INSERT INTO `financial__trans_details` 
    (`financial_trans_id`, `module_id`, `amount`, `head_id`, `crdr`, `brid`, `head_name`) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

        // Batch size for details inserts
        $batch_size = 1000;
        $details_batch = [];
        $batch_count = 0;

        while ($row = $finencial_trans_data->fetch_assoc()) {
            $financial_trans = rand(100000, 999999);
            $admn_no = $row['admn_no'];
            $amount = (float)$row['amount'];
            $crdr = $row['crdr'];
            $trans_date = $row['trans_date'];
            $acad_year = $row['session'];
            $entry_mode = $row['crdr'] == 'C' ? 12 : 0;
            $voucher_no = $row['voucher_no'];
            $branch_id = (int)$row['branch_id'];
            $module_id = 1;
            $sr = $row['row']; // comma-separated list of sr IDs

            // Insert into financial_trans using prepared statement
            $stmt_trans->bind_param(
                "iisdssisis",
                $module_id,
                $financial_trans,
                $admn_no,
                $amount,
                $crdr,
                $trans_date,
                $acad_year,
                $entry_mode,
                $voucher_no,
                $branch_id
            );
            $stmt_trans->execute();
            $lastInsertId = $conn->insert_id;

            // Process details in one query instead of looping
            $sr_array = array_map('intval', explode(',', $sr));
            $sr_list = implode(',', $sr_array);

            $sql = "SELECT SUM(t.due_amount + t.write_off_amount) AS amount, 
           b.branch_name, b.id AS branch_id, f.id AS head_id, f.f_name AS head_name
           FROM temp_table t
           JOIN branches b ON t.faculty = b.branch_name
           JOIN fee_types f ON f.f_name = t.fee_head AND f.br_id = b.id
           WHERE t.sr IN ($sr_list)
           GROUP BY t.sr";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row2 = $result->fetch_assoc()) {
                    $child_amount = (float)$row2['amount'];
                    $child_head_id = (int)$row2['head_id'];
                    $child_branch_id = (int)$row2['branch_id'];
                    $child_head_name = $row2['head_name'];

                    // Add to batch for details insertion
                    $stmt_details->bind_param(
                        "iidisss",
                        $lastInsertId,
                        $module_id,
                        $child_amount,
                        $child_head_id,
                        $crdr,
                        $child_branch_id,
                        $child_head_name
                    );
                    $stmt_details->execute();

                    $batch_count++;

                    // Commit every batch_size records
                    if ($batch_count % $batch_size == 0) {
                        $conn->commit();
                    }
                }
            }
        }

        // Final commit for any remaining transactions
        $conn->commit();

        // Close prepared statements
        $stmt_trans->close();
        $stmt_details->close();

        // Reset autocommit to true
        $conn->autocommit(TRUE);



        $endTime = microtime(true); // End time
        $executionTime = $endTime - $startTime;
        echo "Execution Time: " . round($executionTime, 4) . " seconds";

        // echo "Successfully imported " . $conn->affected_rows . " records.";
    } else {
        echo "Error: " . $conn->error;
    }

    // Delete the uploaded file after import
    unlink($filename);
}



$conn->close();
