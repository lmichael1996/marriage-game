<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class PDFGenerator {
    private TCPDF $pdf;
    private string $codePlayer;
    private string $codeJudge;
    private string $svgPlayer;
    private string $svgJudge;

    private const LOGO_PATH = __DIR__ . '/../../assets/image/logo.jpeg';
    private const FONT = 'dejavusans';
    private const URL = 'https://www.mvmusicaeventi.it/';

    public function __construct(string $codePlayer, string $codeJudge, string $svgPlayer, string $svgJudge) {
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

    private function initializePDF(): void {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator('MVquiz');
        $this->pdf->SetAuthor('MVquiz Admin');
        $this->pdf->SetTitle('MVquiz - QR Codes');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetAutoPageBreak(false);
    }

    private function addPage(string $title, string $code, string $svgMarkup, string $footerRole): void {
        $this->pdf->AddPage();

        if (file_exists(self::LOGO_PATH)) {
            $this->pdf->setAlpha(0.2);
            // Logo at the bottom-right with correct aspect ratio (1181x835 → ~120x85mm)
            $logoW = 120;
            $logoH = 85;
            $logoX = $this->pdf->GetPageWidth() - $logoW - 2;
            $logoY = $this->pdf->GetPageHeight() - $logoH - 10;
            $this->pdf->Image(self::LOGO_PATH, $logoX, $logoY, $logoW, $logoH);
            $this->pdf->setAlpha(1);
        }

        // Title
        $this->pdf->SetFillColor(128, 128, 128);
        $this->pdf->SetFont(self::FONT, 'B', 24);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 20, $title, 0, 1, 'C', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(10);

        // QR code SVG
        $this->pdf->SetFont(self::FONT, '', 12);
        $this->pdf->Cell(0, 8, 'Inquadra il QR code per connetterti:', 0, 1, 'C');
        $this->pdf->Ln(5);

        $tempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.svg';
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
        $this->pdf->SetFont(self::FONT, '', 12);
        $this->pdf->Cell(0, 8, 'Oppure connettiti a questo sito web:', 0, 1, 'C');
        $this->pdf->Ln(2);

        // Website
        $this->pdf->SetFont(self::FONT, 'B', 13);
        $this->pdf->SetTextColor(45, 52, 54);
        $this->pdf->Cell(0, 8, self::URL, 0, 1, 'C', false, self::URL);
        $this->pdf->Ln(8);

        // Or connect with room code
        $this->pdf->SetFont(self::FONT, '', 12);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 8, 'Con questo codice stanza:', 0, 1, 'C');

        // Room code
        $this->pdf->SetFont(self::FONT, 'B', 32);
        $this->pdf->Cell(0, 16, $code, 0, 1, 'C');
    }

    public function generate(): void {
        $this->addPage('MVquiz - Giocatore', $this->codePlayer, $this->svgPlayer, 'Giocatore');
        $this->addPage('MVquiz - Giudice', $this->codeJudge, $this->svgJudge, 'Giudice');
    }

    public function getPDF(): string {
        return $this->pdf->Output('', 'S');
    }
}
?>
