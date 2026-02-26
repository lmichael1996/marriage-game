<?php
require_once __DIR__ . '/../../assets/vendor/autoload.php';
require_once __DIR__ . '/../../assets/vendor/tecnickcom/tcpdf/tcpdf.php';

class PDFGenerator {
    private $pdf;
    private $codePlayer;
    private $codeJudge;
    private $qrSourcePlayer;
    private $qrSourceJudge;

    public function __construct($codePlayer, $codeJudge, $qrSourcePlayer, $qrSourceJudge) {
        if (empty($codePlayer) || empty($codeJudge)) {
            throw new Exception('Both room codes are required');
        }
        if (empty($qrSourcePlayer) || empty($qrSourceJudge)) {
            throw new Exception('Both QR sources are required');
        }
        $this->codePlayer = $codePlayer;
        $this->codeJudge = $codeJudge;
        $this->qrSourcePlayer = $qrSourcePlayer;
        $this->qrSourceJudge = $qrSourceJudge;
        $this->initializePDF();
    }

    private function initializePDF() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator('Mvquiz');
        $this->pdf->SetAuthor('Mvquiz Admin');
        $this->pdf->SetTitle('Mvquiz - QR Codes');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
    }

    private function addPageTitle($title, $roleColor) {
        // Remove emoji, use background color header instead
        $this->pdf->SetFillColor($roleColor[0], $roleColor[1], $roleColor[2]);
        $this->pdf->SetFont('helvetica', 'B', 24);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 20, $title, 0, 1, 'C', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(10);
    }

    private function addRoomCodeSection($code) {
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, 'Codice Stanza:', 0, 1, 'C');

        $this->pdf->SetFont('helvetica', 'B', 32);
        $this->pdf->Cell(0, 20, $code, 0, 1, 'C');

        $this->pdf->Ln(10);
    }

    private function addQRCodeSection($qrSource) {
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, 'Inquadra il QR code per connetterti:', 0, 1, 'C');
        $this->pdf->Ln(5);

        if (!empty($qrSource)) {
            $base64Data = null;

            // If it's an existing file path, use it directly
            if (is_string($qrSource) && file_exists($qrSource)) {
                $pageWidth = $this->pdf->GetPageWidth();
                $qrWidth = 70;
                $xPosition = ($pageWidth - $qrWidth) / 2;

                // Guess type from extension (default to JPG)
                $ext = strtolower(pathinfo($qrSource, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $ext = 'jpg';
                }
                $this->pdf->Image($qrSource, $xPosition, $this->pdf->GetY(), $qrWidth, $qrWidth, strtoupper($ext));
            } else if (strpos($qrSource, 'data:image/') === 0) {
                // Data URI with prefix: decode and write temp file
                $parts = explode(',', $qrSource, 2);
                $base64Data = $parts[1] ?? '';
            } else {
                // Pure base64 from database (no prefix)
                $base64Data = $qrSource;
            }

            // If we have base64 data, decode and create temp file
            if (!empty($base64Data)) {
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

    private function addFooterInfo($roleText) {
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->MultiCell(0, 5, $roleText . ': Puoi connetterti usando il codice stanza o scannerizzando il QR code.', 0, 'C');
    }

    private function addPlayerPage() {
        $this->pdf->AddPage();

        // Add background image with opacity - narrower width
        $logoPath = __DIR__ . '/../../assets/image/background.jpg';
        if (file_exists($logoPath)) {
            $this->pdf->setAlpha(0.2); // 20% opacity
            $this->pdf->Image($logoPath, 5, 0, 200, 297); // 200mm width instead of 210mm
            $this->pdf->setAlpha(1); // Reset to full opacity
        }

        $this->addPageTitle('Mvquiz - Giocatore', [128, 128, 128]); // Gray for player
        $this->addRoomCodeSection($this->codePlayer);
        $this->addQRCodeSection($this->qrSourcePlayer);
        $this->addFooterInfo('Il Giocatore');
    }

    private function addJudgePage() {
        $this->pdf->AddPage();

        // Add background image with opacity - narrower width
        $logoPath = __DIR__ . '/../../assets/image/background.jpg';
        if (file_exists($logoPath)) {
            $this->pdf->setAlpha(0.2); // 20% opacity
            $this->pdf->Image($logoPath, 5, 0, 200, 297); // 200mm width instead of 210mm
            $this->pdf->setAlpha(1); // Reset to full opacity
        }

        $this->addPageTitle('Mvquiz - Giudice', [128, 128, 128]); // Gray for judge
        $this->addRoomCodeSection($this->codeJudge);
        $this->addQRCodeSection($this->qrSourceJudge);
        $this->addFooterInfo('Il Giudice');
    }

    public function generate() {
        $this->addPlayerPage();
        $this->addJudgePage();
    }

    public function getPDF() {
        return $this->pdf->Output('', 'S');
    }
}
?>
