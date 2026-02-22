<?php
require_once __DIR__ . '/../../assets/vendor/autoload.php';
require_once __DIR__ . '/../../assets/vendor/tecnickcom/tcpdf/tcpdf.php';

class PDFGenerator {
    private $pdf;
    private $roomCode;
    private $qrSource;  // Either a data URI (base64) or a file path to image

    public function __construct($roomCode, $qrSource) {
        if (empty($roomCode)) {
            throw new Exception('Room code is required');
        }
        if (empty($qrSource)) {
            throw new Exception('QR source (data URI or file path) is required');
        }
        $this->roomCode = $roomCode;
        $this->qrSource = $qrSource;  // Store QR source
        $this->initializePDF();
    }

    private function initializePDF() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator('Marriage Game');
        $this->pdf->SetAuthor('Marriage Game Admin');
        $this->pdf->SetTitle('Room Code: ' . $this->roomCode);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->AddPage();
    }

    public function addTitle() {
        $this->pdf->SetFont('helvetica', 'B', 24);
        $this->pdf->Cell(0, 15, '🎮 Marriage Game', 0, 1, 'C');

        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 10, 'Stanza Attiva', 0, 1, 'C');

        $this->pdf->Ln(10);
    }

    public function addRoomCode() {
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, 'Codice Stanza:', 0, 1, 'C');

        $this->pdf->SetFont('helvetica', 'B', 32);
        $this->pdf->Cell(0, 20, $this->roomCode, 0, 1, 'C');

        $this->pdf->Ln(10);
    }

    public function addQRCode() {
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, 'Inquadra il QR code per connetterti:', 0, 1, 'C');
        $this->pdf->Ln(5);

        // The source may be either a local file path or a data URI. TCPDF prefers files.
        if (!empty($this->qrSource)) {
            // If it's an existing file path, use it directly
            if (is_string($this->qrSource) && file_exists($this->qrSource)) {
                $pageWidth = $this->pdf->GetPageWidth();
                $qrWidth = 70;
                $xPosition = ($pageWidth - $qrWidth) / 2;

                // Guess type from extension (default to JPG)
                $ext = strtolower(pathinfo($this->qrSource, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $ext = 'jpg';
                }
                $this->pdf->Image($this->qrSource, $xPosition, $this->pdf->GetY(), $qrWidth, $qrWidth, strtoupper($ext));
            } else if (strpos($this->qrSource, 'data:image/') === 0) {
                // Data URI: decode and write temp file
                $parts = explode(',', $this->qrSource, 2);
                $base64Data = $parts[1] ?? '';
                $binaryData = base64_decode($base64Data, true);
                if ($binaryData !== false) {
                    $tempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.jpg';
                    if (file_put_contents($tempFile, $binaryData) !== false && file_exists($tempFile)) {
                        $pageWidth = $this->pdf->GetPageWidth();
                        $qrWidth = 70;
                        $xPosition = ($pageWidth - $qrWidth) / 2;

                        $this->pdf->Image($tempFile, $xPosition, $this->pdf->GetY(), $qrWidth, $qrWidth, 'JPG');

                        @unlink($tempFile);
                    }
                }
            }
        }

        $this->pdf->Ln(75);
        $this->pdf->Ln(5);
    }

    public function addFooterInfo() {
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->MultiCell(0, 5, 'I giocatori possono connettersi usando il codice stanza o scannerizzando il QR code.', 0, 'C');
    }

    public function generate() {
        $this->addTitle();
        $this->addRoomCode();
        $this->addQRCode();
        $this->addFooterInfo();
    }

    public function getPDF() {
        return $this->pdf->Output('', 'S');
    }
}
?>
