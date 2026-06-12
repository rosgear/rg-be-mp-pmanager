<?php
/**
 * Этот файл является частью расширения модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Marketplace\PluginManager\Controller;

use Ge;
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Helper\ExtForm;
use Ge\Panel\Widget\EditWindow;
use Ge\Panel\Widget\Form as WForm;
use Ge\Panel\Controller\FormController;

/**
 * Контроллер редактирования плагина.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\PluginManager\Controller
 * @since 1.0
 */
class Form extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Marketplace\PluginManager\Extension
     */
    public BaseModule $module;

    /**
     * Возвращает идентификатор выбранного плагина.
     *
     * @return int
     */
    public function getIdentifier(): int
    {
        return (int) Ge::$app->router->get('id');
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): EditWindow
    {
        /** @var null|array $moduleInfo Информация о плагине */
        $pluginInfo = null;
        // идентификатор плагина
        if ($identifier = $this->getIdentifier()) {
            $pluginInfo = Ge::$app->plugins->getRegistry()->getInfo($identifier);
        }

        /** @var EditWindow $window */
        $window = parent::createWidget();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        if ($pluginInfo) {
            $window->icon = $pluginInfo['icon'];
        }
        $window->ui = 'install';
        $window->title = $this->t('{form.title}');
        $window->titleTpl = sprintf('%s <span>%s</span>', $this->t('{form.title}'),  $this->t('{form.subtitle}'));
        $window->width = 520;
        $window->autoHeight = true;
        $window->layout = 'fit';
        $window->resizable = false;

        // панель формы (Ge.view.form.Panel GeJS)
        $window->form->autoScroll = true;
        $window->form->router->route = $this->module->route('/form');
        // определяем свой набор кнопок
        $window->form->setStateButtons(WForm::STATE_UPDATE, ['help' => ['subject' => 'edit'], 'save', 'cancel']);
        $viewFile = 'form';
        // если есть информация о плагине
        if ($viewFile) {
            // если плагин системный (настройки нельзя менять)
            $lock = (bool) ($pluginInfo['lock'] ?? false);
            $viewFile = $lock ? 'form-lock' : $viewFile;
        }
        // подстановка переменных в шаблон
        $window->form->loadJSONFile($viewFile, 'items', [
            // языковая панель вкладок с полями
            '@languageTabs' => ExtForm::languageTabs(function ($tag) {
                return [
                    [
                        'xtype'      => 'textfield',
                        'fieldLabel' => '#Name',
                        'name'       => $tag ? 'locale[' . $tag. '][name]' : 'name',
                        'labelWidth' => 105,
                        'anchor'     => '100%',
                        'width'      => 447,
                        'maxLength'  => 255,
                        'allowBlank' => false
                    ],
                    [
                        'xtype'      => 'textfield',
                        'fieldLabel' => '#Description',
                        'name'       => $tag ? 'locale[' . $tag. '][description]' : 'description',
                        'labelWidth' => 105,
                        'anchor'     => '100%',
                        'width'      => 447,
                        'maxLength'  => 255,
                        'allowBlank' => false
                    ],
                ];
            }, true, [], ['layout' => 'anchor'])
        ]);
        return $window;
    }
}