<?php

namespace Etu\Core\CoreBundle\Twig\Extension;

use Etu\Core\CoreBundle\Entity\Notification;
use Etu\Core\CoreBundle\Notification\Helper\HelperManager;

/**
 * StringManipulationExtension
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class NotificationHelperExtension extends \Twig_Extension
{
	/**
	 * @var HelperManager
	 */
	protected $helperManager;

	/**
	 * @param HelperManager $helperManager
	 */
	public function __construct(HelperManager $helperManager)
	{
		$this->helperManager = $helperManager;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'notifs_helper';
	}

	/**
	 * @return array
	 */
	public function getFunctions()
	{
		return array(
			'render_notif' => new \Twig_Function_Method($this, 'render', array('is_safe' => array('html'))),
		);
	}

	/**
	 * @param Notification $notification
	 * @return string
	 */
	public function render(Notification $notification)
	{
		return $this->helperManager->getHelper($notification->getHelper())->render($notification);
	}
}
