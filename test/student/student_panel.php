<?php
include "../includes/auth.php";
include "../config.php";

if ($_SESSION['role'] != 'student') {
    die("Доступ запрещен");
}

$user_id = $_SESSION['user_id'];

// Получаем оценки студента
$result = $conn->query("
    SELECT g.grade, g.lesson_date, g.created_at, s.name AS subject
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    JOIN students st ON g.student_id = st.id
    WHERE st.user_id = $user_id
    ORDER BY g.lesson_date DESC
");

// Общий средний балл
$avg_result = $conn->query("
    SELECT AVG(g.grade) AS avg_grade
    FROM grades g
    JOIN students st ON g.student_id = st.id
    WHERE st.user_id = $user_id
");
$avg_grade = $avg_result->fetch_assoc()['avg_grade'];

// Средний балл по каждому предмету
$avg_subject_result = $conn->query("
    SELECT s.name AS subject, AVG(g.grade) AS avg_grade
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    JOIN students st ON g.student_id = st.id
    WHERE st.user_id = $user_id
    GROUP BY g.subject_id
    ORDER BY s.name
");

$avg_subjects = [];
while ($row = $avg_subject_result->fetch_assoc()) {
    $avg_subjects[$row['subject']] = round($row['avg_grade'],2);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Мои оценки</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.container {
    max-width: 950px;
    margin: 30px auto;
    padding: 20px;
    background: #f7f9fc;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}
h2, h3 {
    text-align: center;
    color: #333;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
th {
    background-color: #4CAF50;
    color: white;
}
td.grade-5 { font-weight: bold; }
td.grade-4 {}
td.grade-3 {}
td.grade-2 { font-weight: bold; }
.avg-box {
    margin: 15px 0;
    padding: 10px;
    background-color: #e3f2fd;
    border-left: 5px solid #2196F3;
    font-weight: bold;
}
.progress-bar {
    height: 12px;
    border-radius: 6px;
    background: #ddd;
    overflow: hidden;
}
.progress-bar-fill {
    height: 12px;
    border-radius: 6px;
}
.logout-btn {
    text-decoration:none; 
    color:#fff; 
    background:#2196F3; 
    padding:10px 20px; 
    border-radius:5px; 
    display: inline-block; 
    margin-top: 15px;
}
</style>
</head>
<body>
<div class="container">

<h2>Мои оценки</h2>

<?php if ($avg_grade !== null): ?>
<div class="avg-box">
Общий средний балл: <?= round($avg_grade,2) ?>
</div>
<?php endif; ?>

<?php if ($avg_subjects): ?>
<h3>Средний балл по предметам</h3>
<table>
<tr>
<th>Предмет</th>
<th>Средний балл</th>
</tr>
<?php foreach ($avg_subjects as $subject => $avg): ?>
<tr>
<td><?= htmlspecialchars($subject) ?></td>
<td><?= $avg ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>Все оценки</h3>
<?php if ($result->num_rows == 0): ?>
<p>Оценок пока нет.</p>
<?php else: ?>
<table>
<tr>
    <th>Предмет</th>
    <th>Оценка</th>
    <th>Прогресс</th>
    <th>Дата занятия</th>
    <th>Дата внесения</th>
</tr>
<?php while($row = $result->fetch_assoc()): 
    $grade = (int)$row['grade'];
    $width = ($grade / 5) * 100;

    // Определяем цвет и эмодзи для оценки
    if($grade == 5){ 
    $color = "#4CAF50"; 
    $emoji = '<img src="../images/grades/5.png" alt="Отлично" width="40" class="grade-icon">'; 
    } elseif($grade == 4){ 
        $color = "#8BC34A"; 
        $emoji = '<img src="../images/grades/4.png" alt="Хорошо" width="40" class="grade-icon">'; 
    } elseif($grade == 3){ 
        $color = "#FFEB3B"; 
        $emoji = '<img src="../images/grades/3.png" alt="Удовлетворительно" width="40" class="grade-icon">'; 
    } else { 
        $color = "#F44336"; 
        $emoji = '<img src="../images/grades/2.png" alt="Неуд" width="40" class="grade-icon">'; 
    }


    // Форматируем даты
    $lessonDate = date('d.m.Y', strtotime($row['lesson_date']));
    $createdAt = date('d.m.Y', strtotime($row['created_at']));
?>
<tr>
    <td><?= htmlspecialchars($row['subject']) ?></td>
    <td class="grade-<?= $grade ?>"><?= $grade ?> <?= $emoji ?></td>
    <td>
        <div class="progress-bar">
            <div class="progress-bar-fill" style="width:<?= $width ?>%; background:<?= $color ?>;"></div>
        </div>
    </td>
    <td><?= $lessonDate ?></td>
    <td><?= $createdAt ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php endif; ?>


<a href="../logout.php" class="logout-btn">Выйти</a>

</div>
</body>
</html>



