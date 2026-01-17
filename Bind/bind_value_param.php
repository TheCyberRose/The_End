<?php
/*
    bindValue:
    - يربط القيمة نفسها
    - إذا تغير المتغير بعد الربط → لا يتغير الاستعلام
*/

$pdo = new PDO("mysql:host=localhost;dbname=test;charset=utf8","root","");


$id = 1;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");

$stmt->bindValue(":id", $id, PDO::PARAM_INT);

$id = 5;

$stmt->execute();


$result = $stmt->fetchAll();
print_r($result);
?>


<?php
/*
    bindParam:
    - يربط المتغير وليس القيمة
    - إذا تغير المتغير → يتغير الاستعلام
*/

$pdo = new PDO("mysql:host=localhost;dbname=test;charset=utf8","root","");

$id = 1;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");

$stmt->bindParam(":id", $id, PDO::PARAM_INT);

$id = 5;

$stmt->execute();

$result = $stmt->fetchAll();
print_r($result);
?>
