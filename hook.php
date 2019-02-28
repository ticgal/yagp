<?php
/**
 * Install all necessary elements for the plugin
 *
 * @return boolean True if success
 */
function plugin_yagp_install() {

   $migration = new Migration(PLUGIN_YAGP_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginYagp' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }
   return true;
}

function plugin_yagp_item_stats($item) {
   PluginYagpActualtimeTask::showStats($item);
}

function plugin_yagp_item_update($item) {
   PluginYagpActualtimeTask::preUpdate($item);
}
/**
 * Uninstall previously installed elements of the plugin
 *
 * @return boolean True if success
 */
function plugin_yagp_uninstall() {

   $migration = new Migration(PLUGIN_ACTUALTIME_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginYagp' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall($migration);
         }
      }
   }

   return true;
}