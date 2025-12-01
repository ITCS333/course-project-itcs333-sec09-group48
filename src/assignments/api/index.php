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

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json; charset=utf-8');

// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return 200 OK for preflight requests
    http_response_code(200);
    exit();
}


// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
require_once '../../config/Database.php';

// TODO: Create database connection
try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit();
}

// TODO: Set PDO to throw exceptions on errors



// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$input = [];
if ($method === 'POST' || $method === 'PUT') {
    $rowInput = file_get_contents('php://input');
    $input = json_decode($rowInput, true);
    if (!empty($rowInput)){
        $decoded = json_decode($rowInput, true);
    } else {
        parse_str($rowInput, $decoded);
    }
    if ($method === 'POST' && empty($input) && !empty($_POST)) {
        $input = $_POST;
    }
}

// TODO: Parse query parameters
$queryParams = [];
if (!empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $queryParams);
}

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
            $commentStmt = $db->prepare("SELECT COUNT(*) as comment_count FROM comments WHERE assignment_id = :assignment_id");
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
                        FROM comments c 
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
    // TODO: Validate required fields
    
    
    // TODO: Sanitize input data
    
    
    // TODO: Validate due_date format
    
    
    // TODO: Generate a unique assignment ID
    
    
    // TODO: Handle the 'files' field
    
    
    // TODO: Prepare INSERT query
    
    
    // TODO: Bind all parameters
    
    
    // TODO: Execute the statement
    
    
    // TODO: Check if insert was successful
    
    
    // TODO: If insert failed, return 500 error
    
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
    // TODO: Validate that 'id' is provided in $data

    
    // TODO: Store assignment ID in variable
    
    
    // TODO: Check if assignment exists
    
    
    // TODO: Build UPDATE query dynamically based on provided fields
    
    
    // TODO: Check which fields are provided and add to SET clause
    
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    
    
    // TODO: Complete the UPDATE query
    
    
    // TODO: Prepare the statement
    
    
    // TODO: Bind all parameters dynamically
    
    
    // TODO: Execute the statement
    
    
    // TODO: Check if update was successful
    
    
    // TODO: If no rows affected, return appropriate message
    
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
    // TODO: Validate that $assignmentId is provided and not empty
    
    
    // TODO: Check if assignment exists
    
    
    // TODO: Delete associated comments first (due to foreign key constraint)
    
    
    // TODO: Prepare DELETE query for assignment
    
    
    // TODO: Bind the :id parameter
    
    
    // TODO: Execute the statement
    
    
    // TODO: Check if delete was successful
    
    
    // TODO: If delete failed, return 500 error
    
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
    // TODO: Validate that $assignmentId is provided and not empty
    
    
    // TODO: Prepare SQL query to select all comments for the assignment
    
    
    // TODO: Bind the :assignment_id parameter
    
    
    // TODO: Execute the statement
    
    
    // TODO: Fetch all results as associative array
    
    
    // TODO: Return success response with comments data
    
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
    // TODO: Validate required fields
    
    
    // TODO: Sanitize input data
    
    
    // TODO: Validate that text is not empty after trimming
    
    
    // TODO: Verify that the assignment exists
    
    
    // TODO: Prepare INSERT query for comment
    
    
    // TODO: Bind all parameters
    
    
    // TODO: Execute the statement
    
    
    // TODO: Get the ID of the inserted comment
    
    
    // TODO: Return success response with created comment data
    
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
    // TODO: Validate that $commentId is provided and not empty
    
    
    // TODO: Check if comment exists
    
    
    // TODO: Prepare DELETE query
    
    
    // TODO: Bind the :id parameter
    
    
    // TODO: Execute the statement
    
    
    // TODO: Check if delete was successful
    
    
    // TODO: If delete failed, return 500 error
    
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    
    
    // TODO: Route based on HTTP method and resource type
    
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            
        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            
        } else {
            // TODO: Invalid resource, return 400 error
            
        }
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            
        } else {
            // TODO: Invalid resource, return 400 error
            
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            
        } else {
            // TODO: PUT not supported for other resources
            
        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            
        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            
        } else {
            // TODO: Invalid resource, return 400 error
            
        }
        
    } else {
        // TODO: Method not supported
        
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    
} catch (Exception $e) {
    // TODO: Handle general errors
    
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
    // TODO: Set HTTP response code
    
    
    // TODO: Ensure data is an array
    
    
    // TODO: Echo JSON encoded data
    
    
    // TODO: Exit to prevent further execution
    
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
