<div id="build" class="gid25">
  <?php include("25_menu.php");?>

  <?php
  if ($village->resarray['f'.$id] >= 10){
    include("25_train.tpl");
  } else{
    echo '<div class="c">'.RESIDENCE_TRAIN_DESC.'</div>';
  }
  ?>
</div>
