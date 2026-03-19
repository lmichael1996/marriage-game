<?php

/**
 * Handles room business logic (creation, start, close, details).
 */
class RoomService {
    private const LOGIN_PLAYER_PAGE = 'login-player.php';
    private const LOGIN_JUDGE_PAGE  = 'login-judge.php';
    private const EXTERNAL_API_TIMEOUT = 3;
    private const EXTERNAL_PORT = 9000;

    public function __construct(
        private RoomRepo $roomRepo,
        private PlayerRepo $playerRepo,
        private RoundRepo $roundRepo,
        private SetRepo $setRepo,
        private JudgeRepo $judgeRepo
    ) {}

    /**
     * Build the base URL from the current server.
     */
    private static function getBaseUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if (str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
            $cmd = 'curl -s --max-time ' . self::EXTERNAL_API_TIMEOUT . ' https://api.ipify.org';
            $ip = trim((string) @shell_exec($cmd));
            if (!empty($ip)) {
                $host = $ip . ':' . self::EXTERNAL_PORT;
            }
        }

        return $scheme . '://' . $host;
    }

    /**
     * Create a new room with QR codes for player and judge.
     *
     * @param int $questionSetId  Question set to assign
     * @return array  Result with room_id and codes on success
     */
    public function createRoom(int $questionSetId): array {
        $baseUrl = self::getBaseUrl();
        $qr = new QRGenerator();

        $playerPair = $qr->generate($baseUrl . BASE_URL . 'public/' . self::LOGIN_PLAYER_PAGE);
        $judgePair  = $qr->generate($baseUrl . BASE_URL . 'public/' . self::LOGIN_JUDGE_PAGE);

        $qrPlayerBase64 = $playerPair['svg'] ? base64_encode($playerPair['svg']) : null;
        $qrJudgeBase64  = $judgePair['svg']  ? base64_encode($judgePair['svg'])  : null;

        $roomId = $this->roomRepo->createRoom(
            $playerPair['code'], $judgePair['code'],
            $questionSetId, $qrPlayerBase64, $qrJudgeBase64, $baseUrl . rtrim(BASE_URL, '/')
        );

        if (!$roomId) {
            return [
                'success' => false,
                'error' => 'Impossibile creare la stanza'
            ];
        }

        return [
            'success'     => true,
            'room_id'     => $roomId,
            'code_player' => $playerPair['code'],
            'code_judge'  => $judgePair['code']
        ];
    }

    /**
     * Start a room (set status to active).
     *
     * @param int $roomId  Room ID
     * @return array  Result
     */
    public function startRoom(int $roomId): array {
        $room = $this->roomRepo->getRoomById($roomId);
        if (!$room) {
            return [
                'success' => false,
                'error' => 'Stanza non trovata'
            ];
        }

        $clearJudge = !$this->judgeRepo->isJudgeConnected($roomId);
        $success = $this->roomRepo->startRoom($roomId, $clearJudge);

        return [
            'success' => $success,
            'message' => $success ? 'Stanza avviata' : 'Impossibile avviare la stanza',
            'room_id' => $roomId
        ];
    }

    /**
     * Finish game — close the room when all rounds are completed.
     *
     * @param int $roomId  Room ID
     * @return array  Result
     */
    public function finishGame(int $roomId): array {
        return $this->roomRepo->closeRoom($roomId);
    }

    /**
     * Cancel a room (admin manually closes it).
     * Deletes associated players so they don't persist as stale data.
     *
     * @param int $roomId  Room ID
     * @return array  Result
     */
    public function cancelRoom(int $roomId): array {
        return $this->roomRepo->cancelRoom($roomId);
    }

    /**
     * Get full room details including players and question set.
     *
     * @param int $roomId  Room ID
     * @return array|null  Room details or null if not found
     */
    public function getRoomDetails(int $roomId): ?array {
        $room = $this->roomRepo->getRoomById($roomId);
        if (!$room) {
            return null;
        }

        $room['has_ranking']  = $room['final_ranking'] !== null;
        $room['players']      = $this->playerRepo->getPlayersByRoomId($roomId);
        $room['player_count'] = count($room['players']);

        if ($room['qset_id']) {
            $room['question_set'] = $this->setRepo->getSetById($room['qset_id']);
        }

        return $room;
    }

    /**
     * Get the active (current) round for a room.
     *
     * @param int $roomId  Room ID
     * @return array|null  Active round or null
     */
    public function getActiveRound(int $roomId): ?array {
        return $this->roundRepo->getActiveRound($roomId);
    }

    /**
     * Count the total number of rounds played in a room.
     *
     * @param int $roomId  Room ID
     * @return int         Number of rounds
     */
    public function getRoundCount(int $roomId): int {
        return $this->roundRepo->countRounds($roomId);
    }

    /**
     * Get a round by its position (round_number) within a room.
     *
     * @param int $roomId    Room ID
     * @param int $position  Round number (1-based)
     * @return array|null  Round data or null
     */
    public function getRoundByPosition(int $roomId, int $position): ?array {
        return $this->roundRepo->getRoundByPosition($roomId, $position);
    }

    /**
     * Determine and save the final ranking based on all round rankings.
     * Uses the same scoring logic as GameService::getFinalLeaderboard.
     *
     * @param int $roomId  Room ID
     * @return bool  true if ranking was saved
     */
    public function markFinalRanking(int $roomId): bool {
        $leaderboard = Container::game()->getFinalLeaderboard($roomId);
        return $leaderboard['success'] && !empty($leaderboard['leaderboard']);
    }

    /**
     * Get a player's placement (1st, 2nd, 3rd, or 0) from the final ranking.
     *
     * @param int $roomId    Room ID
     * @param int $playerId  Player ID
     * @return int  Position (1, 2, 3, 4, …); 0 if not found in ranking
     */
    public function getPlacement(int $roomId, int $playerId): int {
        $ranking = $this->roomRepo->getFinalRanking($roomId);
        foreach ($ranking as $i => $entry) {
            if (($entry['player_id'] ?? null) == $playerId) {
                return $i + 1; // 1-based position
            }
        }
        return 0;
    }

    /**
     * Get connected devices (players + judge status) for a room.
     *
     * @param int $roomId  Room ID
     * @return array  ['devices' => [...], 'count' => int, 'judge_connected' => bool]
     */
    public function getConnectedDevices(int $roomId): array {
        $devices = $this->playerRepo->getPlayersByRoomId($roomId);

        return [
            'devices'         => $devices,
            'count'           => count($devices),
            'judge_connected' => $this->isJudgeConnected($roomId)
        ];
    }

    /**
     * Get a room by ID.
     *
     * @param int $roomId  Room ID
     * @return array|null  Room data or null
     */
    public function getRoomById(int $roomId): ?array {
        return $this->roomRepo->getRoomById($roomId);
    }

    /**
     * Get a room by its player code.
     *
     * @param string $code  Player room code
     * @return array|null   Room data or null
     */
    public function getRoomByPlayerCode(string $code): ?array {
        return $this->roomRepo->getRoomByPlayerCode($code);
    }

    /**
     * Get the number of players in a room.
     *
     * @param int $roomId  Room ID
     * @return int  Player count
     */
    public function getPlayerCount(int $roomId): int {
        return count($this->playerRepo->getPlayersByRoomId($roomId));
    }

    /**
     * Check if a judge is connected to the room.
     *
     * @param int $roomId  Room ID
     * @return bool
     */
    public function isJudgeConnected(int $roomId): bool {
        return $this->judgeRepo->isJudgeConnected($roomId);
    }
}
