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
use Ge\Panel\Http\Response;
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Controller\BaseController;
use Rg\Backend\Marketplace\PluginManager\Widget\InformationTab;

/**
 * Контроллер информации о виджете.
 * 
 * Действия контроллера:
 * - index, информация о виджете;
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\PluginManager\Controller
 * @since 1.0
 */
class PluginInfo extends BaseController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Marketplace\PluginManager\Extension
     */
    public BaseModule $module;

    /**
     * Действие "info" возвращает информацию о виджете.
     * 
     * @return Response
     */
    public function indexAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|string $pluginId Идентификатор виджета */
        $pluginId = Ge::$app->request->get('id');
        if (empty($pluginId)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', ['id']));
            return $response;
        }

        /** @var InformationTab $tab */
        $tab = new InformationTab();
        /** @var null|array $pluginInfo*/
        $pluginInfo = $tab->getPluginInfo($pluginId);

        // если виджет не найден
        if ($pluginInfo === null) {
            $response
                ->meta->error($this->module->t('There is no plugin with the specified id "{0}"', [$pluginId]));
            return $response;
        }

        // панель (Ext.panel Sencha ExtJS)
        $tab->panel->html = $this->getViewManager()->renderPartial('plugin-info', $pluginInfo);
        // панель вкладки компонента (Ge.view.tab.Components GeJS)
        $tab->title = $this->module->t('{info.title}', [$pluginInfo['name']]);
        $tab->icon  = Ge::$app->moduleUrl . $pluginInfo['path'] . '/assets/images/icon_small.svg';
        $tab->tooltip = [
            'icon'  => Ge::$app->moduleUrl . $pluginInfo['path'] . '/assets/images/icon.svg',
            'title' => $tab->title,
            'text'  => $pluginInfo['description']
        ];

        $response
            ->setContent($tab->run())
            ->meta
                ->addWidget($tab);
        return $response;
    }
}
