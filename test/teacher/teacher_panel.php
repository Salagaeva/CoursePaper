<?php
include "../includes/auth.php";
include "../config.php";

if ($_SESSION['role'] != 'teacher') {
    die("Доступ запрещен");
}

$teacher_data = $conn->query("SELECT id FROM teachers WHERE user_id = " . $_SESSION['user_id']);
$teacher_id = $teacher_data->fetch_assoc()['id'];

$teacher_subjects = $conn->query("
    SELECT ts.subject_id, s.name AS subject_name
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.teacher_id = $teacher_id
");

$allowed_subjects = [];
while ($row = $teacher_subjects->fetch_assoc()) {
    $allowed_subjects[$row['subject_id']] = $row['subject_name'];
}

/* ===== ФИЛЬТРЫ ===== */
$subject_filter = $_GET['subject'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = "WHERE g.teacher_id = $teacher_id";

if ($subject_filter && isset($allowed_subjects[$subject_filter])) {
    $where .= " AND g.subject_id = " . intval($subject_filter);
}

if ($date_from) {
    $where .= " AND g.lesson_date >= '$date_from'";
}

if ($date_to) {
    $where .= " AND g.lesson_date <= '$date_to'";
}

/* ===== УДАЛЕНИЕ ===== */
if (isset($_GET['delete_grade'])) {
    $grade_id = intval($_GET['delete_grade']);
    $conn->query("DELETE FROM grades WHERE id = $grade_id AND teacher_id = $teacher_id");
    header("Location: teacher_panel.php");
    exit();
}

/* ===== ОЦЕНКИ ===== */
$grades = $conn->query("
    SELECT g.id AS grade_id,
           st.full_name,
           s.name AS subject_name,
           g.grade,
           g.lesson_date,
           g.created_at
    FROM grades g
    JOIN students st ON g.student_id = st.id
    JOIN subjects s ON g.subject_id = s.id
    $where
    ORDER BY g.lesson_date DESC
");

/* ===== СРЕДНИЙ БАЛЛ ===== */
$avg_query = $conn->query("
    SELECT s.name AS subject_name, AVG(g.grade) AS avg_grade
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    WHERE g.teacher_id = $teacher_id
    GROUP BY g.subject_id
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Панель преподавателя</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">

<h2>Панель преподавателя</h2>

<a href="add_grade.php" class="btn">Добавить оценку</a>
<a href="../logout.php" class="btn">Выйти</a>

<h3>Фильтр</h3>
<form method="GET">

<label>Предмет:</label>
<select name="subject">
<option value="">Все</option>
<?php foreach ($allowed_subjects as $id => $name): ?>
<option value="<?= $id ?>" <?= $subject_filter==$id?'selected':'' ?>>
<?= htmlspecialchars($name) ?>
</option>
<?php endforeach; ?>
</select>

<label>С:</label>
<input type="date" name="date_from" value="<?= $date_from ?>">

<label>По:</label>
<input type="date" name="date_to" value="<?= $date_to ?>">

<button type="submit" class="btn">Применить</button>
</form>

<h3>Средний балл по предметам</h3>
<table>
<tr><th>Предмет</th><th>Средний балл</th></tr>
<?php while($avg = $avg_query->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($avg['subject_name']) ?></td>
<td><?= round($avg['avg_grade'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h3>Оценки студентов</h3>
<table>
<tr>
    <th>Студент</th>
    <th>Предмет</th>
    <th>Оценка</th>
    <th>Дата занятия</th>
    <th>Дата внесения</th>
    <th>Действие</th>
</tr>

<?php while($g = $grades->fetch_assoc()): 
    // Форматируем даты
    $lessonDate = date('d.m.Y', strtotime($g['lesson_date']));
    $createdAt = date('d.m.Y', strtotime($g['created_at']));
?>
<tr>
    <td><?= htmlspecialchars($g['full_name']) ?></td>
    <td><?= htmlspecialchars($g['subject_name']) ?></td>
    <td><?= $g['grade'] ?></td>
    <td><?= $lessonDate ?></td>
    <td><?= $createdAt ?></td>
    <td>
        <a href="edit_grade.php?id=<?= $g['grade_id'] ?>">Редактировать</a> |
        <a href="?delete_grade=<?= $g['grade_id'] ?>" onclick="return confirm('Удалить?')">Удалить</a>
    </td>
</tr>
<?php endwhile; ?>
</table>


</table>

</div>
</body>
</html>

