<?php
require_once 'db.php';

// Path to fpdf.php
$fpdf_path = 'fpdf/fpdf.php'; 
if (file_exists($fpdf_path)) {
    require_once($fpdf_path);
}

// Prevent Redeclare Error for PHPMailer calls
if (!class_exists('PDF')) {
    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'OFFICIAL APPOINTMENT LETTER', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 5, 'External Academic Panel (EAP) Management System', 0, 1, 'C');
            $this->Ln(10);
            $this->Line(10, 32, 200, 32);
        }

        function Footer() {
            $this->SetY(-25);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 5, 'This is an electronically generated document. No signature is required.', 0, 1, 'C');
            $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }
}

/**
 * @param array $data Database row for the panel
 * @param string $mode 'I' for browser view, 'S' for email attachment string
 */
function generatePanelLetterPDF($data, $mode = 'I') {
    if ($mode === 'I' && ob_get_length()) ob_clean();

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 11);

    // Format the Username as PANEL001 (Padded to 3 digits)
    $panelID = 'PANEL' . str_pad($data['id'], 3, '0', STR_PAD_LEFT);

    // Date and Reference
    $pdf->Cell(0, 10, 'Date: ' . date('d F Y'), 0, 1, 'R');
    $pdf->Cell(0, 5, 'Ref: ' . $panelID . '/' . date('Y') . '/APP', 0, 1, 'R');
    $pdf->Ln(10);

    // Recipient Details
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, strtoupper($data['panel_name']), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'Email: ' . $data['email'], 0, 1);
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 10, 'Subject: APPOINTMENT AS EXTERNAL ACADEMIC PANEL (EAP) MEMBER', 0, 1);
    $pdf->Ln(5);

    $pdf->SetFont('Arial', '', 11);
    // Formatting the appointment date from the database
    $apptDate = (!empty($data['start_date'])) ? date('d F Y', strtotime($data['start_date'])) : 'To Be Confirmed';

    $content = "Dear " . $data['panel_name'] . ",\n\n" .
               "We are pleased to inform you that your application has been ACCEPTED. You are formally appointed as an External Academic Panel member for the " . $data['programme'] . " programme.\n\n" .
               "As part of this appointment, you are required to attend the official meeting/session on the following date:\n" .
               "APPOINTMENT DATE: " . $apptDate . "\n\n" .
               "Please use the credentials below to log in to the EAP Portal to access the programme evaluation dashboard and relevant documents.";

    $pdf->MultiCell(0, 7, $content);
    $pdf->Ln(5);

    // CREDENTIALS BOX
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 10, ' PORTAL ACCESS CREDENTIALS', 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(60, 10, ' Username:', 1, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 10, ' ' . $panelID, 1, 1); // Result: PANEL001

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(60, 10, ' Temporary Password:', 1, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 10, ' 123456', 1, 1);

    $pdf->Ln(15);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'Sincerely,', 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 7, 'Registrar Office,', 0, 1);
    $pdf->Cell(0, 7, 'EAP Management System', 0, 1);

    return $pdf->Output($mode, 'Appointment_Letter_' . $panelID . '.pdf');
}

// Logic for manual "Download Letter" button
if (basename($_SERVER['PHP_SELF']) == 'generateletter.php' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM panel_members WHERE id = $id");
    $data = $res->fetch_assoc();
    if ($data && $data['status'] === 'Approved') {
        generatePanelLetterPDF($data, 'I');
    } else {
        echo "No approved panel found with this ID.";
    }
}
?>