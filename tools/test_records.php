<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/IHIS/includes/db_connect.php';

// Disable time limit for script execution
set_time_limit(0);

// Configuration
$batch_size = 10; // Number of records per batch
$total_records = 50; // Total records to generate
$used_phones = []; // Track used phone numbers

// Function to generate unique phone numbers
function generate_unique_phone() {
    global $used_phones;
    $max_phone = 2147483647; // MySQL INT maximum
    $min_phone = 0000000000; // Start of 555 area code numbers
    
    do {
        $phone = mt_rand($min_phone, $max_phone);
    } while (in_array($phone, $used_phones));
    
    $used_phones[] = $phone;
    return $phone;
}

function generate_random_date($start_date, $end_date) {
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $val = rand($min, $max);
    return date('Y-m-d', $val);
}

function generate_random_time() {
    return sprintf("%02d:%02d:00", rand(8, 18), rand(0, 59));
}

try {
    // Set higher timeout for transactions
    $conn->query("SET innodb_lock_wait_timeout = 120");
    
    echo "<h2>Generating Test Data</h2>";
    echo "<pre>";

    // 1. Create Credentials (Creds) - in batches
    $creds = [];
    $first_names = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth'];
    $last_names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Garcia', 'Rodriguez', 'Wilson'];
    $genders = ['M', 'F'];
    $pc_types = ['DO', 'PA', 'ST'];
    
    $conn->begin_transaction();
    for ($i = 1; $i <= $total_records; $i++) {
        $fname = $first_names[array_rand($first_names)];
        $lname = $last_names[array_rand($last_names)];
        $gender = $genders[array_rand($genders)];
        $dob = generate_random_date('1950-01-01', '2000-12-31');
        $doa = generate_random_date('2010-01-01', '2023-12-31');
        $phone = generate_unique_phone();
        $address = rand(100, 999) . ' ' . $last_names[array_rand($last_names)] . ' St';
        $pc_type = $pc_types[array_rand($pc_types)];
        $sec_code = $pc_type . '_' . substr(md5(uniqid()), 0, 7);
        
        $stmt = $conn->prepare("INSERT INTO Creds (PC_Type, FName, LName, Gender, DOB, DOA, Phone, Address, Sec_Code) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssiss", $pc_type, $fname, $lname, $gender, $dob, $doa, $phone, $address, $sec_code);
        $stmt->execute();
        $creds[$i] = $conn->insert_id;
        
        echo "Created Credential ID: {$creds[$i]} for $fname $lname ($pc_type)\n";
        
        // Commit in batches
        if ($i % $batch_size === 0) {
            $conn->commit();
            echo "--> Committed batch " . ($i/$batch_size) . "\n";
            if ($i < $total_records) {
                $conn->begin_transaction();
            }
        }
    }
    // Final commit if there's remaining records
    if ($total_records % $batch_size !== 0) {
        $conn->commit();
    }

    // 2. Create Doctors - single batch
    $doctors = [];
    $specialties = ['Cardiology', 'Neurology', 'Pediatrics', 'Oncology', 'Surgery', 'Psychiatry', 'Radiology', 'Dermatology'];
    
    $conn->begin_transaction();
    for ($i = 1; $i <= 10; $i++) {
        $specialty = $specialties[array_rand($specialties)];
        
        $stmt = $conn->prepare("INSERT INTO Doctors (Speciality, CR_ID) VALUES (?, ?)");
        $stmt->bind_param("si", $specialty, $creds[$i]);
        $stmt->execute();
        $doctors[$i] = $conn->insert_id;
        
        echo "Created Doctor ID: {$doctors[$i]} with specialty $specialty\n";
    }
    $conn->commit();

    // 3. Create Staff - single batch
    $staff = [];
    $titles = ['Nurse', 'Receptionist', 'Technician', 'Administrator', 'Therapist'];
    
    $conn->begin_transaction();
    for ($i = 11; $i <= 20; $i++) {
        $title = $titles[array_rand($titles)];
        
        $stmt = $conn->prepare("INSERT INTO Staffs (Title, CR_ID) VALUES (?, ?)");
        $stmt->bind_param("si", $title, $creds[$i]);
        $stmt->execute();
        $staff[$i-10] = $conn->insert_id;
        
        echo "Created Staff ID: {$staff[$i-10]} with title $title\n";
    }
    $conn->commit();

    // 4. Create Rooms - single batch
    $rooms = [];
    $conn->begin_transaction();
    for ($i = 1; $i <= 20; $i++) {
        $ro_num = 100 + $i;
        $bed_num = rand(1, 4);
        $is_occupied = rand(0, 1);
        
        $stmt = $conn->prepare("INSERT INTO Rooms (Ro_Num, Bed_num, Is_Occupied) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $ro_num, $bed_num, $is_occupied);
        $stmt->execute();
        $rooms[$i] = $conn->insert_id;
        
        echo "Created Room ID: {$rooms[$i]} (Room $ro_num with $bed_num beds)\n";
    }
    $conn->commit();

    // 5. Create Patients - in batches
    $patients = [];
    $med_history_options = ['None', 'Hypertension', 'Diabetes', 'Asthma', 'Heart Disease', 'Allergies', 'Arthritis', 'Cancer', 'Depression', 'Anxiety'];
    
    $conn->begin_transaction();
    for ($i = 21; $i <= $total_records; $i++) {
        $med_history = $med_history_options[array_rand($med_history_options)];
        $is_active = rand(0, 1);
        $st_id = $staff[array_rand($staff)];
        $ro_id = $rooms[array_rand($rooms)];
        
        $stmt = $conn->prepare("INSERT INTO Patients (Med_History, IS_Active, ST_ID, RO_ID, CR_ID) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiii", $med_history, $is_active, $st_id, $ro_id, $creds[$i]);
        $stmt->execute();
        $patients[$i-20] = $conn->insert_id;
        
        echo "Created Patient ID: {$patients[$i-20]} with $med_history\n";
        
        // Commit in batches
        if (($i-20) % $batch_size === 0) {
            $conn->commit();
            echo "--> Committed patient batch " . (($i-20)/$batch_size) . "\n";
            if (($i-20) < ($total_records-20)) {
                $conn->begin_transaction();
            }
        }
    }
    if (($total_records-20) % $batch_size !== 0) {
        $conn->commit();
    }

    // 6. Create Encounters - in batches
    $encounters = [];
    $conn->begin_transaction();
    for ($i = 1; $i <= 50; $i++) {
        $pa_id = $patients[array_rand($patients)];
        $dr_id = $doctors[array_rand($doctors)];
        $date = generate_random_date('2020-01-01', '2023-12-31');
        $time = generate_random_time();
        
        $stmt = $conn->prepare("INSERT INTO Encounters (PA_ID, DR_ID, Date, Time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $pa_id, $dr_id, $date, $time);
        $stmt->execute();
        $encounters[$i] = $conn->insert_id;
        
        echo "Created Encounter ID: {$encounters[$i]} for patient $pa_id with doctor $dr_id on $date at $time\n";
        
        if ($i % $batch_size === 0) {
            $conn->commit();
            echo "--> Committed encounter batch " . ($i/$batch_size) . "\n";
            if ($i < 50) {
                $conn->begin_transaction();
            }
        }
    }
    if (50 % $batch_size !== 0) {
        $conn->commit();
    }

    // 7. Create MD_Records - single batch
    $md_records = [];
    $diagnoses = ['Common cold', 'Influenza', 'Hypertension', 'Type 2 Diabetes', 'Migraine', 'Bronchitis', 'Pneumonia', 'UTI', 'Strep throat', 'COVID-19'];
    $summaries = ['Patient presented with typical symptoms', 'Follow-up visit', 'Initial consultation', 'Emergency visit', 'Routine checkup'];
    
    $conn->begin_transaction();
    for ($i = 1; $i <= 40; $i++) {
        $en_id = $encounters[array_rand($encounters)];
        $summary = $summaries[array_rand($summaries)];
        $diagnosis = $diagnoses[array_rand($diagnoses)];
        
        $stmt = $conn->prepare("INSERT INTO MD_Records (EN_ID, Summery, Diagnosis) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $en_id, $summary, $diagnosis);
        $stmt->execute();
        $md_records[$i] = $conn->insert_id;
        
        echo "Created MD Record ID: {$md_records[$i]} for encounter $en_id with diagnosis $diagnosis\n";
    }
    $conn->commit();

    // 8. Create Prescriptions - single batch
    $prescriptions = [];
    $drugs = ['Amoxicillin', 'Ibuprofen', 'Lisinopril', 'Metformin', 'Atorvastatin', 'Albuterol', 'Omeprazole', 'Losartan', 'Simvastatin', 'Azithromycin'];
    $dosages = ['500mg twice daily', '200mg as needed', '10mg once daily', '20mg at bedtime', '1 tablet daily', '2 puffs every 4 hours'];
    
    $conn->begin_transaction();
    for ($i = 1; $i <= 30; $i++) {
        $mdr_id = $md_records[array_rand($md_records)];
        $drug = $drugs[array_rand($drugs)];
        $dosage = $dosages[array_rand($dosages)];
        
        $stmt = $conn->prepare("INSERT INTO Prescriptions (MDR_ID, Drug, Dosage) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $mdr_id, $drug, $dosage);
        $stmt->execute();
        $prescriptions[$i] = $conn->insert_id;
        
        echo "Created Prescription ID: {$prescriptions[$i]} for MD record $mdr_id - $drug $dosage\n";
    }
    $conn->commit();

    echo "\nAll test data successfully inserted!\n";
    echo "</pre>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<pre>Error: " . $e->getMessage() . "</pre>";
}
?>