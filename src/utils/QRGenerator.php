<?php
require_once __DIR__ . '/../../assets/vendor/autoload.php';
require_once __DIR__ . '/../../assets/vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/../../assets/vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

class QRGenerator {
    private $baseUrl;
    private $roomCode;

    public function __construct($roomCode, $baseUrl = 'http://151.21.203.214:9000/public/login-player.php') {
        if (empty($roomCode)) {
            throw new Exception('Room code is required');
        }
        $this->roomCode = $roomCode;
        $this->baseUrl = $baseUrl;
    }

    public function getQRUrl() {
        return $this->baseUrl . '?code=' . urlencode($this->roomCode);
    }

    public function getQRDataUri() {
        $qrUrl = $this->getQRUrl();

        // Crea un PDF quadrato (200x200mm) per adattarsi all'iframe
        $pdf = new TCPDF('P', 'mm', array(200, 200), true, 'UTF-8', false);
        $pdf->SetCreator('Marriage Game');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // Margini per il centramento visivo
        $pdf->SetMargins(0, 0, 0);

        // QR centrato nel PDF quadrato
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $qrSize = 130; // mm
        $xPosition = ($pageWidth - $qrSize) / 2; // Centra orizzontalmente
        $yPosition = ($pageHeight - $qrSize) / 2; // Centra verticalmente

        // Genera il QR code nel PDF
        $pdf->write2DBarcode($qrUrl, 'QRCODE,L', $xPosition, $yPosition, $qrSize, $qrSize, array(), 'N');

        // Ritorna come base64 data URI
        $pdfContent = $pdf->Output('', 'S');
        $base64 = base64_encode($pdfContent);
        return 'data:application/pdf;base64,' . $base64;
    }

    public function getQRPDF() {
        // Stesso metodo ma ritorna il PDF binario (non data URI)
        $qrUrl = $this->getQRUrl();

        $pdf = new TCPDF('P', 'mm', array(200, 200), true, 'UTF-8', false);
        $pdf->SetCreator('Marriage Game');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetMargins(0, 0, 0);

        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $qrSize = 130;
        $xPosition = ($pageWidth - $qrSize) / 2;
        $yPosition = ($pageHeight - $qrSize) / 2;

        $pdf->write2DBarcode($qrUrl, 'QRCODE,L', $xPosition, $yPosition, $qrSize, $qrSize, array(), 'N');

        return $pdf->Output('', 'S');
    }
}
?>
