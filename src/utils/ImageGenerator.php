<?php
/**
 * ImageGenerator - Genera immagini QR code da URL
 * Gestisce il download da API esterna con fallback
 */
class ImageGenerator {

    // URL dell'API QR Server
    private static $QR_API_URL = 'https://api.qrserver.com/v1/create-qr-code/';

    // Base URLs per i QR code
    private static $BASE_URL_PLAYER = 'http://151.64.86.229:9000/public/login-player.php';
    private static $BASE_URL_JUDGE = 'http://151.64.86.229:9000/public/login-judge.php';

    /**
     * Genera un QR code da un room code
     *
     * @param string $roomCode Il codice della stanza
     * @param int $size Dimensione dell'immagine (default 250)
     * @param string $format Formato immagine: 'jpg' o 'png' (default 'jpg')
     * @param string $type Tipo di utente: 'player' o 'judge' (default 'player')
     * @return string|null Dati binari dell'immagine o null se fallisce
     */
    public static function generateQRFromRoomCode($roomCode, $size = 250, $format = 'jpg', $type = 'player') {
        // Scegli l'URL base in base al tipo
        $baseUrl = ($type === 'judge') ? self::$BASE_URL_JUDGE : self::$BASE_URL_PLAYER;

        // Costruisci l'URL del QR
        $qrUrl = $baseUrl . '?code=' . urlencode($roomCode);
        return self::generateQRFromURL($qrUrl, $size, $format);
    }

    /**
     * Genera un QR code da una URL generica
     *
     * @param string $url L'URL da codificare nel QR
     * @param int $size Dimensione dell'immagine
     * @param string $format Formato immagine
     * @return string|null Dati binari dell'immagine o null se fallisce
     */
    public static function generateQRFromURL($url, $size = 250, $format = 'jpg') {
        if (empty($url)) {
            error_log("ImageGenerator: URL is required");
            return null;
        }

        // Valida il formato
        $format = strtolower($format);
        if (!in_array($format, ['jpg', 'jpeg', 'png'])) {
            $format = 'jpg';
        }

        try {
            $params = [
                'size' => $size . 'x' . $size,
                'data' => $url,
                'format' => $format
            ];

            $fullUrl = self::$QR_API_URL . '?' . http_build_query($params);

            // Prova con curl prima (più robusto)
            if (function_exists('curl_init')) {
                $imageData = self::fetchWithCurl($fullUrl);
                if ($imageData !== null) {
                    return $imageData;
                }
            }

            // Fallback a file_get_contents
            $imageData = self::fetchWithFileGetContents($fullUrl);
            if ($imageData !== null) {
                return $imageData;
            }

            error_log("ImageGenerator: Failed to generate QR from URL: $url");
            return null;

        } catch (Exception $e) {
            error_log("ImageGenerator Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Scarica un'immagine usando curl
     *
     * @param string $url L'URL da scaricare
     * @return string|null Dati binari o null se fallisce
     */
    private static function fetchWithCurl($url) {
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
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode == 200 && $imageData !== false && !empty($imageData)) {
                return $imageData;
            }

            if (!empty($error)) {
                error_log("ImageGenerator Curl Error: $error");
            }

            return null;
        } catch (Exception $e) {
            error_log("ImageGenerator Curl Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Scarica un'immagine usando file_get_contents
     *
     * @param string $url L'URL da scaricare
     * @return string|null Dati binari o null se fallisce
     */
    private static function fetchWithFileGetContents($url) {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $imageData = @file_get_contents($url, false, $context);

            if ($imageData !== false && !empty($imageData)) {
                return $imageData;
            }

            return null;
        } catch (Exception $e) {
            error_log("ImageGenerator FileGetContents Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Salva un'immagine come file temporaneo
     *
     * @param string $imageData Dati binari dell'immagine
     * @param string $format Formato file (jpg, png)
     * @return string|null Percorso del file temporaneo o null se fallisce
     */
    public static function saveAsTemporaryFile($imageData, $format = 'jpg') {
        if (empty($imageData)) {
            return null;
        }

        try {
            $tempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.' . $format;
            if (file_put_contents($tempFile, $imageData) !== false) {
                return $tempFile;
            }
            error_log("ImageGenerator: Failed to write temp file: $tempFile");
            return null;
        } catch (Exception $e) {
            error_log("ImageGenerator Temp File Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Converte un'immagine a data URI (base64)
     *
     * @param string $imageData Dati binari dell'immagine
     * @param string $mimeType MIME type (default image/jpeg)
     * @return string|null Data URI o null se fallisce
     */
    public static function toDataURI($imageData, $mimeType = 'image/jpeg') {
        if (empty($imageData)) {
            return null;
        }

        try {
            $base64 = base64_encode($imageData);
            return 'data:' . $mimeType . ';base64,' . $base64;
        } catch (Exception $e) {
            error_log("ImageGenerator DataURI Exception: " . $e->getMessage());
            return null;
        }
    }
}
?>
