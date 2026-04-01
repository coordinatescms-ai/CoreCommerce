<?php 
namespace App\Core\Plugin; 
class PluginManager
{
    public static function load()
    {
        foreach (glob(__DIR__.'/../../../plugins/*/plugin.php') as $plugin) {
            require $plugin;
        }
    }
}