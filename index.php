<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$DIR = __DIR__.'/data/';

if (!is_dir($DIR)){
  die ("local dir $DIR does not exist");
}
$testfilename = md5(rand(10^5,10^6)).'.txt';
$test = @file_put_contents($DIR.$testfilename, 'test');
@unlink($DIR.$testfilename);
if (!$test){ // $DIR is not writtable
  die ("local dir $DIR is not writable");
}
if (!is_file('conf.php')){
  if (!copy('conf.php.sample', 'conf.php')) {
    die("La copie de conf.php.sample vers conf.php a échoué...\n");
  }
}
include('conf.php');

if (isset($_POST['user'])) {
  $_SESSION['user'] = $_POST['user'];
}

if (!isset($_SESSION['user'])){
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $_ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
      $_ip = $_SERVER['REMOTE_ADDR'];
  }
  $_SESSION['user'] = md5($_ip);
}else if (isset($_POST['user_change'])) {
  $_SESSION['user'] = md5(time()); //new user
}

$_user = $_SESSION['user'];


if (!is_file($DIR.$users_file)){
  $test = @file_put_contents($DIR.$users_file, json_encode(array()),LOCK_EX);
  if (!$test)
    die ("$users_file file do not exist and cannot be created");
}

if (!is_file($DIR.$sort_file)){
  $test = @file_put_contents($DIR.$sort_file, json_encode(array()),LOCK_EX);
  if (!$test)
    die ("$sort_file file do not exist and cannot be created");
}

$_users = json_decode(file_get_contents($DIR.$users_file));
if (!is_object($_users)){
  $_users = json_decode('{}');
}

$orders = json_decode(file_get_contents($DIR.$sort_file));
if (!is_object($orders)){
  $orders = json_decode('{}');
}

if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{    
    if (isset($_POST['order'])){
      $orders->$_user = $_POST['order'];
      file_put_contents($sort_file, json_encode($orders),LOCK_EX);
    }else if (isset($_POST['name'])) {
      $_users->$_user = $_POST['name'];
      file_put_contents($users_file, json_encode($_users),LOCK_EX);
    }else if (isset($_POST['user'])) {
      $_SESSION['user'] = $_POST['user'];
      $_user = $_SESSION['user'];
    }
    exit;    
}

