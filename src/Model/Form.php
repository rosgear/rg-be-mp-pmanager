<?php
/**
 * Этот файл является частью расширения модуля веб-приложения RosGear.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

namespace Rg\Backend\Marketplace\PluginManager\Model;

use Ge;
use Ge\Panel\Data\Model\FormModel;

/**
 * Модель данных изменения плагина.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\PluginManager\Model
 * @since 1.0
 */
class Form extends FormModel
{
    /**
     * {@inheritdoc}
     */
    public array $localizerParams = [
        'tableName'  => '{{plugin_locale}}',
        'foreignKey' => 'plugin_id',
        'modelName'  => 'Ge\PluginManager\Model\PluginLocale',
    ];


    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'useAudit'   => true,
            'tableName'  => '{{plugin}}',
            'primaryKey' => 'id',
            'fields'     => [
                ['id'],
                ['name'],
                ['description'],
                [
                    'plugin_id',
                    'alias' => 'pluginId'
                ],
                [
                    'enabled', 
                    'title' => 'Enabled'
                ],
                /**
                 * поля добавленные динамически:
                 * - title, имя расширения (для заголовка окна)
                 */
            ],
            // правила форматирования полей
            'formatterRules' => [
                [['enabled'], 'logic']
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_AFTER_SAVE, function ($isInsert, $columns, $result, $message) {
                // если всё успешно
                if ($result) {
                    /** @var \Ge\PluginManager\PluginRegistry $installed */
                    $installed = Ge::$app->plugins->getRegistry();
                    $plugin = $installed->get($this->pluginId);
                    if ($plugin) {
                        $lock = (bool) ($plugin['lock'] ?? false);
                        // если плагин не системный
                        if (!$lock) {
                            // обвновление конфигурации установленных плагинов
                            $installed->set($this->pluginId, [
                                'enabled'     => (bool) $this->enabled,
                                'name'        => $this->name,
                                'description' => $this->description
                            ], true);
                        }
                    }
                }
                // всплывающие сообщение
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                /** @var \Ge\Panel\Controller\FormController $controller */
                $controller = $this->controller();
                // обновить список
                $controller->cmdReloadGrid();
            })
            ->on(self::EVENT_AFTER_DELETE, function ($result, $message) {
                // обвновление конфигурации установленных плагинов
                Ge::$app->plugins->update();
                // всплывающие сообщение
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                /** @var \Ge\Panel\Controller\FormController $controller */
                $controller = $this->controller();
                // обновить список
                $controller->cmdReloadGrid();
            });
    }

    /**
     * {@inheritdoc}
     */
    public function processing(): void
    {
        parent::processing();

        // для формирования загаловка по атрибутам
        $locale = $this->getLocalizer()->getModel();
        if ($locale) {
            $this->title = $locale->name ?: '';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function afterValidate(bool $isValid): bool
    {
        if ($isValid) {
            if (!Ge::$app->plugins->getRegistry()->has($this->pluginId)) {
                $this->setError($this->module->t('There is no plugin with the specified id "{0}"', [$this->pluginId]));
                return false;
            }
        }
        return $isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionTitle():string
    {
        return isset($this->title) ? $this->title : parent::getActionTitle();
    }
}
