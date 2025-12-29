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
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Class Miniorange_Controller
 *
 * @since  1.6
 */
class Miniorange_twofaController extends BaseController
{
    /**
     * Method to display a view.
     *
     * @param boolean $cachable If true, the view output will be cached
     * @param mixed $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return   JController This object to support chaining.
     *
     * @since    1.5
     */
    // public function display($cachable = false, $urlparams = false)
    // {
    //     $view = Factory::getApplication()->input->getCmd('view', 'miniorange_twofa');
    //     Factory::getApplication()->input->set('view', $view);

    //     parent::display($cachable, $urlparams);

    //     return $this;
    // }
    protected $default_view = 'account_setup';
}
