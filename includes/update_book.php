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

$book_id = $_POST['book_id'] ?? 0;
$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$isbn = $_POST['isbn'] ?? '';
$category = $_POST['category'] ?? '';
$publisher = $_POST['publisher'] ?? '';
$published_year = $_POST['published_year'] ?? null;
$total_copies = $_POST['total_copies'] ?? 1;
$copies_available = $_POST['copies_available'] ?? 0;
$location = $_POST['location'] ?? '';
$description = $_POST['description'] ?? '';

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}


if ($copies_available > $total_copies) {
    echo json_encode(['success' => false, 'message' => 'Available copies cannot exceed total copies']);
    exit;
}

$conn = getDBConnection();


$check_query = "SELECT id FROM books WHERE isbn = ? AND id != ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("si", $isbn, $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A book with this ISBN already exists']);
    exit;
}


$cover_image = null;
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../assets/images/books/';
    $file_name = time() . '_' . basename($_FILES['cover_image']['name']);
    $file_path = $upload_dir . $file_name;
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($_FILES['cover_image']['tmp_name']);
    
    if (in_array($file_type, $allowed_types) && move_uploaded_file($_FILES['cover_image']['tmp_name'], $file_path)) {
        $cover_image = $file_name;
    }
}


$update_fields = "title = ?, author = ?, isbn = ?, category = ?, publisher = ?, 
                  published_year = ?, total_copies = ?, copies_available = ?, 
                  location = ?, description = ?";
$params = [$title, $author, $isbn, $category, $publisher, $published_year, 
           $total_copies, $copies_available, $location, $description];
$types = "sssssiisss";

if ($cover_image) {
    $update_fields .= ", cover_image = ?";
    $params[] = $cover_image;
    $types .= "s";
}

$update_fields .= ", updated_at = NOW()";

$params[] = $book_id;
$types .= "i";

$update_query = "UPDATE books SET $update_fields WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update book: ' . $conn->error]);
}

$conn->close();
?>