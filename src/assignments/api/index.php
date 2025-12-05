<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments_assignment
 * Columns:
 *   - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (INT UNSIGNED, FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// Set Content-Type header to application/json
header('Content-Type: application/json; charset=utf-8');

// Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return 200 OK for preflight requests
    http_response_code(200);
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

require_once 'database.php';

// Create database connection
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit();
}

// ============================================================================
// REQUEST PARSING
// ============================================================================

// Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request body for POST and PUT requests
$input = [];
$rawInput = file_get_contents('php://input');

if ($method === 'POST' || $method === 'PUT') {
    // Try to decode JSON input first
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
        // Check if JSON decoding failed
        if (json_last_error() !== JSON_ERROR_NONE && $rawInput !== '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid JSON input',
                'error' => json_last_error_msg()
            ]);
            exit();
        }
    }
    
    // Fall back to form data for POST requests if JSON is empty
    if ($method === 'POST' && empty($input) && !empty($_POST)) {
        $input = $_POST;
    }
}

// Parse query parameters
$queryParams = $_GET;

// Get the 'resource' query parameter
$resource = isset($_GET['resource']) ? $_GET['resource'] : '';

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to sanitize string input
 */
function sanitizeInput($data) {
    if ($data === null) {
        return '';
    }
    if (!is_string($data)) {
        if (is_scalar($data)) {
            $data = (string)$data;
        } else {
            return '';
        }
    }

    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return $data;
}

/**
 * Helper function to validate date format (YYYY-MM-DD)
 */
function validateDate($date) {
    if ($date === null) {
        return false;
    }
    if (!is_string($date)) {
        if (is_scalar($date)) {
            $date = (string)$date;
        } else {
            return false;
        }
    }

    $date = trim($date);
    if ($date === '') {
        return false;
    }

    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d === false) {
        return false;
    }

    return $d->format('Y-m-d') === $date;
}

// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Get all assignments
 */
function getAllAssignments($db) {
    // Start building the SQL query
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments WHERE 1=1";
    $params = [];
    
    // Check if 'search' query parameter exists
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $searchTerm = trim($_GET['search']);
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = "%{$searchTerm}%";
    }
    
    // Check if 'sort' and 'order' query parameters exist
    $allowedSortFields = ['title', 'due_date', 'created_at', 'updated_at'];
    $defaultSortField = 'created_at';
    $defaultOrder = 'DESC';
    
    $sortField = $defaultSortField;
    $sortOrder = $defaultOrder;
    
    if (isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields)) {
        $sortField = $_GET['sort'];
    }
    
    if (isset($_GET['order'])) {
        $order = strtoupper($_GET['order']);
        if ($order === 'ASC' || $order === 'DESC') {
            $sortOrder = $order;
        }
    }
    
    $sql .= " ORDER BY $sortField $sortOrder";
    
    // Optional: Add pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    $sql .= " LIMIT :limit OFFSET :offset";
    
    try {
        // Prepare the SQL statement
        $stmt = $db->prepare($sql);
        
        // Bind parameters if search is used
        if (isset($params[':search'])) {
            $stmt->bindParam(':search', $params[':search'], PDO::PARAM_STR);
        }
        
        // Bind pagination parameters
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Execute the prepared statement
        $stmt->execute();
        
        // Fetch all results
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each assignment
        foreach ($assignments as &$assignment) {
            // Decode files JSON
            if (!empty($assignment['files'])) {
                $decodedFiles = json_decode($assignment['files'], true);
                $assignment['files'] = $decodedFiles !== null ? $decodedFiles : [];
            } else {
                $assignment['files'] = [];
            }
            
            // Format dates
            $assignment['due_date'] = $assignment['due_date'] ? date('Y-m-d', strtotime($assignment['due_date'])) : null;
            $assignment['created_at'] = date('Y-m-d H:i:s', strtotime($assignment['created_at']));
            $assignment['updated_at'] = $assignment['updated_at'] ? date('Y-m-d H:i:s', strtotime($assignment['updated_at'])) : null;
            
            // Get comment count for each assignment
            $commentStmt = $db->prepare("SELECT COUNT(*) as comment_count FROM comments_assignment WHERE assignment_id = :assignment_id");
            $commentStmt->bindParam(':assignment_id', $assignment['id'], PDO::PARAM_INT);
            $commentStmt->execute();
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $assignment['comment_count'] = (int)$commentCount['comment_count'];
        }
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM assignments";
        if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
            $countSql .= " WHERE (title LIKE :search OR description LIKE :search)";
        }
        
        $countStmt = $db->prepare($countSql);
        if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
            $countStmt->bindValue(':search', "%{$searchTerm}%", PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $totalCount = (int)$totalResult['total'];
        
        // Return JSON response
        echo json_encode([
            'success' => true,
            'data' => $assignments,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $limit),
                'has_next' => ($page * $limit) < $totalCount,
                'has_previous' => $page > 1
            ],
            'meta' => [
                'search_term' => isset($searchTerm) ? $searchTerm : null,
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'total_fetched' => count($assignments)
            ]
        ], JSON_PRETTY_PRINT);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch assignments',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Get a single assignment by ID
 */
