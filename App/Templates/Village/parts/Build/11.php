<?php
include("next.php");
$bid = \App\Helpers\GlobalVariablesHelper::getBuilding(11);
?>
<div id="build" class="gid11">
  <a href="#" onClick="return Popup(11,4);" class="build_logo">
    <img class="building g11" src="/img/x.gif" alt="Granary" title="<?php echo GRANARY; ?>" />
  </a>

  <h1><?php echo GRANARY; ?> <span class="level"><?php echo LEVEL; ?> <?php echo $village->resarray['f'.$id]; ?></span></h1>

  <p class="build_desc"><?php echo GRANARY_DESC; ?></p>
  
    <table cellpadding="1" cellspacing="1" id="build_value">
    <tr>
        <th><?php echo CURRENT_CAPACITY; ?></th>
        <td><b><?php echo $bid[$village->resarray['f'.$id]]['attri']*STORAGE_MULTIPLIER; ?></b> <?php echo CROP_UNITS; ?></td>
    </tr>
    
    <tr>
        <?php if(!$building->isMax($village->resarray['f'.$id.'t'],$id)) {
            $next = $village->resarray['f'.$id]+1+$loopsame+$doublebuild+$master;
            if ($next<=20) { ?>
                <th><?php echo CAPACITY_LEVEL; ?> <?php echo $next ?>:</th>
                <td><b><?php echo $bid[$next]['attri']*STORAGE_MULTIPLIER; ?></b> <?php echo CROP_UNITS; ?></td>
            <?php } else { ?>
                <th><?php echo CAPACITY_LEVEL; ?> 20:</th>
                <td><b><?php echo $bid[20]['attri']*STORAGE_MULTIPLIER; ?></b> <?php echo CROP_UNITS; ?></td>
            <?php }
        } ?>
    </tr>
    </table>
<?php
include("upgrade.php");
?>
</p></div>
