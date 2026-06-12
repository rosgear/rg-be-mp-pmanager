<?php
/**
 * Расширение модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Marketplace\PluginManager;

/**
 * Расширение "Менеджер плагинов сайта".
 * 
 * Расширение принадлежит модулю "Маркетплейс".
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\PluginManager
 * @since 1.0
 */
class Extension extends \Ge\Panel\Extension\Extension
{
    /**
     * {@inheritdoc}
     */
    public string $id = 'rg.be.mp.pmanager';

    /**
     * {@inheritdoc}
     */
    public string $defaultController = 'grid';

    /**
     * {@inheritdoc}
     */
    public function controllerMap(): array
    {
        return [
            'pinfo'     => 'PluginInfo',
            'psettings' => 'PluginSettings'
        ];
    }
}