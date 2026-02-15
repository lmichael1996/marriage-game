# Marriage Game - Simplified Counter-Based Architecture

## Overview

The application uses a **simple counter-based flow** where:

- **Admin** increments a counter in `$_SESSION['round_counter_' . $roomCode]`
- Each counter increment loads the next question via `getQuestionByCounter()`
- Game state is **purely stored in the database `rounds` table**
- **No shared session variables** between admin and player

---

## Admin Flow (room-admin.php)

```
1. GET /room-admin.php?room_code=XXX
2. Load: $currentCounter = $_SESSION['round_counter_' . $roomCode] ?? 1
3. Load: $nextQuestion = QuestionService::getQuestionByCounter($qsetId, $currentCounter)
4. Load: $activeRound = RoomService::getActiveRound($roomId)
5. Logic: If activeRound exists AND matches nextQuestion → show timer + "Prossima Domanda" button
         Else if nextQuestion exists → show "AVVIA ROUND" button
         Else → show final leaderboard
6. On "AVVIA ROUND": POST to API /start_round with question_id → creates row in rounds table
7. On "Prossima Domanda": POST increment_counter → counter++ → reload page (step 2)
```

**Session State (admin only):**

- `$_SESSION['round_counter_' . $roomCode]` - Current question counter
- `$_SESSION['room_info_' . $roomCode]` - Cached room data (players count, total questions)

**No shared state with player** - Counter is local to admin's session

---

## Player Flow (player.php)

```
1. Polling every 1 second: API /get_game_state?counter=currentRoundCounter
2. API returns round at position counter, or success=false if not available
3. If round exists AND round.round_number === currentRoundCounter:
   - Start round display
   - Show timer, answer options
   - Player answers or timeout
4. On answer or timeout:
   - POST API /answer with round_id, answer, time_taken
   - Increment currentRoundCounter++ (local JavaScript state)
   - Go back to waiting screen (step 1)
```

**JavaScript State (player only):**

- `currentRoundCounter` - Current question counter (increments locally)
- `currentRoundId` - Round ID for answer submission
- `hasAnswered` - Flag to prevent double submission
- `roundInProgress` - Flag for round state

**No shared state with admin** - Counter is local to player's JavaScript

---

## Database Communication (PURE)

**Only communication between admin and player is via `rounds` table:**

### Admin → Player

```sql
INSERT INTO rounds (room_id, question_id, round_number, ...)
VALUES (...)
-- Creates a new round when admin clicks "AVVIA ROUND"
```

### Player → Admin

```sql
SELECT * FROM rounds
WHERE room_id = ?
ORDER BY id DESC
LIMIT 1 OFFSET (counter - 1)
-- Retrieves round at counter position
```

**Answer submission:**

```sql
INSERT INTO answer (round_id, player_id, answer, answer_time)
VALUES (...)
-- Player submits answer to specific round
```

---

## Service Layer (Enforced)

**All API endpoints call services ONLY. Zero direct repository instantiation.**

### Key Services:

#### QuestionService

```php
getQuestionByCounter($qsetId, $counter)
// Returns: ['id', 'question', 'option1', 'option2', 'option3', 'option4', ...]
// Gets question at counter position (1-based)

getQuestionCountByQset($qsetId)
// Returns total number of questions in set
```

#### RoomService

```php
getActiveRound($roomId)
// Returns: Most recent round from database
// Used by admin to check if question is "active"

getRoundByPosition($roomId, $counter)
// Returns: Round at counter position
// Used by player to poll for round data

getRoomDetails($roomCode)
// Returns room information
```

#### GameService

```php
startRound($questionId, $roomCode)
// Creates new row in rounds table
// Returns: ['success' => true, 'round_id' => X]

submitAnswerByRoundId($roundId, $answer, $timeTaken)
// Records player's answer
// Returns: ['success' => true]

getFinalLeaderboard($roomCode)
// Calculates total scores across all rounds with medals
// Returns: ['leaderboard' => [['username', 'score', 'medal'], ...]]

getTopAnswers($roundId, $limit)
// Gets fastest answers for current round
// Returns: ['top_answers' => [...]]
```

---

## API Endpoints (Service-Layer Only)

### GET `/api.php?endpoint=game&action=get_game_state&counter=N`

Returns round at position N:

```json
{
  "success": true,
  "round_number": 2,
  "question": "What is love?",
  "option1": "...",
  "option2": "...",
  "timer": 30,
  "id": 42
}
```

Returns `{"success": false}` if no round at counter.

### POST `/api.php?endpoint=game&action=start_round`

Body: `{"question_id": 7}`
Creates new round in database.

```json
{
  "success": true,
  "round_id": 42
}
```

### POST `/api.php?endpoint=answer`

Body: `{"round_id": 42, "answer": 2, "time_taken": 5.3}`
Records player's answer.

```json
{
  "success": true
}
```

### GET `/api.php?endpoint=final_leaderboard`

Returns final leaderboard with medals.

```json
{
  "success": true,
  "leaderboard": [
    { "username": "Alice", "score": 150, "medal": "🥇" },
    { "username": "Bob", "score": 120, "medal": "🥈" }
  ]
}
```

### GET `/api.php?endpoint=round_answers&round_id=42`

Returns top 10 fastest answers for current round.

```json
{
  "success": true,
  "top_answers": [
    { "username": "Alice", "answer": 2, "answer_time": "5.30" },
    { "username": "Bob", "answer": 1, "answer_time": "6.15" }
  ]
}
```

---

## Game Over Detection

**Admin:** `if (!$question)` = No more questions = Game Over

- Shows final leaderboard
- Fetches via API `/final_leaderboard`

**Player:** Automatically detected

- After answering last round, polling returns `success: false`
- Player stays on waiting screen (game naturally ends)

---

## Key Design Principles

✅ **Simple counter-based logic** - Increment, load, repeat
✅ **Pure database communication** - Admin ↔ Player via `rounds` table only
✅ **No shared session state** - Counter is local to each user/context
✅ **Service-layer enforcement** - Zero direct repository calls in API
✅ **Clean separation of concerns** - Admin UI, Player UI, Services, Repos clearly separated
✅ **No multi-room conflicts** - Each session has own `round_counter_roomCode` key
✅ **Stateless API** - All state in database, not in memory

---

## Files Modified for Simplification

1. **public/room-admin.php** - Counter increment, question loading, simple UI logic
2. **public/player.php** - Polling with local JavaScript counter
3. **src/api/api.php** - Service-layer only, zero direct repo calls
4. **src/services/\* .php** - Core business logic
5. **src/repository/\* .php** - Database access only
