<?php
require_once __DIR__ . '/../config/app.php';

// ── Debug error handlers (JSON instead of HTML on 500) ────────────────────

if (DEBUG) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

function _apiJsonError(int $code, string $message, array $extra = []): never
{
    while (ob_get_level()) ob_end_clean();
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json');
    }
    $body = ['success' => false, 'error' => $message];
    if (DEBUG && $extra) {
        $body['debug'] = $extra;
    }
    echo json_encode($body);
    exit;
}

set_exception_handler(function (Throwable $e): never {
    _apiJsonError(500, DEBUG ? $e->getMessage() : 'Errore interno del server', [
        'type'  => get_class($e),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) return false;
    _apiJsonError(500, DEBUG ? $errstr : 'Errore interno del server', [
        'type' => 'PHP Error ' . $errno,
        'file' => $errfile,
        'line' => $errline,
    ]);
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        _apiJsonError(500, DEBUG ? $err['message'] : 'Errore interno del server', [
            'type' => 'Fatal Error ' . $err['type'],
            'file' => $err['file'],
            'line' => $err['line'],
        ]);
    }
});

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/RoomController.php';
require_once __DIR__ . '/controllers/GameController.php';
require_once __DIR__ . '/controllers/QuestionController.php';
require_once __DIR__ . '/controllers/SetController.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../services/ServiceLoader.php';

header('Content-Type: application/json');

// ── Route map ────────────────────────────────────────────────────────────
//  endpoint                      method  controller                 action                       middleware
//  ─────────────────────────────────────────────────────────────────────────

$router = new Router();

// Auth (public)
$router->add('player_login',              'POST', [AuthController::class,     'playerLogin']);
$router->add('admin_login',               'POST', [AuthController::class,     'adminLogin']);
$router->add('judge_login',               'POST', [AuthController::class,     'judgeLogin']);

// Room
$router->add('create_room',               'POST', [RoomController::class,     'createRoom'],               'admin');
$router->add('start_room',                'POST', [RoomController::class,     'startRoom'],                'auth');
$router->add('check_room_status',         '*',    [RoomController::class,     'checkRoomStatus'],          'auth');
$router->add('delete_room',               'POST', [RoomController::class,     'deleteRoom'],               'admin');
$router->add('connected_devices',         '*',    [RoomController::class,     'connectedDevices']);
$router->add('generate_pdf',              'POST', [RoomController::class,     'generatePDF']);

// Game (sub-routed via ?action=...)
$router->add('game',                      '*',    [GameController::class,     'gameAction'],               'auth');
$router->add('answer',                    'POST', [GameController::class,     'answer'],                   'auth');
$router->add('round_answers',             '*',    [GameController::class,     'roundAnswers'],             'auth');
$router->add('final_leaderboard',         '*',    [GameController::class,     'finalLeaderboard'],         'auth');
$router->add('player_progress',           '*',    [GameController::class,     'playerProgress'],           'auth');

// Questions & Categories
$router->add('get_question',              'GET',  [QuestionController::class, 'getQuestion']);
$router->add('get_questions',             'GET',  [QuestionController::class, 'getQuestions']);
$router->add('get_categories',            'GET',  [QuestionController::class, 'getCategories']);
$router->add('save_categories',           'POST', [QuestionController::class, 'saveCategories']);

// Question Sets
$router->add('get_questionset',           'GET',  [SetController::class,      'getQuestionSet']);
$router->add('add_questionset',           'POST', [SetController::class,      'addQuestionSet']);
$router->add('update_questionset',        'POST', [SetController::class,      'updateQuestionSet']);
$router->add('delete_questionset',        'POST', [SetController::class,      'deleteQuestionSet']);
$router->add('get_set_questions',         'GET',  [SetController::class,      'getSetQuestions']);
$router->add('add_question_to_set',       'POST', [SetController::class,      'addQuestionToSet']);
$router->add('remove_question_from_set',  'POST', [SetController::class,      'removeQuestionFromSet']);
$router->add('update_question_order',     'POST', [SetController::class,      'updateQuestionOrder']);

// ── Dispatch ─────────────────────────────────────────────────────────────

$router->dispatch();
