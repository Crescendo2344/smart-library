<?php
require_once 'config.php';
require_once 'auth.php';



if ($_SESSION['role'] !== 'librarian') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$isbn = $_POST['isbn'] ?? '';
$category = $_POST['category'] ?? '';
$publisher = $_POST['publisher'] ?? '';
$published_year = $_POST['published_year'] ?? null;
$total_copies = $_POST['copies'] ?? 1;
$location = $_POST['location'] ?? '';
$description = $_POST['description'] ?? '';


if (empty($title) || empty($author) || empty($isbn) || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

$conn = getDBConnection();


$check_query = "SELECT id FROM books WHERE isbn = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("s", $isbn);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A book with this ISBN already exists']);
    exit;
}


$cover_image = 'default-book.jpg';
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../assets/images/books/';
    $file_name = time() . '_' . basename($_FILES['cover_image']['name']);
    $file_path = $upload_dir . $file_name;
    
 
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($_FILES['cover_image']['tmp_name']);
    
    if (in_array($file_type, $allowed_types)) {
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $file_path)) {
            $cover_image = $file_name;
        }
    }
}


$insert_query = "INSERT INTO books (title, author, isbn, category, publisher, published_year, 
                  total_copies, copies_available, location, description, cover_image, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("sssssiissss", $title, $author, $isbn, $category, $publisher, $published_year, 
                  $total_copies, $total_copies, $location, $description, $cover_image);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Book added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add book: ' . $conn->error]);
}

$conn->close();
?>