function getAssignmentById($db, $assignmentId) {
    // Validate assignment ID
    if (empty($assignmentId) || !is_numeric($assignmentId) || $assignmentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid assignment ID'
        ]);
        return;
    }
    
    try {
        // Get assignment
        $sql = "SELECT id, title, description, due_date, files, created_at, updated_at 
                FROM assignments WHERE id = :id LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assignment) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Assignment with ID {$assignmentId} not found."
            ]);
            return;
        }
        
        // Decode files
        if (!empty($assignment['files'])) {
            $decodedFiles = json_decode($assignment['files'], true);
            $assignment['files'] = $decodedFiles !== null ? $decodedFiles : [];
        } else {
            $assignment['files'] = [];
        }
        
        // Format dates
        $assignment['due_date'] = $assignment['due_date'] ? date('Y-m-d', strtotime($assignment['due_date'])) : null;
        $assignment['created_at'] = date('Y-m-d H:i:s', strtotime($assignment['created_at']));
        $assignment['updated_at'] = $assignment['updated_at'] ? date('Y-m-d H:i:s', strtotime($assignment['updated_at'])) : null;
        
        // Get comments for this assignment
        $commentsSql = "SELECT id, assignment_id, author, text, created_at 
                       FROM comments_assignment 
                       WHERE assignment_id = :assignment_id 
                       ORDER BY created_at DESC";
        
        $commentsStmt = $db->prepare($commentsSql);
        $commentsStmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $commentsStmt->execute();
        $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format comment dates
        foreach ($comments as &$comment) {
            $comment['created_at'] = date('Y-m-d H:i:s', strtotime($comment['created_at']));
        }
        
        // Add comments to response
        $assignment['comments'] = $comments;
        $assignment['comment_count'] = count($comments);
        
        // Calculate days until due
        if ($assignment['due_date']) {
            $dueDate = new DateTime($assignment['due_date']);
            $today = new DateTime();
            $interval = $today->diff($dueDate);
            
            $assignment['days_until_due'] = $interval->days;
            $assignment['is_overdue'] = $today > $dueDate;
            $assignment['due_status'] = $today > $dueDate ? 'overdue' : ($interval->days <= 3 ? 'soon' : 'future');
        }
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => 'Assignment retrieved successfully',
            'data' => $assignment
        ], JSON_PRETTY_PRINT);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error fetching assignment',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Create a new assignment
 */
