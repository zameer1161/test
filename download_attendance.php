<?php
session_start();
require 'config.php'; // PDO connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT id, fullname, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("User not found.");

// Filters
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$subject_filter = $_GET['subject'] ?? null;
$format = $_GET['format'] ?? 'pdf'; // pdf, excel, csv

// Fetch attendance records
$sql = "SELECT date, subject, status, remarks FROM attendance WHERE student_id = ?";
$params = [$user_id];

if ($start_date) { $sql .= " AND date >= ?"; $params[] = $start_date; }
if ($end_date) { $sql .= " AND date <= ?"; $params[] = $end_date; }
if ($subject_filter && $subject_filter != 'all') { $sql .= " AND subject = ?"; $params[] = $subject_filter; }

$sql .= " ORDER BY date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total = count($records);
$present = $absent = $late = 0;

foreach ($records as $r) {
    if ($r['status'] == 'Present') $present++;
    elseif ($r['status'] == 'Absent') $absent++;
    elseif ($r['status'] == 'Late') $late++;
}

$rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
$stats = ['total'=>$total, 'present'=>$present, 'absent'=>$absent, 'late'=>$late];

// ===== REPORT FUNCTIONS =====

// PDF Report
function generatePDFReport($records, $stats, $rate, $user, $start_date, $end_date, $subject_filter) {
    require_once('TCPDF-main/tcpdf.php');

    $total = $stats['total'] ?? 0;
    $present = $stats['present'] ?? 0;
    $absent = $stats['absent'] ?? 0;
    $late = $stats['late'] ?? 0;
    $present_pct = $total > 0 ? round(($present/$total)*100,1) : 0;
    $absent_pct = $total > 0 ? round(($absent/$total)*100,1) : 0;
    $late_pct = $total > 0 ? round(($late/$total)*100,1) : 0;

    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','B',16);
    $pdf->Cell(0,10,'ATTENDANCE REPORT',0,1,'C');
    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0,10,'Generated on: '.date('F j, Y'),0,1,'C');
    $pdf->Ln(10);

    $pdf->SetFont('helvetica','B',14);
    $pdf->Cell(0,10,'Student Information',0,1);
    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0,8,'Name: '.$user['fullname'],0,1);
    $pdf->Cell(0,8,'Username: '.$user['username'],0,1);
    $pdf->Cell(0,8,'Report Period: '.($start_date?date('M j, Y',strtotime($start_date)):'All time').' to '.($end_date?date('M j, Y',strtotime($end_date)):'Present'),0,1);
    if($subject_filter && $subject_filter!='all') $pdf->Cell(0,8,'Subject: '.$subject_filter,0,1);
    $pdf->Ln(5);

    $pdf->SetFont('helvetica','B',14);
    $pdf->Cell(0,10,'Attendance Summary',0,1);
    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0,8,"Total Classes: $total",0,1);
    $pdf->Cell(0,8,"Present: $present ($present_pct%)",0,1);
    $pdf->Cell(0,8,"Absent: $absent ($absent_pct%)",0,1);
    $pdf->Cell(0,8,"Late: $late ($late_pct%)",0,1);
    $pdf->Cell(0,8,"Overall Attendance Rate: $rate%",0,1);
    $pdf->Ln(10);

    if(count($records)>0){
        $pdf->SetFont('helvetica','B',14);
        $pdf->Cell(0,10,'Detailed Attendance Records',0,1);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetFont('helvetica','B',10);
        $pdf->Cell(40,8,'Date',1,0,'C',1);
        $pdf->Cell(50,8,'Subject',1,0,'C',1);
        $pdf->Cell(30,8,'Status',1,0,'C',1);
        $pdf->Cell(70,8,'Remarks',1,1,'C',1);

        $pdf->SetFont('helvetica','',9);
        foreach($records as $r){
            $pdf->Cell(40,8,date('M j, Y',strtotime($r['date'])),1);
            $pdf->Cell(50,8,$r['subject'] ?? '-',1);

            if($r['status']=='Present') $pdf->SetTextColor(0,128,0);
            elseif($r['status']=='Absent') $pdf->SetTextColor(255,0,0);
            else $pdf->SetTextColor(255,165,0);

            $pdf->Cell(30,8,$r['status'],1,0,'C');
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell(70,8,$r['remarks'] ?? '-',1,1);
        }
    } else {
        $pdf->Cell(0,10,'No attendance records found.',0,1);
    }

    $pdf->Output('attendance_report_'.$user['username'].'_'.date('Ymd_His').'.pdf','D');
    exit;
}

