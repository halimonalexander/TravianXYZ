<?php
/** @var \GameEngine\Building $building */
$loopsame    = (int) ($building->isCurrent($id) || $building->isLoop($id));
$doublebuild = (int) ($building->isCurrent($id) && $building->isLoop($id));
$master = count(
    $this->database
        ->getMasterJobsByField($village->wid,$id)
);
