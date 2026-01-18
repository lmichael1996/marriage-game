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
    
    public function getAll() {
        $result = $this->conn->query("
            SELECT qs.*, 
                   COUNT(r.id) as total_rounds
            FROM question_sets qs
            LEFT JOIN rounds r ON r.question_set_id = qs.id
            GROUP BY qs.id
            ORDER BY qs.id DESC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM question_sets WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function update($id, $name, $description) {
        $stmt = $this->conn->prepare("UPDATE question_sets SET set_name = ?, set_description = ? WHERE id = ?");
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
            WHERE qs.set_name LIKE ? OR qs.set_description LIKE ?
            GROUP BY qs.id
            ORDER BY qs.id DESC
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
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
        
        // Delete existing rounds for this set
        $stmt = $this->conn->prepare("DELETE FROM rounds WHERE question_set_id = ?");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $stmt->close();
        
        // Add new questions
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
            
            $stmt = $this->conn->prepare("
                INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer, status_round) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("iissssssii", $setId, $roundNumber, $type, $question, $option1, $option2, $option3, $option4, $correct, $timer);
            $stmt->execute();
            $stmt->close();
        }
        
        return true;
    }
}
