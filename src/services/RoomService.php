<?php
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/SetRepo.php';
require_once __DIR__ . '/../repository/JudgeRepo.php';
require_once __DIR__ . '/../utils/QRGenerator.php';

/**
 * Handles room business logic (creation, start, close, details).
 */
class RoomService {
    private RoomRepo $roomRepo;
    private PlayerRepo $playerRepo;
    private RoundRepo $roundRepo;
    private SetRepo $setRepo;
    private JudgeRepo $judgeRepo;

    private const LOGIN_PLAYER_URL = 'login-player.php';
    private const LOGIN_JUDGE_URL  = 'login-judge.php';

    public function __construct() {
        $this->roomRepo = new RoomRepo();
        $this->playerRepo = new PlayerRepo();
        $this->roundRepo = new RoundRepo();
        $this->setRepo = new SetRepo();
        $this->judgeRepo = new JudgeRepo();
    }

    /**
     * Create a new room with QR codes for player and judge.
     *
     * @param int $questionSetId  Question set to assign
     * @return array  Result with room_id and codes on success
     */
    public function createRoom(int $questionSetId): array {
        $qrPlayer = new QRGenerator(self::LOGIN_PLAYER_URL);
        $qrJudge  = new QRGenerator(self::LOGIN_JUDGE_URL);

        $playerPair = $qrPlayer->generate();
        $judgePair  = $qrJudge->generate();

        $qrPlayerBase64 = $playerPair['svg'] ? base64_encode($playerPair['svg']) : null;
        $qrJudgeBase64  = $judgePair['svg']  ? base64_encode($judgePair['svg'])  : null;

        $roomId = $this->roomRepo->createRoom(
            $playerPair['code'], $judgePair['code'],
            $questionSetId, $qrPlayerBase64, $qrJudgeBase64
        );

        if (!$roomId) {
            return [
                'success' => false,
                'error' => 'Failed to create room'
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
            return ['success' => false, 'error' => 'Room not found'];
        }

        $clearJudge = !$this->judgeRepo->isJudgeConnected($roomId);
        $success = $this->roomRepo->startRoom($roomId, $clearJudge);

        return [
            'success' => $success,
            'message' => $success ? 'Room started' : 'Failed to start room',
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

        $room['has_winner']   = $room['winner_id'] !== null;
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
     * Determine and save the room winner based on all round rankings.
     * Uses the same scoring logic as GameService::getFinalLeaderboard.
     *
     * @param int $roomId  Room ID
     * @return bool  true if winner was set
     */
    public function markWinner(int $roomId): bool {
        $leaderboard = svc('game')->getFinalLeaderboard($roomId);
        return $leaderboard['success'] && !empty($leaderboard['leaderboard']);
    }

    /**
     * Check if a player is the winner of a room.
     *
     * @param int $roomId    Room ID
     * @param int $playerId  Player ID
     * @return bool
     */
    public function isWinner(int $roomId, int $playerId): bool {
        $winnerId = $this->roomRepo->getWinnerId($roomId);
        return $winnerId !== null && $winnerId == $playerId;
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
