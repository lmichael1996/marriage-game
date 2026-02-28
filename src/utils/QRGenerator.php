<?php
/**
 * QRGenerator - Genera codici QR e codici univoci per stanze
 * Classe oggetto semplificata con un URL base
 */
class QRGenerator {

    // URL dell'API QR Server
    private const QR_API_URL = 'https://api.qrserver.com/v1/create-qr-code/';

    // Parametri di istanza
    private $baseUrl;

    /**
     * Costruttore della classe QRGenerator
     *
     * @param string $baseUrl URL base per il login (player o judge)
     */
    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Genera sia il codice che il QR code in un'unica chiamata
     *
     * @return array Array con 'code' e 'data' (QR binario)
     */
    public function generate() {
        // Genera codice univoco
        $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        // Genera QR code
        $url = $this->baseUrl . '?code=' . urlencode($code);
        $qrData = $this->generateQRFromURL($url, 250);

        return [
            'code' => $code,
            'data' => $qrData
        ];
    }

    /**
     * Genera un QR code da una URL (metodo privato)
     *
     * @param string $url L'URL da codificare nel QR
     * @param int $size Dimensione dell'immagine
     * @return string|null Dati binari dell'immagine o null se fallisce
     */
    private function generateQRFromURL($url, $size = 250) {
        if (empty($url)) {
            return null;
        }

        try {
            $params = [
                'size' => $size . 'x' . $size,
                'data' => $url,
                'format' => 'jpg'
            ];

            $fullUrl = self::QR_API_URL . '?' . http_build_query($params);

            // Scarica il QR usando curl
            $imageData = $this->fetchWithCurl($fullUrl);
            if ($imageData !== null) {
                return $imageData;
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Scarica un'immagine usando curl (metodo privato)
     *
     * @param string $url L'URL da scaricare
     * @return string|null Dati binari o null se fallisce
     */
    private function fetchWithCurl($url) {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200 && $imageData !== false && !empty($imageData)) {
                return $imageData;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
