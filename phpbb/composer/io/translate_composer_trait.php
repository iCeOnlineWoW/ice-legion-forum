<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbb\composer\io;

use phpbb\language\language;
use Symfony\Component\Console\Output\OutputInterface;

trait translate_composer_trait
{
	/**
	 * @var language
	 */
	protected $language;

	/**
	 * @var array
	 */
	protected $composer_error;

	/**
	 * {@inheritdoc}
	 */
	public function write($messages, $newline = true)
	{
		$messages = (array) $messages;
		$translated_messages = [];

		foreach ($messages as $message)
		{
			$lang_key = $message;
			$parameters = [];
			$level = 0;

			$message = trim(strip_tags($message), "\n\r");

			if (strpos($message, 'Deleting ') === 0)
			{
				$elements = explode(' ', $message);
				$lang_key = 'COMPOSER_DELETING';
				$parameters = [$elements[1]];
			}
			else
			{
				dump('WRITE | ' . $message);
			}

			//$translated_message = $this->language->lang_array($lang_key, $parameters);
			$translated_message = call_user_func_array([$this->language, 'lang'], array_merge((array)$lang_key, $parameters));

			switch ($level)
			{
				case 1:
					$translated_message = '<info>' . $translated_message . '</info>';
					break;
				case 2:
					$translated_message = '<comment>' . $translated_message . '</comment>';
					break;
				case 3:
					$translated_message = '<warning>' . $translated_message . '</warning>';
					break;
				case 4:
					$translated_message = '<error>' . $translated_message . '</error>';
					break;
			}

			$translated_messages[] = $translated_message;
		}

		parent::write($translated_messages, $newline);
	}

	/**
	 * {@inheritdoc}
	 */
	public function writeError($messages, $newline = true)
	{
		$messages = (array) $messages;
		$translated_messages = [];

		foreach ($messages as $message)
		{
			$lang_key = $message;
			$parameters = [];
			$level = 0;

			$message = trim(strip_tags($message), "\n\r");

			if (strpos($message, '  Problem ') === 0)
			{
				if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE)
				{
					continue;
				}

				$lang_key = "\n" . $message . "\n";
				$level = 4;
			}
			else if ($message === 'Updating dependencies')
			{
				$lang_key = 'COMPOSER_UPDATING_DEPENDENCIES';
				$level = 1;
			}
			else if ($message === 'Loading composer repositories with package information')
			{
				$lang_key = 'COMPOSER_LOADING_REPOSITORIES';
				$level = 1;
			}
			else if ($message === 'Your requirements could not be resolved to an installable set of packages.')
			{
				$this->composer_error[] = ['COMPOSER_ERROR_CONFLICT', []];
				continue;
			}
			else if (strpos($message, 'could not be fully loaded, package information was loaded from the local cache and may be out of date') !== false)
			{
				$end_repo = strpos($message, 'could not be fully loaded, package information was loaded from the local cache and may be out of date');
				$repo = substr($message, 0, $end_repo - 1);

				$lang_key = 'COMPOSER_REPOSITORY_UNAVAILABLE';
				$parameters = [$repo];
				$level = 3;
			}
			else if (strpos($message, 'file could not be downloaded') !== false)
			{
				continue;
			}
			else if (strpos($message, '  - Installing ') === 0)
			{
				$elements = explode(' ', $message);
				$lang_key = 'COMPOSER_INSTALLING_PACKAGE';
				$parameters = [$elements[4], trim($elements[5], '()')];
			}
			else if ($message === 'Nothing to install or update')
			{
				$lang_key = 'COMPOSER_UPDATE_NOTHING';
				$level = 3;
			}
			else if ($message === '    Downloading')
			{
				continue;
			}
			else if ($message === '    Loading from cache')
			{
				continue;
			}
			else if ($message === 'Writing lock file')
			{
				continue;
			}
			else if ($message === '    Extracting archive')
			{
				continue;
			}
			else if (empty($message))
			{
				continue;
			}
			else
			{
				dump('WRITE ERROR | ' . $message);
			}

			//$translated_message = $this->language->lang_array($lang_key, $parameters);
			$translated_message = call_user_func_array([$this->language, 'lang'], array_merge((array)$lang_key, $parameters));

			switch ($level)
			{
				case 1:
					$translated_message = '<info>' . $translated_message . '</info>';
					break;
				case 2:
					$translated_message = '<comment>' . $translated_message . '</comment>';
					break;
				case 3:
					$translated_message = '<warning>' . $translated_message . '</warning>';
					break;
				case 4:
					$translated_message = '<error>' . $translated_message . '</error>';
					break;
			}

			$translated_messages[] = $translated_message;
		}

		parent::writeError($translated_messages, $newline);
	}

	public function get_composer_error()
	{
		$error = '';
		foreach ($this->composer_error as $error_line)
		{
			// $error .= $this->language->lang_array($error_line[0], $error_line[1]);
			$error .= call_user_func_array([$this->language, 'lang'], array_merge((array)$error_line[0], $error_line[1]));
			$error .= "\n";
		}

		$this->composer_error = [];

		return $error;
	}
}
