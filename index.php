<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $_ip = $_SERVER['REMOTE_ADDR'];
}

$sort_file = "sort.json";
$orders = json_decode(file_get_contents(__DIR__.'/'.$sort_file));

if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{    
  if ($_POST['ip'] == $_ip){
    $orders->$_ip = $_POST['order'];
    file_put_contents($sort_file, json_encode($orders));
  }else{
    die(json_encode(array('success'=>'false')));
  }
  exit;    
}

function array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
              $sortable_array[$k] = $v->$on;
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

$source_file = "subjects.json";
$data = json_decode(file_get_contents(__DIR__.'/'.$source_file));

if (!isset($orders->$_ip)){
 $orders->$_ip = array();
}

foreach ($orders as $ip => $order) {
  foreach ($order as $index => $value) {
    foreach ($data as $key => $subject) {
      if (intval($subject->id) === intval($value)){
        $subject->count += $index;
      }
    }
  }
}

$sorted = array_sort($data,'count',SORT_ASC);

switch (json_last_error()) {
    case JSON_ERROR_NONE:
        //echo ' - No errors';
    break;
    case JSON_ERROR_DEPTH:
        echo ' - Maximum stack depth exceeded';
    break;
    case JSON_ERROR_STATE_MISMATCH:
        echo ' - Underflow or the modes mismatch';
    break;
    case JSON_ERROR_CTRL_CHAR:
        echo ' - Unexpected control character found';
    break;
    case JSON_ERROR_SYNTAX:
        echo ' - Syntax error, malformed JSON';
    break;
    case JSON_ERROR_UTF8:
        echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
    break;
    default:
        echo ' - Unknown error';
    break;
}



?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ODJ Octobre 2018</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
  <style>
  #sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
  #sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.4em; height: auto; }
  #sortable li i { position: absolute; margin-left: -1.3em; }
  </style>
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script>
  $( function() {
    $( "#sortable" ).sortable();
    $( "#sortable" ).on( "sortstop", function( event, ui ) {
        var sorted = []; //$("#sortable").sortable('toArray',{ attribute: "data-id" });
        $( "#sortable li" ).each(function( index ) {
          sorted[index] = $( this ).data('id');
        });
        console.log(sorted);
        $.ajax({
          method: 'POST',
          data: {'order' : sorted,'ip' : "<?php echo $_ip ?>"},
        });
      } );
    $( "#sortable" ).disableSelection();
  } );
  </script>
</head>
<body>
 triez les sujets celon votre préférence. <br>
 <?php echo count((array)$orders); ?> votants. <br>
<ul id="sortable">
  <?php foreach ($sorted as $key => $value) { ?>
      <li class="ui-state-default" data-id="<?php echo $value->id ?>" data-count="<?php echo $value->count ?>"><i class="fas fa-sort"></i>#<?php echo $value->id ?> <?php echo $value->title ?> {all :<?php echo $value->count + 1?>, you: <?php echo array_search($value->id, $orders->$_ip) + 1; ?>}</li>
  <?php } ?>
</ul>
</body>
</html>
