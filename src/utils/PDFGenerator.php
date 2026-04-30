<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class PDFGenerator {
    private TCPDF $pdf;
    private string $url;

    private const LOGO_PATH  = __DIR__ . '/../../assets/image/logo.jpeg';
    private const IMAGE_DIR  = __DIR__ . '/../../assets/image';
    private const FONT       = 'dejavusans';

    public function __construct(string $url) {
        $this->url = $url;
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator('MVquiz');
        $this->pdf->SetAuthor('MVquiz Admin');
        $this->pdf->SetTitle('MVquiz - QR Codes');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetAutoPageBreak(false);
    }

    public function addPage(string $title, string $code, string $svgMarkup): void {
        $this->pdf->AddPage();

        if (file_exists(self::LOGO_PATH)) {
            $this->pdf->setAlpha(0.2);
            // Logo at the bottom-center with correct aspect ratio (1181x835 → ~120x85mm)
            $logoW = 120;
            $logoH = 85;
            $logoX = ($this->pdf->GetPageWidth() - $logoW) / 2;
            $logoY = $this->pdf->GetPageHeight() - $logoH - 10;
            $this->pdf->Image(self::LOGO_PATH, $logoX, $logoY, $logoW, $logoH);
            $this->pdf->setAlpha(1);
        }

        // Title
        $this->pdf->SetFillColor(128, 128, 128);
        $this->pdf->SetFont(self::FONT, 'B', 30);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 20, $title, 0, 1, 'C', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(10);

        // QR code SVG
        $this->pdf->SetFont(self::FONT, '', 16);
        $this->pdf->Cell(0, 8, 'Inquadra il QR code per connetterti:', 0, 1, 'C');
        $this->pdf->Ln(5);

        $tempFile = self::IMAGE_DIR . '/qr_' . uniqid() . '.svg';
        file_put_contents($tempFile, $svgMarkup);
        $qrSize = 70;
        $xPosition = ($this->pdf->GetPageWidth() - $qrSize) / 2;

        // White background behind the QR
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Rect($xPosition, $this->pdf->GetY(), $qrSize, $qrSize, 'F');

        $this->pdf->ImageSVG($tempFile, $xPosition, $this->pdf->GetY(), $qrSize, $qrSize);
        @unlink($tempFile);
        $this->pdf->Ln($qrSize + 15);

        // Or connect via website
        $this->pdf->SetFont(self::FONT, '', 16);
        $this->pdf->Cell(0, 8, 'Oppure connettiti a questo sito web:', 0, 1, 'C');
        $this->pdf->Ln(2);

        // Website
        $this->pdf->SetFont(self::FONT, 'B', 18);
        $this->pdf->SetTextColor(45, 52, 54);
        $this->pdf->Cell(0, 8, $this->url, 0, 1, 'C', false, $this->url);
        $this->pdf->Ln(8);

        // Or connect with room code
        $this->pdf->SetFont(self::FONT, '', 16);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 8, 'Con questo codice stanza:', 0, 1, 'C');

        // Room code
        $this->pdf->SetFont(self::FONT, 'B', 42);
        $this->pdf->Cell(0, 16, $code, 0, 1, 'C');
    }

    public function getPDF(): string {
        return $this->pdf->Output('', 'S');
    }
}
?>
