<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class PDFGenerator {
    private $pdf;
    private $codePlayer;
    private $codeJudge;
    private $svgPlayer;
    private $svgJudge;

    private const LOGO_PATH = __DIR__ . '/../../assets/image/background.jpg';

    public function __construct($codePlayer, $codeJudge, $svgPlayer, $svgJudge) {
        if (empty($codePlayer) || empty($codeJudge)) {
            throw new Exception('Both room codes are required');
        }
        if (empty($svgPlayer) || empty($svgJudge)) {
            throw new Exception('Both QR SVGs are required');
        }
        $this->codePlayer = $codePlayer;
        $this->codeJudge = $codeJudge;
        $this->svgPlayer = $svgPlayer;
        $this->svgJudge = $svgJudge;
        $this->initializePDF();
    }

    private function initializePDF() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator('MVquiz');
        $this->pdf->SetAuthor('MVquiz Admin');
        $this->pdf->SetTitle('MVquiz - QR Codes');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
    }

    private function addPage($title, $code, $svgMarkup, $footerRole) {
        $this->pdf->AddPage();

        if (file_exists(self::LOGO_PATH)) {
            $this->pdf->setAlpha(0.2);
            $this->pdf->Image(self::LOGO_PATH, 5, 0, 200, 297);
            $this->pdf->setAlpha(1);
        }

        // Titolo
        $this->pdf->SetFillColor(128, 128, 128);
        $this->pdf->SetFont('helvetica', 'B', 24);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 20, $title, 0, 1, 'C', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(10);

        // Codice stanza
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, 'Codice Stanza:', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', 'B', 32);
        $this->pdf->Cell(0, 20, $code, 0, 1, 'C');
        $this->pdf->Ln(10);

        // QR code SVG
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, 'Inquadra il QR code per connetterti:', 0, 1, 'C');
        $this->pdf->Ln(5);

        $tempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.svg';
        file_put_contents($tempFile, $svgMarkup);
        $qrSize = 70;
        $xPosition = ($this->pdf->GetPageWidth() - $qrSize) / 2;

        // Sfondo bianco dietro il QR
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Rect($xPosition, $this->pdf->GetY(), $qrSize, $qrSize, 'F');

        $this->pdf->ImageSVG($tempFile, $xPosition, $this->pdf->GetY(), $qrSize, $qrSize);
        @unlink($tempFile);
        $this->pdf->Ln(80);

        // Footer
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->MultiCell(0, 5, $footerRole . ': Puoi connetterti usando il codice stanza o scannerizzando il QR code.', 0, 'C');
    }

    public function generate() {
        $this->addPage('MVquiz - Giocatore', $this->codePlayer, $this->svgPlayer, 'Il Giocatore');
        $this->addPage('MVquiz - Giudice', $this->codeJudge, $this->svgJudge, 'Il Giudice');
    }

    public function getPDF() {
        return $this->pdf->Output('', 'S');
    }
}
?>
