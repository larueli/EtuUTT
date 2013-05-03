<?php

namespace Etu\Module\DailymailBundle;

use Etu\Core\CoreBundle\Framework\Definition\Module;

class EtuModuleDailymailBundle extends Module
{
	/**
	 * @return bool
	 */
	public function mustBoot()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function isReadyToUse()
	{
		return false;
	}

	/**
	 * Module identifier (to be required by other modules)
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return 'dailymail';
	}

	/**
	 * Module title (describe shortly its aim)
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return 'Dailymail';
	}

	/**
	 * Module author
	 *
	 * @return string
	 */
	public function getAuthor()
	{
		return 'anonymous';
	}

	/**
	 * Module description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return 'Default module description';
	}

	/**
	 * Define the modules requirements (the other required modules) using their identifiers
	 *
	 * @return array
	 */
	public function getRequirements()
	{
		return array(
			// Insert your requirements here
		);
	}
}