function toAlpha($num){
    return chr(substr("000".($num+65),-3));
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

if (!file_exists($DIR.$source_file)){
  die("$source_file does not exist; please create a list of subjects to sort first");
}

$data = json_decode(file_get_contents($DIR.$source_file));

if (!isset($orders->$_user)){
 $orders->$_user = array();
}

foreach ($orders as $user => $order) {
  foreach ($order as $index => $value) {
    foreach ($data as $key => $subject) {
      if (intval($subject->id) === intval($value)){
        $subject->count += $index;
      }
    }
  }
}

$sorted = array_sort($data,'count',SORT_ASC);
$subjects = array();
foreach ($data as $key => $value) {
  $subjects[$value->id] = $value;
}
$my_oder = $orders->$_user;

if (!$my_oder)
  $my_oder = $sorted;

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

function scalecolor($key,$nb_of_subject){
  $r = 46;
  $g = 204;
  $b = 113;
  $q = $key/$nb_of_subject;
  return "rgba(".intval($r - $r*$q).",". intval($g - $g*$q).",". intval($b - $b*$q).",0.5)";
}


?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Priorisation <?php echo explode('.', $source_file)[0]; ?></title>
   <!-- Compiled and minified CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
  <!-- Compiled and minified JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
  <style>
  #sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
  #sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1em; height: auto; }
  #sortable li i { position: absolute; margin-left: -1.3em; }
  #sortable li.ui-state-highlight { height: 1.5em; line-height: 1.2em; }
  </style>
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script src="jquery.ui.touch-punch.min.js"></script>
  <script>
  $( function() {
    $( "#sortable" ).sortable({placeholder: "ui-state-highlight"});
    $( "#sortable" ).on( "sortstop", function( event, ui ) {
        var sorted = []; //$("#sortable").sortable('toArray',{ attribute: "data-id" });
        $( "#sortable li" ).each(function( index ) {
          sorted[index] = $( this ).data('id');
        });
        console.log(sorted);
        $.ajax({
          method: 'POST',
          data: {'order' : sorted},
        });
        $("#message").html('enregistré').show().hide(1000,function(){
           $("#message").html('');
        });
      } );
    $( "#sortable" ).disableSelection();
    $("#new_name").on('submit',function(e){
      e.preventDefault();
      $.ajax({
          method: 'POST',
          data: {'name' : $('input[name="name"]').val()},
        });
      $('#name').hide();
      $('#myname').html($('input[name="name"]').val());
      $('#sort').show();
    });
    $(".switcher").on('click',function(e){
      e.preventDefault();
      $(".switcher").toggleClass('disabled');
      $(".ordered-list").toggle();
    });
  } );
  </script>
</head>
<body>
  <div class="container">
    <?php if (!isset($_users->$_user)) : ?>
    <div id="name">
        Je suis : 
        <div class="row">
          <?php foreach ($_users as $id => $name) { ?>
            <?php if ($name) : ?>
            <form method="POST" action="" class="col">
              <input type="hidden" name="user" value="<?php echo $id; ?>">
              <input type="submit" value="<?php echo $name; ?>" class="users btn orange" />
            </form>
            <?php endif; ?>
          <?php } ?>
        </div>
        <form id="new_name">
          <?php if (count($_users) > 0): ?>
        Quelqu'un d'autre : <br><?php endif; ?>
        <input type="text" name="name" placeholder="mon prénom" />
        <input type="submit" name="ok" value="ok" class="btn btn-lg"/>
      </form>
    </div>
    <div id="sort" style="display: none">
    <?php else: ?>
      <div id="sort">
    <?php endif; ?>
    Bonjour <span id="myname"><?php echo $_users->$_user; ?></span> [<a href="javascript:$('#changeuser').submit();">ce n'est pas moi</a>]
      <form method="POST" action="" id="changeuser"><input type="hidden" name="user_change" value="true"></form>
      <h5>Trie les sujets selon ta préférence.</h2>
      <p><?php echo count((array)$orders); ?> votants. 
        <br>La couleur correspond à <b>ton</b> ordre.
      </p>
     <a href="#!" class="btn disabled switcher"><i class="fas fa-user left"></i><span class="hide-on-small-only">voir </span>mon classement</a>
     <a href="#!" class="btn switcher"><i class="fas fa-users left"></i><span class="hide-on-small-only">voir le </span>classement général</a>
     <br>
     <br>
     <?php $nb_of_subject = count($sorted); ?>
      <ul class="all ordered-list" style="display:none" >
        <?php foreach ($sorted as $key => $value) { ?>
            <li  
            style="background-color: <?php echo scalecolor($key,$nb_of_subject); ?>;">
              #<?php echo toAlpha($value->id) ?> <?php echo $value->title ?> <!--{all :<?php echo $value->count + 1?>, you: <?php echo array_search($value->id, $orders->$_user) + 1; ?>}-->
            </li>
        <?php } ?>
      </ul>
      <div class="myself ordered-list">
        <div id="message" class="green-text" style="display: none;">
        </div>
        <ul id="sortable" >
        <?php foreach ($my_oder as $key => $value) { ?>
            <li
            class="ui-state-default" 
            data-id="<?php echo $subjects[$value]->id ?>" 
            data-count="<?php echo $subjects[$value]->count ?>"
            style="background-color: <?php echo scalecolor($key,$nb_of_subject); ?>;">
            <i class="fas fa-sort"></i>
              #<?php echo toAlpha($subjects[$value]->id) ?> <?php echo $subjects[$value]->title ?> <!--{all :<?php echo $subjects[$value]->count + 1?>, you: <?php echo $key + 1; ?>}-->
            </li>
        <?php } ?>
        </ul>
        <a href=""><i class="fas fa-sync"></i>&nbsp;rafraichir la page</a>
      </div>
    </div>
 </div>
</body>
</html>
