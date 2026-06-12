<?php
/**
 * Этот файл является частью модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Marketplace\PluginManager\Controller;

use Ge\Panel\Controller\ComboTriggerController;

/**
 * Контроллера выпадающего списка.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\PluginManager\Controller
 */
class Trigger extends ComboTriggerController
{
    /**
     * {@inheritdoc}
     */
    protected array $triggerNames = [
        'plugins' => 'PluginCombo'
    ];
}
