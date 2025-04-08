while ($row = $finencial_trans_data->fetch_assoc()) {
            echo "first";
            $financial_trans = rand(100000, 999999);
            $admn_no = $row['admn_no'];
            $amount = (float)$row['amount'];
            $crdr = $row['crdr'];
            $trans_date = $row['trans_date'];
            $acad_year = $row['session'];
            $entry_mode = 0;
            $voucher_no = $row['voucher_no'];
            $branch_id = (int)$row['branch_id'];
            $module_id = 1;
            $sr = $row['row']; // comma-separated list of sr IDs

            $stmt = $conn->prepare("INSERT INTO `financial_trans` 
                (`module_id`, `trans_id`, `admn_no`, `amount`, `crdr`, `trans_date`, `acad_year`, `entry_mode`, `voucher_no`, `br_id`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                echo "Prepare failed: " . $conn->error;
                continue;
            }

            $stmt->bind_param(
                "iisdsssisi",
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

            if (!$stmt->execute()) {
                echo "Main insert failed: " . $stmt->error;
                $stmt->close();
                continue;
            }

            $fi_id = $conn->insert_id;
            $stmt->close();

            // Clean and secure the sr list
            $sr_array = array_map('intval', explode(',', $sr));
            $sr_list = implode(',', $sr_array);

            $sql = "SELECT SUM(t.due_amount + t.write_off_amount) AS amount, 
                        b.branch_name,
                        b.id AS branch_id,
                        f.id AS head_id,
                        f.f_name AS head_name
                    FROM temp_table t
                    JOIN branches b ON t.faculty = b.branch_name
                    JOIN fee_types f ON f.f_name = t.fee_head AND f.br_id = b.id
                    WHERE t.sr IN ($sr_list)
                    GROUP BY t.sr";


            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                echo "if";
                while ($row2 = $result->fetch_assoc()) {
                    echo "while";
                    $insertQry = $conn->prepare("INSERT INTO `financial__trans_details` (`financial_trans_id`, `module_id`, `amount`, `head_id`, `crdr`, `brid`, `head_name`) VALUES (?,?,?,?,?,?,?)");

                    if (!$insertQry) {
                        echo "Detail prepare failed: " . $conn->error;
                        continue;
                    }

                    $child_amount = $row2['amount'];
                    $child_head_id = $row2['head_id'];
                    $child_branch_id = $row2['branch_id'];
                    $child_head_name = $row2['head_name'];

                    $insertQry->bind_param(
                        "iidisis",
                        $fi_id,
                        $module_id,
                        $child_amount,
                        $child_head_id,
                        $crdr,
                        $child_branch_id,
                        $child_head_name
                    );

                    if (!$insertQry->execute()) {
                        echo "Detail insert failed: " . $insertQry->error;
                    }

                    $insertQry->close();
                }
            } else {
                echo "Sub query error: " . $conn->error;
            }
        }
