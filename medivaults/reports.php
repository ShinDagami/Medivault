<?php
session_start();
include 'php/config.php'; 


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$avatarInitials = strtoupper(substr($_SESSION['username'], 0, 2));
$currentPage = 'reports'; 


$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';


function fetchSingleValue($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("DB Error in fetchSingleValue: " . $e->getMessage());
        return 0;
    }
}




$date_filter_current = "";

$date_filter_previous = "";

switch ($period) {
    case 'daily':
        
        $date_filter_current = "DATE(created_at) = CURDATE()";
        $date_filter_previous = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'weekly':
        
        $date_filter_current = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $date_filter_previous = "YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
        break;
    case 'yearly':
        
        $date_filter_current = "YEAR(created_at) = YEAR(CURDATE())";
        $date_filter_previous = "YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))";
        break;
    case 'monthly':
    default:
        
        $date_filter_current = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        $date_filter_previous = "YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        break;
}


$totalPatients = $patients_last_period = 0;
$totalAppointments = $appointments_last_period = 0;
$bedOccupancyCount = 0;
$totalBeds = 43; 
$activeStaff = 56; 

if (isset($pdo)) {
    try {
        
        $totalPatients = fetchSingleValue($pdo, "SELECT COUNT(patient_id) FROM patients WHERE " . $date_filter_current);
        $patients_last_period = fetchSingleValue($pdo, "SELECT COUNT(patient_id) FROM patients WHERE " . $date_filter_previous);

        
        $totalAppointments = fetchSingleValue($pdo, "SELECT COUNT(appt_id) FROM appointments WHERE " . $date_filter_current);
        $appointments_last_period = fetchSingleValue($pdo, "SELECT COUNT(appt_id) FROM appointments WHERE " . $date_filter_previous);

        
        $bedOccupancyCount = fetchSingleValue($pdo, "SELECT COUNT(admission_id) FROM patient_admissions WHERE status = 'admitted' AND discharge_date IS NULL");
        
        
        
        $staff_last_period = 53;
        
    } catch (PDOException $e) {
        error_log("Failed to fetch top-level metrics: " . $e->getMessage());
    }
}


$patients_percent_change = $patients_last_period > 0 ? (($totalPatients - $patients_last_period) / $patients_last_period) * 100 : ($totalPatients > 0 ? 100 : 0);
$appointments_percent_change = $appointments_last_period > 0 ? (($totalAppointments - $appointments_last_period) / $appointments_last_period) * 100 : ($totalAppointments > 0 ? 100 : 0);
$bed_occupancy_percent = $totalBeds > 0 ? round(($bedOccupancyCount / $totalBeds) * 100) : 0;
$staff_percent_change = $staff_last_period > 0 ? (($activeStaff - $staff_last_period) / $staff_last_period) * 100 : ($activeStaff > 0 ? 100 : 0);



$admissions_trend_data = [];
$date_format = '%b'; 
$interval_months = 7; 

if ($period === 'yearly') {
    $date_format = '%Y'; 
    $interval_months = 5 * 12; 
} else if ($period === 'weekly' || $period === 'daily') {
    $date_format = '%a'; 
    $interval_months = 1; 
}

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, :date_format) AS label,
                COUNT(admission_id) AS count
            FROM patient_admissions
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :interval_months MONTH)
            GROUP BY label
            ORDER BY created_at
        ");
        $stmt->bindParam(':date_format', $date_format);
        $stmt->bindParam(':interval_months', $interval_months, PDO::PARAM_INT);
        $stmt->execute();
        $admissions_trend_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $admissions_json = json_encode($admissions_trend_data);
    } catch (PDOException $e) {
        error_log("Failed to fetch admissions trend: " . $e->getMessage());
        $admissions_json = '[]';
    }
}



$diagnoses_data = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                d.name AS diagnosis,
                COUNT(pr.diagnosis_id) AS count
            FROM patient_records pr
            JOIN diagnoses d ON pr.diagnosis_id = d.id
            WHERE pr.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) 
            GROUP BY d.name
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $raw_diagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_diagnoses = array_sum(array_column($raw_diagnoses, 'count'));
        $diagnoses_data = [];
        $other_count = 0;
        
        for ($i = 0; $i < count($raw_diagnoses); $i++) {
            if ($i < 4) { 
                $percent = $total_diagnoses > 0 ? round(($raw_diagnoses[$i]['count'] / $total_diagnoses) * 100) : 0;
                $diagnoses_data[] = ['diagnosis' => $raw_diagnoses[$i]['diagnosis'], 'percent' => $percent, 'value' => $raw_diagnoses[$i]['count']];
            } else {
                $other_count += $raw_diagnoses[$i]['count'];
            }
        }

        if ($other_count > 0) {
            $other_percent = $total_diagnoses > 0 ? round(($other_count / $total_diagnoses) * 100) : 0;
            $diagnoses_data[] = ['diagnosis' => 'Others', 'percent' => $other_percent, 'value' => $other_count];
        }

        $diagnoses_json = json_encode($diagnoses_data);
    } catch (PDOException $e) {
        error_log("Failed to fetch diagnoses data: " . $e->getMessage());
        $diagnoses_json = '[]'; 
    }
}



