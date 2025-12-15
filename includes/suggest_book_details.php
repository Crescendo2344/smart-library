<?php

require_once 'config.php';
require_once 'auth.php';



if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}


$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$publisher = isset($_POST['publisher']) ? trim($_POST['publisher']) : null;
$year = isset($_POST['year']) ? intval($_POST['year']) : null;
$pages = isset($_POST['pages']) ? intval($_POST['pages']) : null;
$language = isset($_POST['language']) ? trim($_POST['language']) : null;


if (empty($description) && empty($publisher) && empty($year) && empty($pages) && empty($language)) {
    echo json_encode(['success' => false, 'message' => 'Please provide at least one suggestion']);
    exit;
}

$conn = getDBConnection();


$create_table = "CREATE TABLE IF NOT EXISTS book_suggestions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    description TEXT,
    publisher VARCHAR(255),
    published_year INT,
    total_pages INT,
    language VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,
    reviewer_id INT,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    INDEX idx_book_status (book_id, status)
)";

$conn->query($create_table);


$insert_query = "INSERT INTO book_suggestions 
                 (user_id, book_id, description, publisher, published_year, total_pages, language) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iissiis", $user_id, $book_id, $description, $publisher, $year, $pages, $language);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your suggestion! Library staff will review it.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save suggestion: ' . $conn->error]);
}

$conn->close();
?>