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
use Ge\Filesystem\Filesystem;
use Ge\Mvc\Module\BaseModule;
use Ge\Panel\Controller\BaseController;
use Rg\Backend\Marketplace\PluginManager\Widget\InformationTab;

/**
 * Контроллер удаления и демонтажа плагина.
 * 
 * Действия контроллера:
 * - unmount, удаление установленного плагина без удаления его из репозитория;
 * - uninstall, полностью удаление установленного плагина;
 * - update, обновление конфигурации установленных плагинов;
 * - delete, удаление не установленного плагина из репозитория;
 * - info, информация о плагине.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\PluginManager\Controller
 * @since 1.0
 */
class Plugin extends BaseController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Rg\Backend\Marketplace\PluginManager\Extension
     */
    public BaseModule $module;

    /**
     * Действие "unmount" выполняет удаление установленного плагина без удаления его 
     * из репозитория.
     * 
     * @return Response
     */
    public function unmountAction(): Response
    {
        /** @var \Ge\PluginManager\PluginManager */
        $plugins = Ge::$app->plugins;
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Ge\Http\Request $request */
        $request = Ge::$app->request;

        // идентификатор плагина в базе данных
        $pluginId = $request->getPost('id', null, 'int');
        if (empty($pluginId)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', [$pluginId]));
            return $response;
        }

        /** @var null|array Конфигурация установленного плагина */
        $pluginConfig = $plugins->getRegistry()->getInfo($pluginId, true);
        if ($pluginConfig === null) {
            $response
                ->meta->error($this->module->t('Plugin with specified id "{0}" not found', [$pluginId]));
            return $response;
        }

        // локализация плагина
        $localization = $plugins->selectName($pluginConfig['rowId']);
        if ($localization) {
            $name = $localization['name'] ?? SYMBOL_NONAME;
        } else {
            $name = $moduleConfig['name'] ?? SYMBOL_NONAME;
        }

        // если плагин не имеет установщика "Installer\Installer.php"
        if (!$plugins->installerExists($pluginConfig['path'])) {
            $response
                ->meta->error(
                    $this->module->t('The plugin installer at the specified path "{0}" does not exist', [$pluginConfig['path']])
                );
            return $response;
        }

        // каждый плагин обязан иметь установщик, управление установщиком передаётся текущему модулю
        /** @var \Ge\PluginManager\PluginInstaller $installer Установщик плагина */
        $installer = $plugins->getInstaller([
            'module'    => $this->module,
            'namespace' => $pluginConfig['namespace'],
            'path'      => $pluginConfig['path'],
            'pluginId'  => $pluginId
        ]);

        // если не получилось создать установщик
        if ($installer === null) {
            $response
                ->meta->error($this->t('Unable to create plugin installer'));
            return $response;
        }

        // демонтируем плагин
        if ($installer->unmount()) {
            $response
                ->meta
                    ->cmdPopupMsg(
                        $this->module->t('Unmounting of plugin "{0}" completed successfully', [$name]), 
                        $this->t('Unmounting'), 
                        'accept'
                    )
                    ->cmdReloadGrid($this->module->viewId('grid'));
        } else {
            $response
                ->meta->error($installer->getError());
        }
        return $response;
    }

    /**
     * Действие "uninstall" выполняет полностью удаление установленного плагина.
     * 
     * @return Response
     */
    public function uninstallAction():Response
    {
        /** @var \Ge\PluginManager\PluginManager */
        $plugins = Ge::$app->plugins;
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Ge\Http\Request $request */
        $request = Ge::$app->request;

        // идентификатор плагина в базе данных
        $pluginId = $request->getPost('id', null, 'int');
        if (empty($pluginId)) {
            $response
                ->meta->error(Ge::t('app', 'Parameter "{0}" not specified', ['id']));
            return $response;
        }

        /** @var null|array Конфигурация установленного плагина */
        $pluginConfig = $plugins->getRegistry()->getInfo($pluginId, true);
        if ($pluginConfig === null) {
            $response
                ->meta->error($this->module->t('Plugin with specified id "{0}" not found', [$pluginId]));
            return $response;
        }

        // локализация плагина
        $localization = $plugins->selectName($pluginConfig['rowId']);
        if ($localization) {
            $name = $localization['name'] ?? SYMBOL_NONAME;
        } else {
            $name = $pluginConfig['name'] ?? SYMBOL_NONAME;
        }

        // если плагин не имеет установщика "Installer\Installer.php"
        if (!$plugins->installerExists($pluginConfig['path'])) {
            $response
                ->meta->error(
                    $this->module->t('The plugin installer at the specified path "{0}" does not exist', [$pluginConfig['path']])
                );
            return $response;
        }

        // каждый плагин обязано иметь установщик, управление установщиком передаётся текущему модулю
        /** @var \Ge\PluginManager\PluginInstaller $installer Установщик плагина */
        $installer = $plugins->getInstaller([
            'module'    => $this->module,
            'namespace' => $pluginConfig['namespace'],
            'path'      => $pluginConfig['path'],
            'pluginId'  => $pluginId
        ]);

        // если не получилось создать установщик
        if ($installer === null) {
            $response
                ->meta->error($this->t('Unable to create plugin installer'));
            return $response;
        }

        // удаление плагина
        if ($installer->uninstall()) {
            $response
                ->meta
                    ->cmdPopupMsg(
                        $this->module->t('Uninstalling of plugin "{0}" completed successfully', [$name]), 
                        $this->t('Uninstalling'), 
                        'accept'
                    )
                    ->cmdReloadGrid($this->module->viewId('grid'));
        } else {
            $response
                ->meta->error($installer->getError());
        }
        return $response;
    }

    /**
     * Действие "update" обновляет конфигурацию установленных плагинов.
     * 
     * @return Response
     */
    public function updateAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        // обновляет конфигурацию установленных плагинов
        Ge::$app->plugins->update();
        $response
            ->meta->success(
                $this->t('Plugins configuration files are updated'), 
                $this->t('Updating plugins'), 
                'custom', 
                $this->module->getAssetsUrl() . '/images/icon-update-config.svg'
            );
        return $response;
    }

    /**
     * Действие "delete" выполняет удаление не установленного плагина из репозитория.
     * 
     * @return Response
     */
    public function deleteAction(): Response
    {
        /** @var \Ge\PluginManager\PluginManager */
        $plugins = Ge::$app->plugins;
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|string $installId Идентификатор установки плагина */
        $installId = Ge::$app->request->post('installId');

        /** @var string|array $decrypt Расшифровка идентификатора установки плагина */
        $decrypt = $plugins->decryptInstallId($installId);
        if (is_string($decrypt)) {
            $response
                ->meta->error($decrypt);
            return $response;
        }

        /** @var null|array $installConfig Параметры конфигурации установки плагина */
        $installConfig = $plugins->getConfigInstall($decrypt['path']);
        if (empty($installConfig)) {
            $response
                ->meta->error(
                    $this->module->t('Plugin installation configuration file is missing')
                );
            return $response;
        }

        // если плагин установлен
        if ($plugins->getRegistry()->has($installConfig['id'])) {
            $response
                ->meta->error(
                    $this->module->t('It is not possible to remove the plugin from the repository because it\'s installed')
                );
            return $response;
        }

        // попытка удаления всех файлов плагина
        if (Filesystem::deleteDirectory(Ge::$app->modulePath . $decrypt['path'])) {
            $response
                ->meta
                    ->cmdPopupMsg(
                        $this->t('Deleting of plugin completed successfully'), 
                        $this->t('Deleting'), 
                        'accept'
                    )
                    ->cmdReloadGrid($this->module->viewId('grid'));
        } else {
            $response
                ->meta->error(
                    Ge::t('app', 'Could not perform directory deletion "{0}"', [Ge::$app->modulePath . $decrypt['path']])
                );
        }
        return $response;
    }

    /**
     * Действие "info" возвращает информацию о плагине.
     * 
     * @return Response
     */
    public function infoAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var null|string $pluginId Идентификатор плагина */
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

        // если плагин не найден
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
                ->addPlugin($tab);
        return $response;
    }
}
