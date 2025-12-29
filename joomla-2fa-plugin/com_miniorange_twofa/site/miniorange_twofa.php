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
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/Mo_tfa_utility.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/Mo_tfa_customer_setup.php';


// Get an instance of the controller prefixed by HelloWorld
$controller = BaseController::getInstance('Miniorange_TwoFa');


// Perform the Request task
$app = Factory::getApplication();
$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
