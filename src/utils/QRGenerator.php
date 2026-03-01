<?php
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

/**
 * QRGenerator - Genera codici QR e codici univoci per stanze
 * Usa TCPDF2DBarcode per generare QR SVG internamente (no API esterna, no GD)
 */
class QRGenerator {

    /** Pagina login relativa (es. 'login-player.php') */
    private $loginPage;

    /**
     * @param string $loginPage Nome della pagina login (es. 'login-player.php')
     */
    public function __construct($loginPage) {
        $this->loginPage = $loginPage;
    }

    /**
     * Costruisce il base URL dal server corrente (no link hardcoded)
     */
    private static function getBaseUrl() {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/public/';
    }

    /**
     * Genera codice univoco + SVG del QR
     *
     * @return array ['code' => string, 'svg' => string (SVG markup)]
     */
    public function generate() {
        $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $url = self::getBaseUrl() . $this->loginPage . '?code=' . urlencode($code);

        return [
            'code' => $code,
            'svg'  => $this->generateSVG($url)
        ];
    }

    /**
     * Genera SVG del QR code usando TCPDF2DBarcode (puro PHP, nessuna estensione)
     *
     * @param string $data Contenuto da codificare nel QR
     * @return string|null SVG markup o null se fallisce
     */
    private function generateSVG($data) {
        if (empty($data)) {
            return null;
        }

        try {
            $barcode = new TCPDF2DBarcode($data, 'QRCODE,H');
            $svg = $barcode->getBarcodeSVGcode(3, 3, 'black');
            return !empty($svg) ? $svg : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
