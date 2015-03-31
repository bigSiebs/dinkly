<?php

require_once('config/bootstrap.php');

echo "\n ======================== Generate All Dinkly Models in Schema  ========================\n\n";
    
$options = getopt("hm:s:ip:");

if(isset($options['h']) || $options == array())
{
    echo "   This tool will build all models for a given schema and optionally insert a\n";
    echo "   matching table into the database. If you add additional fields to your schema,\n";
    echo "   you may re-run this script with the insert flag to update your tables.";
    echo "\n";
    echo "   Usage: php tools/gen_models.php [args]\n\n";
    echo "   The available arguments are:\n";
    echo "       -h     Show this help\n";
    echo "       -s     Schema name, in underscore format (required)\n";
    echo "       -s     Schema name, in underscore format (required)\n";
    echo "       -p     Plugin name, in underscore format (optional)\n";
    echo "       -i     Insert SQL (optional)\n";
    echo "\n";
    echo "   Example: php tools/gen_models.php -s=monkey_tail -p=tail_extensions -i\n";
    
    echo "\n =======================================================================================\n\n";
    exit;
}

if(!isset($options['s']))
{
	echo "\nPlease use the -s flag to indicate which schema set to use.\n\n";
	die();
}

if(!isset($options['m']))
{
	echo "\nPlease use the -m flag to indicate the desired model name to use.\n\n";
	die();
}

$plugin_name = null;
if(isset($options['p'])) { $plugin_name = $options['p']; }

$insert_sql = false;
if(isset($options['i'])) { $insert_sql = true; }

DinklyBuilder::buildAllModels($options['s'], $insert_sql, $plugin_name);