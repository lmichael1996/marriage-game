<?php
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

/**
 * Generates QR codes and unique room codes.
 * Uses TCPDF2DBarcode for SVG QR generation (pure PHP, no external API or GD).
 */
class QRGenerator {

    /**
     * Generate a unique code and its QR SVG.
     *
     * @param string $url  Full login URL without code param (e.g. 'http://host/public/login-player.php')
     * @return array{code: string, svg: string|null}
     */
    public function generate(string $url): array {
        $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $fullUrl = $url . '?code=' . urlencode($code);

        return [
            'code' => $code,
            'svg'  => $this->generateSVG($fullUrl)
        ];
    }

    /**
     * Generate an SVG QR code using TCPDF2DBarcode.
     *
     * @param string $data  Content to encode in the QR code
     * @return string|null  SVG markup or null on failure
     */
    private function generateSVG(string $data): ?string {
        if (empty($data)) {
            return null;
        }

        try {
            $barcode = new TCPDF2DBarcode($data, 'QRCODE,H');
            $svg = $barcode->getBarcodeSVGcode(3, 3, 'black');
            return $svg ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}
