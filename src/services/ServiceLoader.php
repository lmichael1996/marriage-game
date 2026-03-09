<?php
// ── Repository includes ──────────────────────────────────────────────────
require_once __DIR__ . '/../repository/UserRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/JudgeRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/AnswerRepo.php';
require_once __DIR__ . '/../repository/QuestionRepo.php';
require_once __DIR__ . '/../repository/SetRepo.php';
require_once __DIR__ . '/../repository/SettingsRepo.php';
require_once __DIR__ . '/../repository/TableRepository.php';

// ── Utility includes ─────────────────────────────────────────────────────
require_once __DIR__ . '/../utils/QRGenerator.php';

// ── Service includes ─────────────────────────────────────────────────────
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/GameService.php';
require_once __DIR__ . '/RoomService.php';
require_once __DIR__ . '/QuestionService.php';
require_once __DIR__ . '/SetService.php';
require_once __DIR__ . '/TableService.php';
require_once __DIR__ . '/SettingsService.php';

// ── Service Container ────────────────────────────────────────────────────

class Container
{
    /** @var array<class-string, object> Singleton repository instances */
    private static array $repos = [];

    /** @var array<string, object> Singleton service instances */
    private static array $services = [];

    // ── Repository factory (singleton per class) ─────────────────────────

    /** @template T of object */
    /** @param class-string<T> $class */
    /** @return T */
    private static function repo(string $class): object
    {
        return self::$repos[$class] ??= new $class();
    }

    // ── Typed service accessors (lazy singleton with DI) ─────────────────

    public static function auth(): AuthService
    {
        return self::$services[__FUNCTION__] ??= new AuthService(
            self::repo(UserRepo::class),
            self::repo(PlayerRepo::class),
            self::repo(RoomRepo::class),
            self::repo(JudgeRepo::class),
        );
    }

    public static function game(): GameService
    {
        return self::$services[__FUNCTION__] ??= new GameService(
            self::repo(RoundRepo::class),
            self::repo(AnswerRepo::class),
            self::repo(RoomRepo::class),
            self::question(),
            self::settings(),
        );
    }

    public static function room(): RoomService
    {
        return self::$services[__FUNCTION__] ??= new RoomService(
            self::repo(RoomRepo::class),
            self::repo(PlayerRepo::class),
            self::repo(RoundRepo::class),
            self::repo(SetRepo::class),
            self::repo(JudgeRepo::class),
        );
    }

    public static function question(): QuestionService
    {
        return self::$services[__FUNCTION__] ??= new QuestionService(
            self::repo(QuestionRepo::class),
        );
    }

    public static function set(): SetService
    {
        return self::$services[__FUNCTION__] ??= new SetService(
            self::repo(SetRepo::class),
        );
    }

    public static function table(): TableService
    {
        return self::$services[__FUNCTION__] ??= new TableService(
            self::repo(TableRepository::class),
        );
    }

    public static function settings(): SettingsService
    {
        return self::$services[__FUNCTION__] ??= new SettingsService(
            self::repo(SettingsRepo::class),
        );
    }
}
