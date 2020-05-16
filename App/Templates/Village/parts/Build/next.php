<?php
/** @var \GameEngine\Building $building */
$loopsame = ($building->isCurrent($id) || $building->isLoop($id))?1:0;
$doublebuild = ($building->isCurrent($id) && $building->isLoop($id))?1:0;
$master = count($this->database->getMasterJobsByField($village->wid,$id));
