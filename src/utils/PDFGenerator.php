<?php
require_once __DIR__ . '/../../assets/vendor/autoload.php';
require_once __DIR__ . '/../../assets/vendor/tecnickcom/tcpdf/tcpdf.php';

class PDFGenerator {
    private $pdf;
    private $roomCode;
    private $qrImageBlob;  // Immagine QR come BLOB

    public function __construct($roomCode, $qrImageBlob) {
        if (empty($roomCode)) {
            throw new Exception('Room code is required');
        }
        if (empty($qrImageBlob)) {
            throw new Exception('QR image BLOB is required');
        }
        $this->roomCode = $roomCode;
        $this->qrImageBlob = $qrImageBlob;  // Store QR image BLOB
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

        // Usa il BLOB QR salvato nel database
        if (!empty($this->qrImageBlob)) {
            // Salva temporaneamente il BLOB come file
            $tempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.jpg';
            if (file_put_contents($tempFile, $this->qrImageBlob) !== false && file_exists($tempFile)) {
                // Inserisci l'immagine nel PDF (centrata)
                $pageWidth = $this->pdf->GetPageWidth();
                $qrWidth = 70;
                $xPosition = ($pageWidth - $qrWidth) / 2;

                $this->pdf->Image($tempFile, $xPosition, $this->pdf->GetY(), $qrWidth, $qrWidth, 'JPG');

                // Pulisci il file temporaneo
                @unlink($tempFile);
            }
        }

        $this->pdf->Ln(75);
        $this->pdf->Ln(5);
    }    public function generate() {
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