// Excel Report
function generateExcelReport($records, $stats, $rate, $user, $start_date, $end_date, $subject_filter){
    $total = $stats['total'] ?? 0;
    $present = $stats['present'] ?? 0;
    $absent = $stats['absent'] ?? 0;
    $late = $stats['late'] ?? 0;
    $present_pct = $total>0?round(($present/$total)*100,1):0;
    $absent_pct = $total>0?round(($absent/$total)*100,1):0;
    $late_pct = $total>0?round(($late/$total)*100,1):0;

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="attendance_report_'.$user['username'].'_'.date('Ymd_His').'.xls"');
    header('Cache-Control: max-age=0');

    echo "<table border='1'>";
    echo "<tr><th colspan='4'>ATTENDANCE REPORT</th></tr>";
    echo "<tr><td colspan='4'>Generated on: ".date('F j, Y')."</td></tr><tr><td colspan='4'>&nbsp;</td></tr>";

    echo "<tr><th colspan='4'>Student Information</th></tr>";
    echo "<tr><td>Name:</td><td>".$user['fullname']."</td><td>Username:</td><td>".$user['username']."</td></tr>";
    echo "<tr><td>Report Period:</td><td colspan='3'>".($start_date?date('M j, Y',strtotime($start_date)):'All time')." to ".($end_date?date('M j, Y',strtotime($end_date)):'Present')."</td></tr>";
    if($subject_filter && $subject_filter!='all') echo "<tr><td>Subject:</td><td colspan='3'>$subject_filter</td></tr>";
    echo "<tr><td colspan='4'>&nbsp;</td></tr>";

    echo "<tr><th colspan='4'>Attendance Summary</th></tr>";
    echo "<tr><td>Total Classes:</td><td>$total</td><td>Overall Rate:</td><td>$rate%</td></tr>";
    echo "<tr><td>Present:</td><td>$present ($present_pct%)</td><td>Absent:</td><td>$absent ($absent_pct%)</td></tr>";
    echo "<tr><td>Late:</td><td colspan='3'>$late ($late_pct%)</td></tr>";
    echo "<tr><td colspan='4'>&nbsp;</td></tr>";

    if(count($records)>0){
        echo "<tr><th colspan='4'>Detailed Attendance Records</th></tr>";
        echo "<tr><th>Date</th><th>Subject</th><th>Status</th><th>Remarks</th></tr>";
        foreach($records as $r){
            echo "<tr>
                    <td>".date('M j, Y',strtotime($r['date']))."</td>
                    <td>".($r['subject']??'-')."</td>
                    <td>".$r['status']."</td>
                    <td>".($r['remarks']??'-')."</td>
                  </tr>";
        }
    } else echo "<tr><td colspan='4'>No attendance records found.</td></tr>";

    echo "</table>";
    exit;
}

// CSV Report
function generateCSVReport($records, $stats, $rate, $user, $start_date, $end_date, $subject_filter){
    $total = $stats['total'] ?? 0;
    $present = $stats['present'] ?? 0;
    $absent = $stats['absent'] ?? 0;
    $late = $stats['late'] ?? 0;
    $present_pct = $total>0?round(($present/$total)*100,1):0;
    $absent_pct = $total>0?round(($absent/$total)*100,1):0;
    $late_pct = $total>0?round(($late/$total)*100,1):0;

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="attendance_report_'.$user['username'].'_'.date('Ymd_His').'.csv"');

    $out = fopen('php://output','w');

    fputcsv($out,['ATTENDANCE REPORT']);
    fputcsv($out,['Generated on:',date('F j, Y')]);
    fputcsv($out,[]);
    fputcsv($out,['Student Information']);
    fputcsv($out,['Name',$user['fullname']]);
    fputcsv($out,['Username',$user['username']]);
    fputcsv($out,['Report Period',($start_date?date('M j, Y',strtotime($start_date)):'All time').' to '.($end_date?date('M j, Y',strtotime($end_date)):'Present')]);
    if($subject_filter && $subject_filter!='all') fputcsv($out,['Subject',$subject_filter]);
    fputcsv($out,[]);

    fputcsv($out,['Attendance Summary']);
    fputcsv($out,['Total Classes',$total]);
    fputcsv($out,['Present',$present.' ('.$present_pct.'%)']);
    fputcsv($out,['Absent',$absent.' ('.$absent_pct.'%)']);
    fputcsv($out,['Late',$late.' ('.$late_pct.'%)']);
    fputcsv($out,['Overall Attendance Rate',$rate.'%']);
    fputcsv($out,[]);

    if(count($records)>0){
        fputcsv($out,['Detailed Attendance Records']);
        fputcsv($out,['Date','Subject','Status','Remarks']);
        foreach($records as $r){
            fputcsv($out,[
                date('M j, Y',strtotime($r['date'])),
                $r['subject']??'-',
                $r['status'],
                $r['remarks']??'-'
            ]);
        }
    } else fputcsv($out,['No attendance records found.']);

    fclose($out);
    exit;
}

// ===== Generate the report =====
if($format=='excel') generateExcelReport($records,$stats,$rate,$user,$start_date,$end_date,$subject_filter);
elseif($format=='csv') generateCSVReport($records,$stats,$rate,$user,$start_date,$end_date,$subject_filter);
else generatePDFReport($records,$stats,$rate,$user,$start_date,$end_date,$subject_filter);
