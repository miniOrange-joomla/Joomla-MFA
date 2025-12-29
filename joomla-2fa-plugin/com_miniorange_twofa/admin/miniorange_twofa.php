<?php
/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
require_once JPATH_COMPONENT . '/helpers/Mo_tfa_utility.php';
require_once JPATH_COMPONENT . '/helpers/Mo_tfa_customer_setup.php';
// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_miniorange_twofa')) {
    throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('com_miniorange_twofa', JPATH_COMPONENT_ADMINISTRATOR);

$controller = BaseController::getInstance('miniorange_twofa');
$app = Factory::getApplication();
$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
$task = ($input && method_exists($input, 'get')) ? $input->get('task') : '';
$controller->execute($task);
$controller->redirect();