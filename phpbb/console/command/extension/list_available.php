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

namespace phpbb\console\command\extension;

use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use phpbb\composer\installer;
use phpbb\composer\manager;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class list_available extends \phpbb\console\command\command
{
	/**
	 * @var \phpbb\composer\manager Composer extensions manager
	 */
	protected $manager;

	public function __construct(\phpbb\user $user, manager $manager)
	{
		$this->manager = $manager;

		parent::__construct($user);
	}

	/**
	* Sets the command name and description
	*
	* @return null
	*/
	protected function configure()
	{
		$this
			->setName('extension:list-available')
			->setDescription($this->user->lang('CLI_DESCRIPTION_EXTENSION_LIST_AVAILABLE'))
		;
	}

	/**
	* Executes the command extension:install
	*
	* @param InputInterface $input
	* @param OutputInterface $output
	* @return integer
	*/
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);

		$extensions = [];

		foreach ($this->manager->get_available_packages() as $package)
		{
			$extensions[] = '<info>' . $package['name'] . '</info> <comment>' . $package['url'] . "</comment>\n" . $package['description'];
		}

		$io->listing($extensions);

		return 0;
	}
}
