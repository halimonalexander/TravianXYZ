<div id="build" class="gid25">
  <?php include("25_menu.php"); ?>

  <p><?=RESIDENCE_CULTURE_DESC?></p>
  
  <table cellpadding="1" cellspacing="1" id="build_value">
    <tr>
      <th><?=PRODUCTION_POINTS?></th>
    <?php if($this->database->getVillageField($village->wid, 'natar') == 0){ ?>
      <td><b><?=$this->database->getVillageField($village->wid, 'cp')?></b> <?=POINTS_DAY; ?></td>
    <?php }else{ ?>
      <td><b>0</b> <?=POINTS_DAY?></td>
    <?php } ?>
    </tr>
    <tr>
      <th><?=PRODUCTION_ALL_POINTS?></th>
      <td><b><?=$this->database->getVSumField($session->uid, 'cp')?></b> <?=POINTS_DAY?></td>
    </tr>
  </table>
  
  <p>
      <?=VILLAGES_PRODUCED?> <b><?=$session->cp?></b> <?=POINTS_NEED?>
      <b><?php
          $total = count($this->database->getProfileVillages($session->uid));
          $cp = \App\Helpers\GlobalVariablesHelper::getVillageExpansionCulturePoints(CP);
          echo $cp[$total+1];
          ?></b> <?=POINTS; ?>.</p>
</div>
