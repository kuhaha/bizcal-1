<?php
  const DAT_DIR = 'dat';
  include "cal.php";

  use \Symfony\Component\Yaml\Yaml;
  use bizcal\MyCalendar;
  use bizcal\KsHoliday;
  use bizcal\KsCalendar;

  $curr_y = date('n') <4 ? date('Y') -1 : date('Y');
  $year = isset($_GET['y']) ? (int)$_GET['y'] : $curr_y;
  $prev_y = $year - 1;
  $next_y = $year + 1;

  $cal = new MyCalendar();
  $dat_holiday = Yaml::parseFile(DAT_DIR . "/holiday.yaml");
  $holiday1 = new KsHoliday($year, $dat_holiday);
  $holiday2 = new KsHoliday($year+1, $dat_holiday);

?>
<!doctype html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title>KsuCalendar v1.0</title>

    <!-- Bootstrap core CSS -->
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

  </head>
  <body>
  <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
  <symbol id="home" viewBox="0 0 16 16">
    <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4H2.5z"/>
  </symbol>
  <symbol id="table" viewBox="0 0 16 16">
    <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h3a1 1 0 0 0 1-1v-2zm-5 3v-3H6v3h4zm-5 0v-3H1v2a1 1 0 0 0 1 1h3zm-4-4h4V8H1v3zm0-4h4V4H1v3zm5-3v3h4V4H6zm4 4H6v3h4V8z"/>
  </symbol>
  <symbol id="calendar3" viewBox="0 0 16 16">
    <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
    <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
  </symbol>
</svg>   
<header>
  <div class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
      <a href="#" class="navbar-brand d-flex align-items-center">      
      <svg width="20" height="20" fill="currentColor" class="bi bi-table"><use xlink:href="#table"/></svg>
        <strong class="text-info">&nbsp;KsuCalendar</strong>
      </a>
      <div class="d-flex justify-content-right"> 
        <a role="button" class="btn btn-outline-success mx-2" href="?y=<?=$prev_y?>">Last Year</a>
        <a role="button" class="btn btn-outline-primary mx-2" href="?y=<?=$curr_y?>">This Year</a>
        <a role="button" class="btn btn-outline-danger mx-2" href="?y=<?=$next_y?>">Next Year</a>
      </div>
    </div>
  </div>
</header>

<main>

  <div class="album py-5 bg-light">
    <div class="container">
    <div class="row">    
      <h2 class="display-5"><span class="badge bg-success"><?=$year?></span> Business Calendar <span class="badge bg-success">KSU</span></h2>
    </div>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
    <?php
      foreach (range(3,14) as $m){
        $month = $m % 12 + 1;
        $n_year = $year;
        $holiday = $holiday1;
        if ($month < 4) {
            $n_year =  $year + 1;
            $holiday = $holiday2;
        }
        $holidays = $holiday->getHolidays($month);
        $calendar = new KsCalendar($n_year, $month);
        $schedule = [];
        try {
            $schedule = Yaml::parseFile(DAT_DIR . "/schedule/cal{$year}.yaml");
        }catch (Exception $e){
            // echo $e->getMessage();
        } 
        $file = 'assets/img/' . $month . '.jpg';
        echo '<div class="col">' , "\n";
        echo '<div class="card shadow-sm">', "\n"; 
        echo '<img card-img-top" rounded mx-auto d-block width="100%" height="225" src="'.$file.'">';
        echo '<div class="card-body">', "\n";
        echo "<h3>{$month}月</h3>\n";
        list('table'=>$table, 'days'=>$days) =  $cal->getMonth($n_year, $month, $holidays, $schedule);
        echo $table;
        echo '<div class="d-flex justify-content-between align-items-center">', "\n";
        echo '<div class="btn-group">', "\n";
        echo '<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#collapse_month'.$month.'">
  View/Hide</button>';
        // echo '<button type="button" class="btn btn-sm btn-outline-secondary">View</button>', "\n";
        // echo '<button type="button" class="btn btn-sm btn-outline-secondary">Edit</button>', "\n";
        echo '</div>', "\n";
        echo '<small class="text-muted">'. sizeof($days ).' items</small>', "\n";        
        echo '</div>', "\n";
        echo '</div>', "\n";         
        echo '</div>',  "\n";
        echo '<div class="collapse" id="collapse_month'.$month.'">'.implode("；\n", $days).'</div>';
        echo '</div>',  "\n";
      }
    ?>
  </div>
</div>

</main>

<footer class="text-muted py-5">
  <div class="container">
    <p class="float-end mb-1">
      <a href="#">Back to top</a>
    </p>
    <p class="mb-1"><svg width="16" height="16" fill="currentColor" class="bi bi-table"><use xlink:href="#table"/></svg>
        <strong class="text-info">&nbsp;KsuCalendar</strong> is a Business Calendar with rule-based automatical calculation of 
      special days such as national holidays, local business schedules (business days or non-business days). &copy; 2023 Klab, Kyushu Sangyo Univeristy. 
       </p>
       
    <p class="mb-0"><a href="http://www.is.kyusan-u.ac.jp/~chengk/">Visit the developer's homepage</a>.</p>
  </div>
</footer>


    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>

      
  </body>
</html>
