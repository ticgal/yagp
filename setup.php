<?php
define ('PLUGIN_YAGP_VERSION', '1.0.1');
// Minimal GLPI version, inclusive
define("PLUGIN_YAGP_MIN_GLPI", "9.3.0");
// Maximum GLPI version, exclusive
define("PLUGIN_YAGP_MAX_GLPI", "9.5");

function plugin_version_yagp() {
   return ['name'       => 'yagp',
      'version'        => PLUGIN_YAGP_VERSION,
      'author'         => '<a href="https://tic.gal">TICgal</a>',
      'homepage'       => 'https://tic.gal',
      'license'        => 'GPLv3+',
      'minGlpiVersion' => PLUGIN_YAGP_MIN_GLPI,
      'requirements'   => [
         'glpi'   => [
            'min' => PLUGIN_YAGP_MIN_GLPI,
            'max' => PLUGIN_YAGP_MAX_GLPI,
         ]
      ]];
}

/**
 * Check plugin's prerequisites before installation
 */
function plugin_yagp_check_prerequisites() {
   return true;
}

/**
 * Check plugin's config before activation
 */
function plugin_yagp_check_config($verbose = false) {
   return true;
}

function plugin_init_yagp() {
   global $PLUGIN_HOOKS;

   if (Session::haveRightsOr("config", [READ, UPDATE])) {
      Plugin::registerClass('PluginYagpConfig', ['addtabon' => 'Config']);
      $PLUGIN_HOOKS['config_page']['yagp'] = 'front/config.form.php';
   }
   $PLUGIN_HOOKS['csrf_compliant']['yagp'] = true;

   $PLUGIN_HOOKS['pre_item_update']['yagp'] = [
      'PluginYagpConfig'  => 'plugin_yagp_updateitem'
   ];

   $plugin=new Plugin();
   if ($plugin->isActivated('yagp')) {
      $config= PluginYagpConfig::getConfig();
      if ($config->fields['fixedmenu']) {
         $PLUGIN_HOOKS['add_css']['yagp']='fixedmenu.css';
      }
      if ($config->fields['gototicket']) {
         $PLUGIN_HOOKS['add_javascript']['yagp']='js/gototicket.js';
      }
   }

}
