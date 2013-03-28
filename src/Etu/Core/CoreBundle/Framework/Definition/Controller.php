<?php

namespace Etu\Core\CoreBundle\Framework\Definition;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class Controller extends BaseController
{
	/**
	 * @return \Etu\Core\CoreBundle\Menu\Sidebar\SidebarBuilder
	 */
	public function getSidebarBuilder()
	{
		return $this->get('etu.menu.sidebar_builder');
	}

	/**
	 * @return \Etu\Core\CoreBundle\Menu\UserMenu\UserMenuBuilder
	 */
	public function getUserMenuBuilder()
	{
		return $this->get('etu.menu.user_builder');
	}

	/**
	 * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
	 */
	public function createAccessDeniedResponse()
	{
		$this->redirect($this->generateUrl('user_connect'));
	}
}
