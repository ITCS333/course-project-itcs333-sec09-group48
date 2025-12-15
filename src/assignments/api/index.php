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
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
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


/**
 * Database Connection Class
 * Provides PDO connection to MySQL database
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


// Initialize session if needed
session_start();
// Initialize session if needed
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'api_user_' . time(); // Create a unique user ID
    $_SESSION['authenticated'] = true;
    $_SESSION['last_api_call'] = date('Y-m-d H:i:s');
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// Include the database connection class
require_once '../../common/config.php';

// Get PDO database connection from config
$conn = $pdo;

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
$queryParams = $_GET; // Simpler and more reliable than parsing QUERY_STRING

// Also get any URL parameters if you're using URL rewriting
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // Start building the SQL query
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments WHERE 1=1";
    $params = [];
    
    // Check if 'search' query parameter exists in $_GET
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
        // Prepare the SQL statement using $db->prepare()
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
        
        // Fetch all results as associative array
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For each assignment, decode the 'files' field from JSON to array
        foreach ($assignments as &$assignment) {
            if (!empty($assignment['files'])) {
                $decodedFiles = json_decode($assignment['files'], true);
                $assignment['files'] = $decodedFiles !== null ? $decodedFiles : [];
            } else {
                $assignment['files'] = [];
            }
            
            // Format dates for better readability
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
        
        // Get total count for pagination info
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
        header('Content-Type: application/json');
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
        // Handle database errors
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch assignments',
            'error' => $e->getMessage()
        ]);
    }
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // Validate that $assignmentId is provided and not empty
    if (empty($assignmentId) || !is_numeric($assignmentId) || $assignmentId <= 0) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'success' => false,
            'message' => 'Invalid assignment ID. Please provide a valid positive numeric ID.'
        ]);
        return;
    }
    
    try {
        // Prepare SQL query to select assignment by id
        $sql = "SELECT 
                    a.id, 
                    a.title, 
                    a.description, 
                    a.due_date, 
                    a.files, 
                    a.created_at, 
                    a.updated_at
                FROM assignments a 
                WHERE a.id = :id
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        
        // Bind the :id parameter
        $stmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
        
        // Execute the statement
        $stmt->execute();
        
        // Fetch the result as associative array
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if assignment was found
        if (!$assignment) {
            http_response_code(404); // Not Found
            echo json_encode([
                'success' => false,
                'message' => "Assignment with ID {$assignmentId} not found."
            ]);
            return;
        }
        
        // Decode the 'files' field from JSON to array
        if (!empty($assignment['files'])) {
            $decodedFiles = json_decode($assignment['files'], true);
            $assignment['files'] = $decodedFiles !== null ? $decodedFiles : [];
        } else {
            $assignment['files'] = [];
        }
        
        // Format dates for better readability
        $assignment['due_date'] = $assignment['due_date'] ? date('Y-m-d', strtotime($assignment['due_date'])) : null;
        $assignment['created_at'] = date('Y-m-d H:i:s', strtotime($assignment['created_at']));
        $assignment['updated_at'] = $assignment['updated_at'] ? date('Y-m-d H:i:s', strtotime($assignment['updated_at'])) : null;
        
        // Get comments for this assignment
        $commentsSql = "SELECT 
                            c.id, 
                            c.assignment_id, 
                            c.author, 
                            c.text, 
                            c.created_at
                        FROM comments_assignment c 
                        WHERE c.assignment_id = :assignment_id
                        ORDER BY c.created_at DESC";
        
        $commentsStmt = $db->prepare($commentsSql);
        $commentsStmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $commentsStmt->execute();
        $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format comment dates
        foreach ($comments as &$comment) {
            $comment['created_at'] = date('Y-m-d H:i:s', strtotime($comment['created_at']));
        }
        
        // Add comments to the assignment response
        $assignment['comments'] = $comments;
        $assignment['comment_count'] = count($comments);
        
        // Calculate days until due (if due_date is in the future)
        if ($assignment['due_date']) {
            $dueDate = new DateTime($assignment['due_date']);
            $today = new DateTime();
            $interval = $today->diff($dueDate);
            
            $assignment['days_until_due'] = $interval->days;
            $assignment['is_overdue'] = $today > $dueDate;
            $assignment['due_status'] = $today > $dueDate ? 'overdue' : ($interval->days <= 3 ? 'soon' : 'future');
        }
        
        // Return success response with assignment data
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Assignment retrieved successfully',
            'data' => $assignment
        ], JSON_PRETTY_PRINT);
        
    } catch (PDOException $e) {
        // Handle database errors
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred while fetching assignment',
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        ]);
    } catch (Exception $e) {
        // Handle any other errors
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ]);
    }
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
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
    
    // Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate = $data['due_date'];
    
    // Validate due_date format
    if (!validateDate($dueDate)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format. Use YYYY-MM-DD'
        ]);
        return;
    }
    
    // Handle the 'files' field
    $files = [];
    if (!empty($data['files']) && is_array($data['files'])) {
        $files = array_map('sanitizeInput', $data['files']);
    }
    $filesJson = json_encode($files);
    
    // Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    
    try {
        // Prepare statement
        $stmt = $db->prepare($sql);
        
        // Bind all parameters
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':due_date', $dueDate, PDO::PARAM_STR);
        $stmt->bindParam(':files', $filesJson, PDO::PARAM_STR);
        
        // Execute the statement
        $stmt->execute();
        
        // Check if insert was successful
        if ($stmt->rowCount() > 0) {
            // Get the ID of the inserted assignment
            $newId = $db->lastInsertId();
            
            // Fetch the created assignment
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
            
            // Return success response
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
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // Validate that 'id' is provided in $data
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required field: id'
        ]);
        return;
    }
    
    // Store assignment ID in variable
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
    
    // Build UPDATE query dynamically based on provided fields
    $updates = [];
    $params = [':id' => $assignmentId];
    
    // Check which fields are provided and add to SET clause
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
    
    // If no fields to update (besides updated_at), return 400 error
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No fields provided for update'
        ]);
        return;
    }
    
    // Add updated_at timestamp
    $updates[] = "updated_at = NOW()";
    
    // Complete the UPDATE query
    $sql = "UPDATE assignments SET " . implode(', ', $updates) . " WHERE id = :id";
    
    try {
        // Prepare the statement
        $stmt = $db->prepare($sql);
        
        // Bind all parameters dynamically
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        // Execute the statement
        $stmt->execute();
        
        // Check if update was successful
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
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => $assignment
            ], JSON_PRETTY_PRINT);
        } else {
            // If no rows affected, return appropriate message
            http_response_code(200);
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
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // Validate that $assignmentId is provided and not empty
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
        
        // Delete associated comments first (due to foreign key constraint)
        $deleteCommentsSql = "DELETE FROM comments_assignment WHERE assignment_id = :assignment_id";
        $deleteCommentsStmt = $db->prepare($deleteCommentsSql);
        $deleteCommentsStmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $deleteCommentsStmt->execute();
        
        // Prepare DELETE query for assignment
        $sql = "DELETE FROM assignments WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        // Bind the :id parameter
        $stmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
        
        // Execute the statement
        $stmt->execute();
        
        // Check if delete was successful
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
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
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // Validate that $assignmentId is provided and not empty
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
        
        // Prepare SQL query to select all comments for the assignment
        $sql = "SELECT id, assignment_id, author, text, created_at 
                FROM comments_assignment 
                WHERE assignment_id = :assignment_id 
                ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        
        // Bind the :assignment_id parameter
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        
        // Execute the statement
        $stmt->execute();
        
        // Fetch all results as associative array
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        foreach ($comments as &$comment) {
            $comment['created_at'] = date('Y-m-d H:i:s', strtotime($comment['created_at']));
        }
        
        // Return success response with comments data
        http_response_code(200);
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
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
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
    
    // Sanitize input data
    $assignmentId = (int)$data['assignment_id'];
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // Validate that text is not empty after trimming
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
        
        // Prepare INSERT query for comment
        $sql = "INSERT INTO comments_assignment (assignment_id, author, text, created_at) 
                VALUES (:assignment_id, :author, :text, NOW())";
        
        $stmt = $db->prepare($sql);
        
        // Bind all parameters
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->bindParam(':author', $author, PDO::PARAM_STR);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        
        // Execute the statement
        $stmt->execute();
        
        // Get the ID of the inserted comment
        $newId = $db->lastInsertId();
        
        // Fetch the created comment
        $selectSql = "SELECT id, assignment_id, author, text, created_at 
                      FROM comments_assignment WHERE id = :id";
        $selectStmt = $db->prepare($selectSql);
        $selectStmt->bindParam(':id', $newId, PDO::PARAM_INT);
        $selectStmt->execute();
        $comment = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format date
        $comment['created_at'] = date('Y-m-d H:i:s', strtotime($comment['created_at']));
        
        // Return success response with created comment data
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
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // Validate that $commentId is provided and not empty
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
        
        // Prepare DELETE query
        $sql = "DELETE FROM comments_assignment WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        // Bind the :id parameter
        $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
        
        // Execute the statement
        $stmt->execute();
        
        // Check if delete was successful
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
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
    // Get the 'resource' query parameter to determine which resource to access
    $resource = isset($_GET['resource']) ? $_GET['resource'] : '';
    
    // Route based on HTTP method and resource type
    if ($method === 'GET') {
        // Handle GET requests
        
        if ($resource === 'assignments') {
            // Check if 'id' query parameter exists
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                getAssignmentById($conn, $_GET['id']);
            } else {
                getAllAssignments($conn);
            }
        } elseif ($resource === 'comments') {
            // Check if 'assignment_id' query parameter exists
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
            // Invalid resource, return 400 error
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid resource. Use "assignments" or "comments"'
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // Call createAssignment($db, $data)
            createAssignment($conn, $input);
        } elseif ($resource === 'comments') {
            // Call createComment($db, $data)
            createComment($conn, $input);
        } else {
            // Invalid resource, return 400 error
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid resource for POST. Use "assignments" or "comments"'
            ]);
        }
        
    } elseif ($method === 'PUT') {
        // Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // Call updateAssignment($db, $data)
            updateAssignment($conn, $input);
        } else {
            // PUT not supported for other resources
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'PUT method only supported for assignments resource'
            ]);
        }
        
    } elseif ($method === 'DELETE') {
        // Handle DELETE requests
        
        if ($resource === 'assignments') {
            // Get 'id' from query parameter or request body
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($input['id']) ? $input['id'] : '');
            deleteAssignment($conn, $id);
        } elseif ($resource === 'comments') {
            // Get comment 'id' from query parameter
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            deleteComment($conn, $id);
        } else {
            // Invalid resource, return 400 error
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid resource for DELETE. Use "assignments" or "comments"'
            ]);
        }
        
    } else {
        // Method not supported
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed. Supported methods: GET, POST, PUT, DELETE'
        ]);
    }
    
} catch (PDOException $e) {
    // Handle database errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    // Handle general errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // Set HTTP response code
    http_response_code($statusCode);
    
    // Ensure data is an array
    if (!is_array($data)) {
        $data = ['data' => $data];
    }
    
    // Add success flag if not present
    if (!isset($data['success'])) {
        $data['success'] = ($statusCode >= 200 && $statusCode < 300);
    }
    
    // Echo JSON encoded data
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Exit to prevent further execution
    exit();
}



/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // If data is not a string, convert to string or return empty string
    if ($data === null) {
        return '';
    }
    if (!is_string($data)) {
        // Attempt to convert scalars to string safely, otherwise return empty string
        if (is_scalar($data)) {
            $data = (string)$data;
        } else {
            return '';
        }
    }

    // Trim whitespace from beginning and end
    $data = trim($data);

    // Remove HTML and PHP tags
    $data = strip_tags($data);

    // Convert special characters to HTML entities (preserve UTF-8 and both quotes)
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // Reject null or non-scalar values early
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

    // Trim and ensure non-empty
    $date = trim($date);
    if ($date === '') {
        return false;
    }

    // Use DateTime to validate the format strictly
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d === false) {
        return false;
    }

    // Ensure the input exactly matches the canonical Y-m-d representation
    return $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // Ensure $allowedValues is an array with at least one element
    if (!is_array($allowedValues) || empty($allowedValues)) {
        return false;
    }

    // If value is null, consider it invalid
    if ($value === null) {
        return false;
    }

    // Use strict comparison to avoid type-coercion issues
    return in_array($value, $allowedValues, true);
}

?>