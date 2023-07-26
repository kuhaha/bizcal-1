<?php
use bizcal\MyCalendar;
include "cal.php";
$yr =  date('m')>3 ? date('Y') : date('Y')-1 ;
if (isset($_GET['y'])){
	$yr = $_GET['y'];
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="css/style.css" rel="stylesheet" type="text/css" media="all" />
<title><?=$yr?>年度カレンダー</title>
</head>
<body>
<?php
	$cal = new MyCalendar();
	$cal->printYear($yr);
?>
</body>
</html>