function createAssignment($db, $data) {
    // Validate required fields
    $requiredFields = ['title', 'description', 'due_date'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: {$field}"
            ]);
            return;
        }
    }
    
    // Sanitize input
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate = $data['due_date'];
    
    // Validate date
    if (!validateDate($dueDate)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format. Use YYYY-MM-DD'
        ]);
        return;
    }
    
    // Handle files
    $files = [];
    if (!empty($data['files']) && is_array($data['files'])) {
        $files = array_map('sanitizeInput', $data['files']);
    }
    $filesJson = json_encode($files);
    
    // Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':due_date', $dueDate, PDO::PARAM_STR);
        $stmt->bindParam(':files', $filesJson, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $newId = $db->lastInsertId();
            
            // Fetch created assignment
            $selectSql = "SELECT id, title, description, due_date, files, created_at, updated_at 
                          FROM assignments WHERE id = :id";
            $selectStmt = $db->prepare($selectSql);
            $selectStmt->bindParam(':id', $newId, PDO::PARAM_INT);
            $selectStmt->execute();
            $assignment = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            // Decode files
            if (!empty($assignment['files'])) {
                $decodedFiles = json_decode($assignment['files'], true);
                $assignment['files'] = $decodedFiles !== null ? $decodedFiles : [];
            } else {
                $assignment['files'] = [];
            }
            
            // Format dates
            $assignment['due_date'] = date('Y-m-d', strtotime($assignment['due_date']));
            $assignment['created_at'] = date('Y-m-d H:i:s', strtotime($assignment['created_at']));
            $assignment['updated_at'] = date('Y-m-d H:i:s', strtotime($assignment['updated_at']));
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Assignment created successfully',
                'data' => $assignment
            ], JSON_PRETTY_PRINT);
        } else {
            throw new Exception('Failed to insert assignment');
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error creating assignment',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Update an existing assignment
 */
function updateAssignment($db, $data) {
    // Validate required ID
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required field: id'
        ]);
        return;
    }
    
    $assignmentId = (int)$data['id'];
    
    // Check if assignment exists
    $checkSql = "SELECT id FROM assignments WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "Assignment with ID {$assignmentId} not found"
        ]);
        return;
    }
    
    // Build UPDATE query dynamically
    $updates = [];
    $params = [':id' => $assignmentId];
    
    if (isset($data['title']) && $data['title'] !== '') {
        $updates[] = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }
    
    if (isset($data['description']) && $data['description'] !== '') {
        $updates[] = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }
    
    if (isset($data['due_date']) && $data['due_date'] !== '') {
        if (!validateDate($data['due_date'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date format for due_date. Use YYYY-MM-DD'
            ]);
            return;
        }
        $updates[] = "due_date = :due_date";
        $params[':due_date'] = $data['due_date'];
    }
    
    if (isset($data['files']) && is_array($data['files'])) {
        $files = array_map('sanitizeInput', $data['files']);
        $filesJson = json_encode($files);
        $updates[] = "files = :files";
        $params[':files'] = $filesJson;
    }
    
    // If no fields to update
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No fields provided for update'
        ]);
        return;
    }
    
    // Add updated_at
    $updates[] = "updated_at = NOW()";
    
    // Complete UPDATE query
    $sql = "UPDATE assignments SET " . implode(', ', $updates) . " WHERE id = :id";
    
    try {
        $stmt = $db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Fetch updated assignment
            $selectSql = "SELECT id, title, description, due_date, files, created_at, updated_at 
                          FROM assignments WHERE id = :id";
            $selectStmt = $db->prepare($selectSql);
            $selectStmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
            $selectStmt->execute();
            $assignment = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            // Decode files
            if (!empty($assignment['files'])) {
                $decodedFiles = json_decode($assignment['files'], true);
                $assignment['files'] = $decodedFiles !== null ? $decodedFiles : [];
            } else {
                $assignment['files'] = [];
            }
            
            // Format dates
            $assignment['due_date'] = date('Y-m-d', strtotime($assignment['due_date']));
            $assignment['created_at'] = date('Y-m-d H:i:s', strtotime($assignment['created_at']));
            $assignment['updated_at'] = date('Y-m-d H:i:s', strtotime($assignment['updated_at']));
            
            echo json_encode([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => $assignment
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'No changes made to the assignment',
                'data' => null
            ]);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error updating assignment',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Delete an assignment
 */
function deleteAssignment($db, $assignmentId) {
    // Validate ID
    if (empty($assignmentId) || !is_numeric($assignmentId) || $assignmentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid assignment ID'
        ]);
        return;
    }
    
    try {
        // Check if assignment exists
        $checkSql = "SELECT id, title FROM assignments WHERE id = :id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Assignment with ID {$assignmentId} not found"
            ]);
            return;
        }
        
        $assignment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $assignmentTitle = $assignment['title'];
        
        // Delete associated comments first
        $deleteCommentsSql = "DELETE FROM comments_assignment WHERE assignment_id = :assignment_id";
        $deleteCommentsStmt = $db->prepare($deleteCommentsSql);
        $deleteCommentsStmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $deleteCommentsStmt->execute();
        
        // Delete assignment
        $sql = "DELETE FROM assignments WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Assignment '{$assignmentTitle}' deleted successfully",
                'data' => [
                    'id' => $assignmentId,
                    'title' => $assignmentTitle,
                    'comments_deleted' => $deleteCommentsStmt->rowCount()
                ]
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete assignment'
            ]);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error deleting assignment',
            'error' => $e->getMessage()
        ]);
    }
}

// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Get all comments for a specific assignment
 */
