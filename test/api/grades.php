<?php
include "../config.php";
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!isset($_GET['student_id'])) {
        echo json_encode(["error" => "student_id required"]);
        exit;
    }

    $student_id = intval($_GET['student_id']);

    $stmt = $conn->prepare("
        SELECT 
            s.name AS subject,
            g.grade,
            g.lesson_date,
            g.created_at
        FROM grades g
        JOIN subjects s ON g.subject_id = s.id
        WHERE g.student_id = ?
        ORDER BY g.lesson_date DESC
    ");

    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $grades = [];

    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }

    echo json_encode($grades, JSON_UNESCAPED_UNICODE);
}
