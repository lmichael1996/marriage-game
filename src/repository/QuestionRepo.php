<?php
require_once __DIR__ . '/../config/database.php';

class QuestionRepo {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function create($name, $description = '') {
        $stmt = $this->conn->prepare("INSERT INTO question_sets (set_name, set_description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        return $this->conn->insert_id;
    }
    
    public function getAll($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $result = $this->conn->query("
            SELECT qs.id,
                   qs.set_name,
                   qs.set_description,
                   qs.created_at,
                   qs.updated_at,
                   COUNT(r.id) as total_rounds
            FROM question_sets qs
            LEFT JOIN rounds r ON r.question_set_id = qs.id
            GROUP BY qs.id, qs.set_name, qs.set_description, qs.created_at, qs.updated_at
            ORDER BY qs.set_name ASC
            LIMIT $perPage OFFSET $offset
        ");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        error_log("QuestionRepo.getAll() returned: " . json_encode($data));
        return $data;
    }
    
    public function getTotalCount() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM question_sets");
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM question_sets WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function update($id, $name, $description) {
        $stmt = $this->conn->prepare("UPDATE question_sets SET set_name = ?, set_description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        return $stmt->execute();
    }
    
    public function delete($id) {
        // Delete all rounds in this set
        $stmt = $this->conn->prepare("DELETE FROM rounds WHERE question_set_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Delete the set
        $stmt = $this->conn->prepare("DELETE FROM question_sets WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function search($query) {
        $searchTerm = "%$query%";
        $stmt = $this->conn->prepare("
            SELECT qs.*, 
                   COUNT(r.id) as total_rounds
            FROM question_sets qs
            LEFT JOIN rounds r ON r.question_set_id = qs.id
            WHERE qs.set_name LIKE ?
            GROUP BY qs.id, qs.set_name, qs.set_description, qs.created_at, qs.updated_at
            ORDER BY qs.set_name ASC
        ");
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getRounds($setId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM rounds 
            WHERE question_set_id = ? 
            ORDER BY round_number ASC
        ");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Create a question set with multiple questions
     */
    public function createWithQuestions($setName, $setDescription, $questions) {
        // Create the question set
        $setId = $this->create($setName, $setDescription);
        
        if (!$setId) {
            return false;
        }
        
        // Add questions
        foreach ($questions as $i => $q) {
            $roundNumber = $i + 1;
            $question = $q['question'] ?? '';
            $type = $q['type'] ?? 'multiple';
            $timer = isset($q['timer']) ? intval($q['timer']) : 30;
            
            if (!$question) continue;
            
            $option1 = $q['option1'] ?? '';
            $option2 = $q['option2'] ?? '';
            $option3 = $q['option3'] ?? '';
            $option4 = $q['option4'] ?? '';
            $correct = isset($q['correct']) ? intval($q['correct']) : 1;
            
            // Auto-set options for true/false
            if ($type === 'truefalse') {
                $option1 = 'Vero';
                $option2 = 'Falso';
                $option3 = '';
                $option4 = '';
            }
            
            // For clickfirst, no correct answer needed and no timer
            if ($type === 'clickfirst') {
                $correct = null;
                $option1 = $option2 = $option3 = $option4 = '';
                $timer = null;
            }
            
            // Insert round
            $stmt = $this->conn->prepare("
                INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer, status_round) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("iissssssii", $setId, $roundNumber, $type, $question, $option1, $option2, $option3, $option4, $correct, $timer);
            $stmt->execute();
            $stmt->close();
        }
        
        return $setId;
    }
    
    /**
     * Update a question set with questions
     */
    public function updateWithQuestions($setId, $setName, $setDescription, $questions) {
        // Update set name and description
        $this->update($setId, $setName, $setDescription);
        
        // Get existing rounds for this set
        $stmt = $this->conn->prepare("SELECT id, round_number FROM rounds WHERE question_set_id = ? ORDER BY round_number");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingRounds = [];
        while ($row = $result->fetch_assoc()) {
            $existingRounds[$row['round_number']] = $row['id'];
        }
        $stmt->close();
        
        // Update or insert questions
        foreach ($questions as $i => $q) {
            $roundNumber = $i + 1;
            $question = $q['question'] ?? '';
            $type = $q['type'] ?? 'multiple';
            $timer = isset($q['timer']) ? intval($q['timer']) : 30;
            
            if (!$question) continue;
            
            $option1 = $q['option1'] ?? '';
            $option2 = $q['option2'] ?? '';
            $option3 = $q['option3'] ?? '';
            $option4 = $q['option4'] ?? '';
            $correct = isset($q['correct']) ? intval($q['correct']) : 1;
            
            // Auto-set options for true/false
            if ($type === 'truefalse') {
                $option1 = 'Vero';
                $option2 = 'Falso';
                $option3 = '';
                $option4 = '';
            }
            
            // For clickfirst, no timer
            if ($type === 'clickfirst') {
                $correct = null;
                $option1 = $option2 = $option3 = $option4 = '';
                $timer = null;
            }
            
            // Update existing round or insert new one
            if (isset($existingRounds[$roundNumber])) {
                // Update existing round (only if status is 'pending')
                $stmt = $this->conn->prepare("
                    UPDATE rounds 
                    SET round_type = ?, question = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_answer = ?, timer = ?
                    WHERE id = ? AND status_round = 'pending'
                ");
                $roundId = $existingRounds[$roundNumber];
                $stmt->bind_param("sssssssii", $type, $question, $option1, $option2, $option3, $option4, $correct, $timer, $roundId);
                $stmt->execute();
                $stmt->close();
                unset($existingRounds[$roundNumber]);
            } else {
                // Insert new round
                $stmt = $this->conn->prepare("
                    INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer, status_round) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("iissssssii", $setId, $roundNumber, $type, $question, $option1, $option2, $option3, $option4, $correct, $timer);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Delete remaining rounds (only if status is 'pending')
        foreach ($existingRounds as $roundId) {
            $stmt = $this->conn->prepare("DELETE FROM rounds WHERE id = ? AND status_round = 'pending'");
            $stmt->bind_param("i", $roundId);
            $stmt->execute();
            $stmt->close();
        }
        
        return true;
    }
}