function getCommentsByAssignment($db, $assignmentId) {
    // Validate assignment ID
    if (empty($assignmentId) || !is_numeric($assignmentId) || $assignmentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid assignment ID'
        ]);
        return;
    }
    
    try {
        // Check if assignment exists
        $checkSql = "SELECT id FROM assignments WHERE id = :assignment_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Assignment with ID {$assignmentId} not found"
            ]);
            return;
        }
        
        // Get comments
        $sql = "SELECT id, assignment_id, author, text, created_at 
                FROM comments_assignment 
                WHERE assignment_id = :assignment_id 
                ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        foreach ($comments as &$comment) {
            $comment['created_at'] = date('Y-m-d H:i:s', strtotime($comment['created_at']));
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Comments retrieved successfully',
            'data' => $comments,
            'meta' => [
                'assignment_id' => $assignmentId,
                'total_comments' => count($comments)
            ]
        ], JSON_PRETTY_PRINT);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error fetching comments',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Create a new comment
 */
function createComment($db, $data) {
    // Validate required fields
    $requiredFields = ['assignment_id', 'author', 'text'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: {$field}"
            ]);
            return;
        }
    }
    
    // Sanitize input
    $assignmentId = (int)$data['assignment_id'];
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // Validate text
    if (trim($text) === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Comment text cannot be empty'
        ]);
        return;
    }
    
    try {
        // Verify that the assignment exists
        $checkSql = "SELECT id, title FROM assignments WHERE id = :assignment_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Assignment with ID {$assignmentId} not found"
            ]);
            return;
        }
        
        $assignment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Insert comment
        $sql = "INSERT INTO comments_assignment (assignment_id, author, text, created_at) 
                VALUES (:assignment_id, :author, :text, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->bindParam(':author', $author, PDO::PARAM_STR);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        $stmt->execute();
        
        $newId = $db->lastInsertId();
        
        // Fetch created comment
        $selectSql = "SELECT id, assignment_id, author, text, created_at 
                      FROM comments_assignment WHERE id = :id";
        $selectStmt = $db->prepare($selectSql);
        $selectStmt->bindParam(':id', $newId, PDO::PARAM_INT);
        $selectStmt->execute();
        $comment = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format date
        $comment['created_at'] = date('Y-m-d H:i:s', strtotime($comment['created_at']));
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => $comment,
            'assignment_info' => [
                'id' => $assignment['id'],
                'title' => $assignment['title']
            ]
        ], JSON_PRETTY_PRINT);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error creating comment',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Delete a comment
 */
function deleteComment($db, $commentId) {
    // Validate comment ID
    if (empty($commentId) || !is_numeric($commentId) || $commentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid comment ID'
        ]);
        return;
    }
    
    try {
        // Check if comment exists
        $checkSql = "SELECT id, assignment_id, author, text FROM comments_assignment WHERE id = :id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':id', $commentId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Comment with ID {$commentId} not found"
            ]);
            return;
        }
        
        $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete comment
        $sql = "DELETE FROM comments_assignment WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Comment deleted successfully',
                'data' => [
                    'id' => $commentId,
                    'assignment_id' => $comment['assignment_id'],
                    'author' => $comment['author'],
                    'preview' => strlen($comment['text']) > 50 ? 
                                 substr($comment['text'], 0, 50) . '...' : 
                                 $comment['text']
                ]
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete comment'
            ]);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error deleting comment',
            'error' => $e->getMessage()
        ]);
    }
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // Route based on HTTP method and resource type
    if ($method === 'GET') {
        if ($resource === 'assignments') {
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                getAssignmentById($conn, $_GET['id']);
            } else {
                getAllAssignments($conn);
            }
        } elseif ($resource === 'comments') {
            if (isset($_GET['assignment_id']) && !empty($_GET['assignment_id'])) {
                getCommentsByAssignment($conn, $_GET['assignment_id']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing assignment_id parameter for comments'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid resource. Use "assignments" or "comments"'
            ]);
        }
        
    } elseif ($method === 'POST') {
        if ($resource === 'assignments') {
            createAssignment($conn, $input);
        } elseif ($resource === 'comments') {
            createComment($conn, $input);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid resource for POST. Use "assignments" or "comments"'
            ]);
        }
        
    } elseif ($method === 'PUT') {
        if ($resource === 'assignments') {
            updateAssignment($conn, $input);
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'PUT method only supported for assignments resource'
            ]);
        }
        
    } elseif ($method === 'DELETE') {
        if ($resource === 'assignments') {
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($input['id']) ? $input['id'] : '');
            deleteAssignment($conn, $id);
        } elseif ($resource === 'comments') {
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            deleteComment($conn, $id);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid resource for DELETE. Use "assignments" or "comments"'
            ]);
        }
        
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed. Supported methods: GET, POST, PUT, DELETE'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}

?>