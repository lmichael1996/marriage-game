<?php
require_once __DIR__ . '/../repository/SettingsRepo.php';

/**
 * Service for game settings management (points configuration).
 */
class SettingsService {
    private SettingsRepo $settingsRepo;

    private const array DEFAULT_POINTS = [
        // Multiple Choice points
        'points_mult_1st'  => 25,
        'points_mult_2nd'  => 18,
        'points_mult_3rd'  => 15,
        'points_mult_4th'  => 12,
        'points_mult_5th'  => 10,
        'points_mult_6th'  => 8,
        'points_mult_7th'  => 6,
        'points_mult_8th'  => 4,
        'points_mult_9th'  => 2,
        'points_mult_10th' => 1,

        // True/False points
        'points_tf_1st'  => 20,
        'points_tf_2nd'  => 15,
        'points_tf_3rd'  => 12,
        'points_tf_4th'  => 10,
        'points_tf_5th'  => 8,
        'points_tf_6th'  => 6,
        'points_tf_7th'  => 5,
        'points_tf_8th'  => 3,
        'points_tf_9th'  => 2,
        'points_tf_10th' => 1,

        // Click First points
        'points_clickfirst' => 50
    ];

    public function __construct() {
        $this->settingsRepo = new SettingsRepo();
    }

    /**
     * Get a single setting value by key.
     *
     * @param string $key  Setting key
     * @return string|null Setting value or null if not found
     */
    public function getSetting(string $key): ?string {
        return $this->settingsRepo->getSetting($key);
    }

    /**
     * Get all game settings.
     *
     * @return array  ['success' => true, 'settings' => [...]]
     */
    public function getAllSettings(): array {
        $settings = $this->settingsRepo->getAllSettings();
        return [
            'success'  => true,
            'settings' => $settings
        ];
    }

    /**
     * Save game settings (points for each question type and position).
     *
     * @param array $settingsData  Raw settings data (e.g. from POST)
     * @return array  Result array
     */
    public function saveSettings(array $settingsData): array {
        $settings = [];
        foreach (self::DEFAULT_POINTS as $key => $default) {
            $settings[$key] = intval($settingsData[$key] ?? $default);
        }

        $success = $this->settingsRepo->saveSettings($settings);

        if (!$success) {
            return ['success' => false, 'error' => 'Failed to save settings'];
        }

        return ['success' => true];
    }

    /**
     * Get the full points map for a round type (position => points).
     *
     * @param string $roundType  Round type (multiple, truefalse)
     * @param int    $maxPositions  Max positions to include
     * @return array<int, int>  [1 => 25, 2 => 18, ...]
     */
    public function getPointsMap(string $roundType, int $maxPositions = 10): array {
        $prefix = $roundType === 'truefalse' ? 'points_tf' : 'points_mult';
        $map = [];
        for ($pos = 1; $pos <= $maxPositions; $pos++) {
            $suffix = match ($pos) {
                1 => '1st',
                2 => '2nd',
                3 => '3rd',
                default => "{$pos}th"
            };
            $setting = $this->getSetting("{$prefix}_{$suffix}");
            $map[$pos] = $setting ? intval($setting) : 0;
        }
        return $map;
    }

    /**
     * Get points for the clickfirst winner.
     *
     * @return int  Points
     */
    public function getClickfirstPoints(): int {
        return intval($this->getSetting('points_clickfirst'));
    }
}
