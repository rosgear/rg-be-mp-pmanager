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
 * Модель данных профиля записи установленного плагина.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Rg\Backend\Marketplace\PluginManager\Model
 * @since 1.0
 */
class GridRow extends FormModel
{
    /**
     * Идентификатор выбранного плагина.
     * 
     * @see GridRow::afterValidate()
     * 
     * @var string
     */
    protected ?string $pluginId;

    /**
     * Имя выбранного плагина.
     * 
     * @see GridRow::afterValidate()
     * 
     * @var string
     */
    public ?string $pluginName;

    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'tableName'  => '{{plugin}}',
            'primaryKey' => 'id',
            'fields'     => [
                ['id'],
                ['name'], 
                ['enabled', 'label' => 'Enabled']
            ],
            'useAudit' => true
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
                if ($message['success']) {
                    if (isset($columns['enabled'])) {
                        $enabled = (int) $columns['enabled'];
                        $message['message'] = $this->module->t('Plugin {0} - ' . ($enabled > 0 ? 'enabled' : 'disabled'), [$this->pluginName]);
                        $message['title']   = $this->module->t($enabled > 0 ? 'Enabled' : 'Disabled');
                    }
                }
                // всплывающие сообщение
                $this->response()
                    ->meta
                        ->cmdPopupMsg($message['message'], $message['title'], $message['type']);
            });
    }

    /**
     * {@inheritDoc}
     */
    public function afterValidate(bool $isValid): bool
    {
        if ($isValid) {
            /** @var \Ge\Http\Request $request */
            $request  = Ge::$app->request;
            // имя плагина
            $this->pluginName = $request->post('name');
            if (empty($this->pluginName)) {
                $this->setError(Ge::t('app', 'Parameter passed incorrectly "{0}"', ['Name']));
                return false;
            }
            // идентификатор плагина
            $this->pluginId = $request->post('pluginId');
            if (empty($this->pluginId)) {
                $this->setError(Ge::t('app', 'Parameter passed incorrectly "{0}"', ['Plugin Id']));
                return false;
            }
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
    public function beforeUpdate(array &$columns): void
    {
        /** @var \Ge\PluginManager\PluginRegistry $installed */
        $installed = Ge::$app->plugins->getRegistry();
        /** @var \Ge\Http\Request $request */
        $request = Ge::$app->request;
        // доступность плагина
        $enabled = $request->getPost('enabled', 0, 'int');
        $installed->set($this->pluginId, ['enabled' => (bool) $enabled], true);
    }
}
