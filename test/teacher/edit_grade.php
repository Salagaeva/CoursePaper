<?php
include "../includes/auth.php";
include "../config.php";

if ($_SESSION['role'] != 'teacher') die("Доступ запрещен");

$teacher_data = $conn->query("SELECT id FROM teachers WHERE user_id = " . $_SESSION['user_id']);
$teacher_id = $teacher_data->fetch_assoc()['id'];

$grade_id = intval($_GET['id']);

$stmt = $conn->prepare("
SELECT g.grade, g.lesson_date, g.created_at,
       st.full_name, s.name AS subject_name
FROM grades g
JOIN students st ON g.student_id = st.id
JOIN subjects s ON g.subject_id = s.id
WHERE g.id=? AND g.teacher_id=?
");
$stmt->bind_param("ii", $grade_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$grade = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"]=="POST") {

$new_grade = $_POST['grade'];
$new_date = $_POST['lesson_date'];

$update = $conn->prepare("
UPDATE grades SET grade=?, lesson_date=?
WHERE id=? AND teacher_id=?
");
$update->bind_param("isii",$new_grade,$new_date,$grade_id,$teacher_id);
$update->execute();

header("Location: teacher_panel.php");
exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Редактировать оценку</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">

<h2>Редактирование оценки</h2>

<p><strong>Студент:</strong> <?= htmlspecialchars($grade['full_name']) ?></p>
<p><strong>Предмет:</strong> <?= htmlspecialchars($grade['subject_name']) ?></p>
<p><strong>Дата внесения:</strong> <?= $grade['created_at'] ?></p>

<form method="POST">
<label>Оценка:</label>
<input type="number" name="grade" min="2" max="5" value="<?= $grade['grade'] ?>" required>

<label>Дата занятия:</label>
<input type="date" name="lesson_date" value="<?= $grade['lesson_date'] ?>" required>

<button type="submit" class="btn">Сохранить</button>
<a href="teacher_panel.php" class="btn">Назад</a>
</form>

</div>
</body>
</html>

