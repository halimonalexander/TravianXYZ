<div id="build" class="gid25">
  <?php include("25_menu.php");?>

  <p class="build_desc">
    <a href="#" onClick="return Popup(25,4, 'gid');" class="build_logo">
      <img
              class="building g25"
              src="/img/x.gif" alt="Residence"
              title="<?=RESIDENCE?>" />
    </a>
    <?=RESIDENCE_DESC?>
  </p>

  <?php
  include("upgrade.php");

  if ($village->capital == 1) {
    echo "<p class=\"act\">".CAPITAL."</p>";
  }
  ?>
</div>
