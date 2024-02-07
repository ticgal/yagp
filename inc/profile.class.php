<?php

/**
*/

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginYagpProfile extends Profile
{
    public static $rightname = "profile";

    public const SEE_GROUP_TICKETS_ONLY = 1;

    /**
     * getTabNameForItem
     *
     * @param  mixed $item
     * @param  mixed $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        switch ($item->getType()) {
            case 'Profile':
                return self::createTabEntry('YAGP');
                break;
        }

        return '';
    }

    /**
     * displayTabContentForItem
     *
     * @param  mixed $item
     * @param  mixed $tabnum
     * @param  mixed $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): void
    {
        switch ($item->getType()) {
            case 'Profile':
                $profile = new self();
                $profile->showForm($item->getID());
                break;
        }
    }

    /**
     * showForm
     *
     * @param  mixed $profiles_id
     * @param  mixed $options
     * @return void
     */
    public function showForm($profiles_id, $options = []): void
    {
        if (!Session::haveRight("profile", READ)) {
            return;
        }
        $canedit = Session::haveRight("profile", UPDATE);

        $profile = new Profile();
        $profile->getFromDB($profiles_id);

        echo "<form action='" . Profile::getFormUrl() . "' method='post'>";

        $general_rights = self::getGeneralRights();
        $matrix_options = [
            'canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => 'Yet Another GLPI Plugin'
        ];

        $profile->displayRightsChoiceMatrix($general_rights, $matrix_options);
        $profile->showLegend();

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>\n";
            Html::closeForm();
        }
        echo "</div>";
    }

    /**
     * getGeneralRights
     *
     * @return array
     */
    public static function getGeneralRights(): array
    {
        $crud = [
            self::SEE_GROUP_TICKETS_ONLY => __("See group tickets only", 'yagp')
        ];

        $rights = [
            'yagp' => [
                'rights'    => $crud,
                'itemtype'  => self::getType(),
                'label'     => __("Ticket"),
                'field'     => 'plugin_yagp_tickets'
            ]
        ];

        return $rights;
    }

    /**
     * uninstall
     *
     * @return void
     */
    public static function uninstall(): void
    {
        global $DB;

        $table = ProfileRight::getTable();
        $query = "DELETE FROM $table WHERE `name` LIKE '%plugin_yagp%'";
        $DB->query($query) or die($DB->error());
    }
}
