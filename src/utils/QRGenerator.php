<?php
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

/**
 * Generates QR codes and unique room codes.
 * Uses TCPDF2DBarcode for SVG QR generation (pure PHP, no external API or GD).
 */
class QRGenerator {

    private string $loginPage;
    private const EXTERNAL_API_TIMEOUT = 3;
    private const CURL_IPIFY = 'curl -s --max-time ' . self::EXTERNAL_API_TIMEOUT . ' https://api.ipify.org';
    private const EXTERNAL_PORT = 9000;

    /**
     * @param string $loginPage  Relative login page (e.g. 'login-player.php')
     */
    public function __construct(string $loginPage) {
        $this->loginPage = $loginPage;
    }

    /**
     * Build the base URL from the current server (no hardcoded links).
     */
    private static function getBaseUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // If running on localhost, try to get the public IP
        if (str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
            $ip = trim((string) @shell_exec(self::CURL_IPIFY));
            if (!empty($ip)) {
                $host = $ip . ':' . self::EXTERNAL_PORT;
            }
        }

        return $scheme . '://' . $host . '/public/';
    }

    /**
     * Generate a unique code and its QR SVG.
     *
     * @return array{code: string, svg: string|null}
     */
    public function generate(): array {
        $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $url = self::getBaseUrl() . $this->loginPage . '?code=' . urlencode($code);

        return [
            'code' => $code,
            'svg'  => $this->generateSVG($url)
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
