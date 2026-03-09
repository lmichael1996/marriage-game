<?php
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../services/ServiceLoader.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/RoomController.php';
require_once __DIR__ . '/controllers/GameController.php';
require_once __DIR__ . '/controllers/QuestionController.php';
require_once __DIR__ . '/controllers/SetController.php';

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
$router->add('add_question_to_set_at_position', 'POST', [SetController::class, 'addQuestionToSetAtPosition']);
$router->add('remove_question_from_set',  'POST', [SetController::class,      'removeQuestionFromSet']);
$router->add('update_question_order',     'POST', [SetController::class,      'updateQuestionOrder']);

// ── Dispatch ─────────────────────────────────────────────────────────────

$router->dispatch();
