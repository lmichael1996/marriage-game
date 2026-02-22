<?php
require_once __DIR__ . '/../../assets/vendor/autoload.php';
require_once __DIR__ . '/../../assets/vendor/tecnickcom/tcpdf/tcpdf.php';

class PDFGenerator {
    private $pdf;
    private $roomCode;
    private $qrUrl;  // QR URL string

    public function __construct($roomCode, $qrUrl) {
        if (empty($roomCode)) {
            throw new Exception('Room code is required');
        }
        if (empty($qrUrl)) {
            throw new Exception('QR URL is required');
        }
        $this->roomCode = $roomCode;
        $this->qrUrl = $qrUrl;  // Store QR URL
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

        // Genera il QR code come immagine da URL
        $qrImageDataUri = $this->generateQRImage($this->qrUrl);

        if ($qrImageDataUri) {
            // Inserisci l'immagine nel PDF (centrata)
            $pageWidth = $this->pdf->GetPageWidth();
            $qrWidth = 70;
            $xPosition = ($pageWidth - $qrWidth) / 2;

            $this->pdf->Image($qrImageDataUri, $xPosition, $this->pdf->GetY(), $qrWidth, $qrWidth);
        }

        $this->pdf->Ln(75);
        $this->pdf->Ln(5);
    }

    /**
     * Genera il QR code come data URI (base64)
     * Funziona sia in TCPDF che in HTML
     */
    private function generateQRImage($url, $size = 250) {
        try {
            // Usa QR Server API
            $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
            $params = [
                'size' => $size . 'x' . $size,
                'data' => $url,
                'format' => 'jpg'
            ];

            $fullUrl = $qrApiUrl . '?' . http_build_query($params);

            // Scarica l'immagine
            $imageData = @file_get_contents($fullUrl);
            if ($imageData === false) {
                return null;
            }

            // Restituisci come data URI
            $base64 = base64_encode($imageData);
            return 'data:image/jpeg;base64,' . $base64;
        } catch (Exception $e) {
            return null;
        }
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
