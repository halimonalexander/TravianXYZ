<div id="content" class="village1">
    <h1><?php echo ANNOUNCEMENT; ?></h1>

    <h3>Hi <?php echo $session->username; ?>,</h3>
    <?php include("Templates/text.tpl"); ?>
    <div class="c1">
        <h3><a href="<?=\App\Routes::DORF1?>?ok">&raquo; <?php echo GO2MY_VILLAGE; ?></a></h3>
    </div>
</div>