$medicines_data = [];
if (isset($pdo)) {
    try {
        
        $stmt = $pdo->prepare("
            SELECT 
                m.name AS name,
                SUM(p.quantity) AS units
            FROM prescriptions p
            JOIN medications m ON p.medication_id = m.id
            WHERE " . str_replace("created_at", "p.created_at", $date_filter_current) . "
            GROUP BY m.name
            ORDER BY units DESC
            LIMIT 5
        ");
        $stmt->execute();
        $medicines_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $medicines_json = json_encode($medicines_data);
    } catch (PDOException $e) {
        error_log("Failed to fetch medicines data: " . $e->getMessage());
        $medicines_json = '[]';
    }
}


$max_medicine_units = !empty($medicines_data) ? max(array_column($medicines_data, 'units')) : 1; 



$weekly_appt_data = [];
$weekly_appt_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

if (isset($pdo)) {
    try {
        
        $stmt = $pdo->prepare("
            SELECT 
                DAYOFWEEK(appointment_date) AS day_num,
                COUNT(appt_id) AS count
            FROM appointments
            WHERE YEARWEEK(appointment_date, 1) = YEARWEEK(CURDATE(), 1)
            GROUP BY day_num
        ");
        $stmt->execute();
        $raw_appts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        $appt_map = array_fill(1, 7, 0); 
        foreach ($raw_appts as $appt) {
            
            
            $day_index = ($appt['day_num'] == 1) ? 7 : ($appt['day_num'] - 1);
            $appt_map[$day_index] = $appt['count'];
        }
        
        $weekly_appt_data = array_values($appt_map);
        $weekly_appt_json = json_encode($weekly_appt_data);

    } catch (PDOException $e) {
        error_log("Failed to fetch weekly appointment data: " . $e->getMessage());
        $weekly_appt_json = json_encode([0, 0, 0, 0, 0, 0, 0]);
    }
}



function formatPercent($percent) {
    $sign = $percent >= 0 ? '+' : '';
    $color = $percent >= 0 ? 'green' : 'red';
    return '<span class="' . $color . '">' . $sign . round($percent) . '% from last period</span>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVault - Reports & Analytics</title>
<link rel="stylesheet" href="css/reports.css"> 
<link rel="stylesheet" href="css/main.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app">
    <?php include 'sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div class="search"><input placeholder="Search patients, appointments..." aria-label="Search"/></div>
            <div class="user-actions">
                <div class="notification-bell"><i class="fas fa-bell"></i></div>
                <div class="user-menu-container" id="userMenuContainer">
                    <div class="user-avatar-wrapper" role="button" tabindex="0">
                        <div class="avatar" id="userAvatar"><?= $avatarInitials ?></div>
                    </div>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <div class="account-info"><?= htmlspecialchars($_SESSION['username']) ?></div>
                        <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                        <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-title">
            <div class="title-group">
                <h2>Reports & Analytics</h2>
                <p>View insights and generate reports</p>
            </div>
            <div class="actions">
                <div class="dropdown-container">
                    <button class="btn btn-secondary dropdown-toggle" id="period-dropdown-btn">
                        <?= ucfirst($period) ?> <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu period-menu" id="period-dropdown-menu">
                        <a href="reports.php?period=daily" class="menu-item <?= $period == 'daily' ? 'selected' : '' ?>">Daily</a>
                        <a href="reports.php?period=weekly" class="menu-item <?= $period == 'weekly' ? 'selected' : '' ?>">Weekly</a>
                        <a href="reports.php?period=monthly" class="menu-item <?= $period == 'monthly' ? 'selected' : '' ?>">Monthly</a>
                        <a href="reports.php?period=yearly" class="menu-item <?= $period == 'yearly' ? 'selected' : '' ?>">Yearly</a>
                    </div>
                </div>
                <button class="btn btn-secondary"><i class="fas fa-file-pdf"></i> Export PDF</button>
                <button class="btn btn-secondary"><i class="fas fa-file-excel"></i> Export Excel</button>
                <button class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>

        <div class="reports-grid">
            <div class="metric-card">
                <div class="metric-title">Total Patients</div>
                <div class="metric-value"><?= number_format($totalPatients) ?></div>
                <div class="metric-change"><?= formatPercent($patients_percent_change) ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Appointments</div>
                <div class="metric-value"><?= number_format($totalAppointments) ?></div>
                <div class="metric-change"><?= formatPercent($appointments_percent_change) ?></div>
            </div>
            <div class="metric-card" style="grid-column: span 1;"> 
                <div class="metric-title">Bed Occupancy</div>
                <div class="metric-value"><?= $bed_occupancy_percent ?>%</div>
                <div class="metric-detail"><?= $bedOccupancyCount ?> of <?= $totalBeds ?> beds</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Active Staff</div>
                <div class="metric-value"><?= number_format($activeStaff) ?></div>
                <div class="metric-detail"><?= formatPercent($staff_percent_change) ?></div>
            </div>


            <div class="chart-panel trend-chart">
                <h3>Patient Admissions Trend</h3>
                <p class="muted"><?= ucfirst($period) ?> admission statistics</p>
                <canvas id="admissions-trend-chart"></canvas>
            </div>

            <div class="chart-panel diagnosis-chart">
                <h3>Most Common Diagnoses</h3>
                <p class="muted">Distribution of patient conditions (Last Year)</p>
                <canvas id="diagnoses-pie-chart"></canvas>
            </div>

            <div class="chart-panel weekly-trend-chart">
                <h3>Weekly Appointment Trends</h3>
                <p class="muted">Appointments per day this week</p>
                <canvas id="weekly-appointment-chart"></canvas>
            </div>

            <div class="chart-panel medicine-usage">
                <h3>Top Used Medicines</h3>
                <p class="muted">Medicine consumption <?= ucfirst($period) ?></p>
                <div class="medicine-list">
                    <?php if (!empty($medicines_data)): ?>
                        <?php foreach ($medicines_data as $medicine): ?>
                            <div class="medicine-item">
                                <span class="med-name"><?= htmlspecialchars($medicine['name']) ?></span>
                                <div class="med-bar-container">
                                    <div class="med-bar" style="width: <?= round(($medicine['units'] / $max_medicine_units) * 100) ?>%;"></div>
                                </div>
                                <span class="med-units"><?= number_format($medicine['units']) ?> units</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted" style="text-align: center;">No medicine usage data for this period.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div> </main> </div> <script src="js/main.js"></script> 
<script src="js/reports.js"></script> 
<script>

const period = '<?= $period ?>';
const admissionsData = <?= $admissions_json ?>;
const diagnosesData = <?= $diagnoses_json ?>;
const weeklyApptData = <?= $weekly_appt_json ?>; 
const weeklyApptLabels = <?= json_encode($weekly_appt_labels) ?>;


document.addEventListener('DOMContentLoaded', () => {

    
    function formatAdmissionsData(data, period) {
        if (data.length === 0) {
            return { labels: [], counts: [] };
        }
        
        let labels = data.map(d => d.label);
        let counts = data.map(d => d.count);

        if (period === 'monthly') {
            
            
        }
        
        return { labels, counts };
    }
    
    const formattedAdmissions = formatAdmissionsData(admissionsData, period);

    
    const admissionsCtx = document.getElementById('admissions-trend-chart').getContext('2d');
    new Chart(admissionsCtx, {
        type: 'bar',
        data: {
            labels: formattedAdmissions.labels,
            datasets: [{
                label: 'Admissions',
                data: formattedAdmissions.counts,
                backgroundColor: '#3b82f6', 
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });

    
    const diagnosesCtx = document.getElementById('diagnoses-pie-chart').getContext('2d');
    if (diagnosesData.length > 0) {
        new Chart(diagnosesCtx, {
            type: 'doughnut',
            data: {
                labels: diagnosesData.map(d => d.diagnosis + ' ' + d.percent + '%'),
                datasets: [{
                    data: diagnosesData.map(d => d.percent),
                    backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#6b7280'], 
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 10 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let dataPoint = diagnosesData[context.dataIndex];
                                return dataPoint.diagnosis + ': ' + dataPoint.value + ' cases (' + context.parsed + '%)';
                            }
                        }
                    }
                }
            }
        });
    } else {
         diagnosesCtx.font = "16px sans-serif";
         diagnosesCtx.fillStyle = "#6b7280";
         diagnosesCtx.textAlign = "center";
         diagnosesCtx.fillText("No diagnoses data available for the last year.", admissionsCtx.canvas.width / 2, admissionsCtx.canvas.height / 2);
    }

    
    const weeklyAppointmentsCtx = document.getElementById('weekly-appointment-chart').getContext('2d');
    
    new Chart(weeklyAppointmentsCtx, {
        type: 'line',
        data: {
            labels: weeklyApptLabels, 
            datasets: [{
                label: 'Appointments',
                data: weeklyApptData,
                borderColor: '#3b82f6',
                tension: 0.3,
                fill: false,
                pointBackgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: Math.max(...weeklyApptData, 50) } }, 
            plugins: { legend: { display: false } }
        }
    });
});
</script>
</body>
</html>