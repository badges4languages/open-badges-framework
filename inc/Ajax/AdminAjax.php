<?php

namespace Inc\Ajax;

use Inc\Base\BaseController;
use Inc\Database\DbBadge;
use Inc\Utils\DisplayFunction;
use Inc\Utils\JsonManagement;
use templates\SingleBadgeTemp;

/**
 *
 *
 * @author      Alessandro RICCARDI
 * @since       1.0.0
 *
 * @package     OpenBadgesFramework
 */
class AdminAjax extends BaseController {

    /**
     *
     *
     * @author Alessandro RICCARDI
     * @since  1.0.0
     */
    public function ajaxShowBadgesTable() {
        echo DisplayFunction::badgesTable();

        wp_die();
    }

    /**
     *
     *
     * @author Alessandro RICCARDI
     * @since  1.0.0
     */
    public function ajaxDeleteBadge() {
        $ids = $_POST['ids'];
        $badges = array();

        foreach ($ids as $id) {
            $badges[] = DbBadge::getById($id);
        }

        foreach ($badges as $badge) {
            echo JsonManagement::deleteJson($badge->json);
            echo DbBadge::deleteById(array('id' => $badge->id));
        }

        wp_die();
    }

    public function ajaxShowBadge() {
        $id = $_POST['id'];
        SingleBadgeTemp::showDatabaseBadge($id);

        wp_die();
    }
}