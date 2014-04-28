<?php
/**
*
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (php_sapi_name() != 'cli')
{
	die("This program must be run from the command line.\n");
}

$phpEx = substr(strrchr(__FILE__, '.'), 1);
$phpbb_root_path = __DIR__ . '/../';

function usage()
{
	echo "Usage: export_events_for_wiki.php COMMAND [EXTENSION]\n";
	echo "\n";
	echo "COMMAND:\n";
	echo "    all:\n";
	echo "        Generate the complete wikipage for https://wiki.phpbb.com/Event_List\n";
	echo "\n";
	echo "    php:\n";
	echo "        Generate the PHP event section of Event_List\n";
	echo "\n";
	echo "    adm:\n";
	echo "        Generate the ACP Template event section of Event_List\n";
	echo "\n";
	echo "    styles:\n";
	echo "        Generate the Styles Template event section of Event_List\n";
	echo "\n";
	echo "EXTENSION (Optional):\n";
	echo "    If not given, only core events will be exported.\n";
	echo "    Otherwise only events from the extension will be exported.\n";
	echo "\n";
	exit(2);
}

function validate_argument_count($arguments, $count)
{
	if ($arguments <= $count)
	{
		usage();
	}
}

validate_argument_count($argc, 1);

$action = $argv[1];
$extension = isset($argv[2]) ? $argv[2] : null;
require __DIR__ . '/../phpbb/event/php_exporter.' . $phpEx;
require __DIR__ . '/../phpbb/event/md_exporter.' . $phpEx;
require __DIR__ . '/../phpbb/event/recursive_event_filter_iterator.' . $phpEx;
require __DIR__ . '/../phpbb/recursive_dot_prefix_filter_iterator.' . $phpEx;

switch ($action)
{
	case 'all':
		echo '__FORCETOC__' . "\n";

	case 'php':
		$exporter = new \phpbb\event\php_exporter($phpbb_root_path);
		$exporter->crawl_phpbb_directory_php($extension);
		echo $exporter->export_events_for_wiki();

		if ($action === 'php')
		{
			break;
		}
		echo "\n";
		// no break;

	case 'styles':
		$exporter = new \phpbb\event\md_exporter($phpbb_root_path);
		$exporter->crawl_phpbb_directory_styles('docs/events.md', $extension);
		echo $exporter->export_events_for_wiki();

		if ($action === 'styles')
		{
			break;
		}
		echo "\n";
		// no break;

	case 'adm':
		$exporter = new \phpbb\event\md_exporter($phpbb_root_path);
		$exporter->crawl_phpbb_directory_adm('docs/events.md', $extension);
		echo $exporter->export_events_for_wiki();

		if ($action === 'all')
		{
			echo "\n" . '[[Category:Events and Listeners]]' . "\n";
		}
	break;

	default:
		usage();
}
