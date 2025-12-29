<?php
/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Factory;

/**
 * HelloWorlds View
 *
 * @since  0.0.1
 */
class miniorange_twofaViewlicensing extends HtmlView
{
	/**
	 * Display the Hello World view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	function display($tpl = null)
	{
		// Get data from the model
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->state = $this->get('State');

        $errors = $this->get('Errors');
		// Check for errors.
		if (is_array($errors) &&  count($errors))
		{
			Factory::getApplication()->enqueueMessage(500, implode('<br />', $errors));
			return false;
		}
		$this->addToolbar();

		$this->extra_sidebar = '';
       
		// Display the template
		parent::display($tpl);
	}
	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.0
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('mini<span><strong style="color:orange;">O</strong>range 2FA</span>'), 'mo_tfa_icon mo_tfa_mini_logo');
	}
}