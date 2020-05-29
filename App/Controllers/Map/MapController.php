<?php

namespace App\Controllers\Map;

use App\Helpers\ResponseHelper;
use App\Helpers\TraceHelper;
use App\Routes;
use GameEngine\Building;
use GameEngine\Session;
use HalimonAlexander\Registry\Registry;

class MapController extends AbstractMapController
{
    public function displayMapAction()
    {
        $this->oldMap(
            (Registry::getInstance())->get('building')
        );

        $this->loadTemplate('map', [
            'start'      => TraceHelper::getTimer(),
            'building'   => (Registry::getInstance())->get('building'),
            'database'   => (Registry::getInstance())->get('database'),
            'generator'  => (Registry::getInstance())->get('generator'),
            'message'    => (Registry::getInstance())->get('message'),
            'session'    => (Registry::getInstance())->get('session'),
            'technology' => (Registry::getInstance())->get('technology'),
            'village'    => (Registry::getInstance())->get('village'),
        ]);
    }

    private function oldMap(Building $building)
    {
        if (isset($_GET['z']) && !is_numeric($_GET['z']))
            die('Hacking Attempt');

        if (isset($_GET['newdid'])) {
            $_SESSION['wid'] = $_GET['newdid'];

            if (isset($_GET['d']) && isset($_GET['c'])) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?d=" . preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET['d']) . "&c=" . preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET['c']));
            } else if (isset($_GET['d'])) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?d=" . preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET['d']));
            } else {
                header("Location: " . $_SERVER['PHP_SELF']);
            }
        } else {
            $building->procBuild($_GET);
        }
    }

    public function displayBigMapAction()
    {
        $this->oldBigMap(
            (Registry::getInstance())->get('building'),
            (Registry::getInstance())->get('session')
        );

        $this->loadTemplate('bigMap', [
            'start'      => TraceHelper::getTimer(),
            'building'   => (Registry::getInstance())->get('building'),
            'message'    => (Registry::getInstance())->get('message'),
            'session'    => (Registry::getInstance())->get('session'),
            'technology' => (Registry::getInstance())->get('technology'),
            'village'    => (Registry::getInstance())->get('village'),
        ]);
    }

    private function oldBigMap(Building $building, Session $session)
    {
        $building->procBuild($_GET);
        if (!$session->plus) {
            ResponseHelper::redirect(Routes::MAP);
        }
    }
}
