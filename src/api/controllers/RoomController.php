<?php

/**
 * Handles room API endpoints (create, start, status, delete, devices, PDF).
 */
class RoomController
{
    public function createRoom(): void
    {
        $room   = Container::room();
        $result = $room->createRoom((int)(Router::input()['question_set_id'] ?? 0));

        if ($result['success']) {
            $_SESSION['active_room_code']  = $result['code_player'];
            $_SESSION['active_judge_code'] = $result['code_judge'];
            $_SESSION['room_id']           = $result['room_id'];

            $roomData = $room->getRoomDetails($result['room_id']);
            if ($roomData) {
                if (!empty($roomData['qr_uri_player'])) $result['qr_uri_player'] = 'data:image/svg+xml;base64,' . $roomData['qr_uri_player'];
                if (!empty($roomData['qr_uri_judge']))  $result['qr_uri_judge']  = 'data:image/svg+xml;base64,' . $roomData['qr_uri_judge'];
            }
        }
        Router::respond($result);
    }

    public function startRoom(): void
    {
        $roomId = authRoomId();
        if (!$roomId) Router::error('Nessuna stanza attiva in sessione');
        Router::respond(Container::room()->startRoom($roomId));
    }

    public function checkRoomStatus(): void
    {
        $roomId = authRoomId();
        if (!$roomId) Router::respond([
            'room_open' => false,
            'status'    => 'unknown',
            'message'   => 'Nessuna stanza associata',
        ]);

        $roomData = Container::room()->getRoomDetails($roomId);
        if (!$roomData) Router::respond([
            'room_open' => false,
            'status'    => 'unknown',
            'message'   => 'Stanza non trovata',
        ]);

        $status = $roomData['status_room'] ?? 'unknown';
        Router::respond([
            'room_open'   => ($status === 'open' || $status === 'running'),
            'status'      => $status,
            'has_ranking' => $roomData['has_ranking'] ?? false,
        ]);
    }

    public function deleteRoom(): void
    {
        $roomId = authRoomId();
        if (!$roomId) Router::error('Nessuna stanza attiva');

        $result = Container::room()->cancelRoom($roomId);
        if ($result['success']) {
            unset($_SESSION['active_room_code'], $_SESSION['active_judge_code'], $_SESSION['room_id']);
        }
        Router::respond($result);
    }

    public function connectedDevices(): void
    {
        $roomId = authRoomId();
        if (!$roomId) Router::respond(['success' => true, 'devices' => [], 'count' => 0]);

        $result = Container::room()->getConnectedDevices($roomId);
        Router::respond([
            'success'         => true,
            'devices'         => $result['devices'],
            'count'           => $result['count'],
            'judge_connected' => $result['judge_connected'] ?? false,
        ]);
    }

    public function generatePDF(): void
    {
        $data     = Router::input();
        $roomCode = strtoupper(trim($data['room_code'] ?? ''));

        // Resolve room: prefer room_code from request, fall back to session
        if ($roomCode) {
            $roomResult = Container::room()->getRoomByPlayerCode($roomCode);
        } else {
            $roomId     = authRoomId();
            $roomResult = $roomId ? Container::room()->getRoomDetails($roomId) : null;
        }
        if (!$roomResult) Router::error('Stanza non trovata', 404);

        $codePlayer = $roomResult['code_player'] ?? null;
        $codeJudge  = $roomResult['code_judge']  ?? null;
        if (!$codePlayer || !$codeJudge) Router::error('Codici stanza non trovati. Ricrea la stanza.');

        // Retrieve base64-encoded QR SVGs from DB and decode
        $svgPlayer = !empty($roomResult['qr_uri_player']) ? base64_decode($roomResult['qr_uri_player']) : null;
        $svgJudge  = !empty($roomResult['qr_uri_judge'])  ? base64_decode($roomResult['qr_uri_judge'])  : null;
        if (!$svgPlayer || !$svgJudge) Router::error('QR code non trovati per questa stanza. Ricrea la stanza.', 500);

        $url = $roomResult['base_url'] ?? '';
        if (!$url) Router::error('URL base non trovato per questa stanza', 500);

        // Normalize: strip trailing /public/ or /public from old records
        $url = rtrim($url, '/');
        $url = preg_replace('#/public$#', '', $url);

        // Generate PDF with both player and judge pages
        $pdf = new PDFGenerator($url);
        $pdf->addPage('MVquiz - Giocatore', $codePlayer, $svgPlayer);
        $pdf->addPage('MVquiz - Giudice', $codeJudge, $svgJudge);

        Router::respond([
            'success'  => true,
            'pdf_data' => 'data:application/pdf;base64,' . base64_encode($pdf->getPDF()),
            'filename' => 'MVquiz-room-' . $codePlayer . '-' . $codeJudge . '.pdf',
        ]);
    }
}
