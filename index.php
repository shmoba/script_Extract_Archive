<?php
	header('Content-Type: text/html;'); // ; charset=windows-1251
	error_reporting(E_ALL);
	mb_internal_encoding('utf-8');
	define('UPLOAD_DIR', 'upload'); // Константа с папкой загрузки
	/*
	изначально должна выводиться такая форма
	http://joxi.ru/nAypzGRHYNJLN2

	если в поле ввести урл к файлу на яндекс диске или гугл диске, то скрипт должен
	1. его скачать в папку uploads
	2. распакковать если архив
	3. если не архив, то вывести об этом дополнительное сообщение
	4. если все успешно то вывести сообщение
	5. если неудачно, то вы вывести ошибку, особенно если размер большой или нет места
	6. удалить архив

	аналогично, если во второе поле Обзор выбрать архив.

	ССЫЛКА ДЛЯ ТЕСТА прямая:
	https://drive.google.com/uc?export=download&id=1LqOzezKNckfa-PavDkqGUdfONfBR1coW

	№1
		https://drive.google.com/open?id=1LqOzezKNckfa-PavDkqGUdfONfBR1coW
		обрезать ссылку по вопросительный знак
		добавить:
		https://drive.google.com/uc?export=download&
	№2
		https://yadi.sk/d/Vj186ZxO69Yv5Q
		Добавить:
		https://getfile.dokpub.com/yandex/get/ 
	*/
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8" >
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>Закачать и распаковать!</title>
</head>
<body>
	<h2>Адрес архива на Гугл.Диске или Яндекс.Диске</h2>

<?php
 
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR); // Если папки загрузки нет, то создаем ее

function check_type($file_path) { // Проверка на формат
		if (mime_content_type($file_path) != 'application/zip') { 
			unlink($file_path);
			die('<div class="error">Поддерживаются только файлы с расширением zip!<br><a href="index.php">Попробуйте еще раз</a></div>');
		}
	}

function extract_archive($path) { // Распаковать архив
	$zipArchive = new ZipArchive();
	$result = $zipArchive->open($path);
	if ($result === TRUE) {
	    $zipArchive ->extractTo(UPLOAD_DIR);
	    $zipArchive ->close();
	} else {
	    die('<div class="error">Нельзя разархивировать указанный вами файл!<br><a href="index.php">Попробуйте еще раз</a></div>');
	}
}

function upload_path($filename) { // Путь загрузки
	return UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;
}

function check_file_size($file_path) { // Ограничения по размеру архива
	if (filesize($file_path) > disk_free_space('.')) { // Не больше чем места на диске
		unlink($file_path);
		die('<div class="error">Файл больше допустимого размера!<br><a href="index.php">Попробуйте еще раз</a></div>');
	}
	if (filesize($file_path) > 1024*1024*50) { // 50 мегабайт
		unlink($file_path);
		die('<div class="error">Файл больше допустимого размера!<br><a href="index.php">Попробуйте еще раз</a></div>');
	}
}

$context = stream_context_create( // Создаем контекст для file_get_contents
    array(
        'http' => array(
            'follow_location' => true
        )
    )
);

if (!empty($_POST['url'])) { // Определяем значение массива, что не пустое

	
	$url = $_POST['url'];

	if (stripos($_POST['url'], 'yadi.sk')) {
		$url = 'https://getfile.dokpub.com/yandex/get/'.$_POST['url']; // Добавить ссылку файлообменника, другого способа не нашла
	}


	elseif (stripos($_POST['url'], 'drive.google.com/open?id')) {
			$link = explode('?', $url); // Разделить полный путь через '?'
			array_shift($link); // Оставить только часть с id ссылки на архив
			$string = implode($link); // Преобразовать в строку
			$url = 'https://drive.google.com/uc?export=download&'.$string; // Склеить в прямую ссылку
	}


	else {
		echo '<div class="error">Некорректная ссылка.</div>';
	}


	$file = file_get_contents($url, false, $context);


	if ($file != FALSE) { 
		$name = 'tmp_file';
		$ext = pathinfo($url, PATHINFO_EXTENSION);
		$file_path = upload_path($name);
		file_put_contents($file_path, $file);
		check_file_size($file_path);
		check_type($file_path);
		extract_archive($file_path);
		unlink($file_path); // Удалить архив
		echo "<div class='success'>Файл успешно загружен и распакован в <a href='" . UPLOAD_DIR. "'>папку</a><br><a href='index.php'>Распаковать еще один</a></div>";
	} 
	else { die('Не удалось скачать архив по ссылке'); }
} 
	if (!empty($_FILES['filename'])){

		if (is_uploaded_file($_FILES['filename']['tmp_name'])) { // Проверяем загрузился ли файл
			$file_path = upload_path(basename($_FILES['filename']['name'])); // Составляем путь файла
			move_uploaded_file($_FILES['filename']['tmp_name'], $file_path); // Переместить файл в директорию загрузки
			check_file_size($file_path);
			check_type($file_path);
			extract_archive($file_path);
			unlink($file_path);
			echo "<div class='success'>Файл успешно загружен и распакован в <a href='" . UPLOAD_DIR. "'>папку</a><br><a href='index.php'>Распаковать еще один</a></div>";

		}
	}

?>

<form method="post" enctype="multipart/form-data">
		<input name="url" size="50" />
		<button name="go">Закачать и распаковать!</button>

		<h2>или архив ZIP:</h2>

	    <input type="file" name="filename" class="select_file"><br>
	    <button name="go" class="upload">Закачать и распаковать!</button>
	</form>
</body>
</html>


