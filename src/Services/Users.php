<?php

namespace NerdsAndCompany\Schematic\Services;

use Craft;
use craft\elements\User;
use NerdsAndCompany\Schematic\Behaviors\FieldLayoutBehavior;
use NerdsAndCompany\Schematic\Schematic;
use NerdsAndCompany\Schematic\Interfaces\MappingInterface;
use yii\base\Component as BaseComponent;

/**
 * Schematic Users Service.
 *
 * Sync Craft Setups.
 *
 * @author    Nerds & Company
 * @copyright Copyright (c) 2015-2018, Nerds & Company
 * @license   MIT
 *
 * @see      http://www.nerds.company
 */
class Users extends BaseComponent implements MappingInterface
{
    /**
     * Load fieldlayout behavior.
     *
     * @return array
     */
    public function behaviors()
    {
        return [
          FieldLayoutBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $settings = []): array
    {
        $settings = Craft::$app->systemSettings->getSettings('users');
        $photoVolumeId = (int) $settings['photoVolumeId'];
        $volume = Craft::$app->volumes->getVolumeById($photoVolumeId);
        unset($settings['photoVolumeId']);

        $fieldLayout = Craft::$app->getFields()->getLayoutByType(User::class);

        return [
            'settings' => $settings,
            'photoVolume' => $volume ? $volume->handle : null,
            'fieldLayout' => $this->getFieldLayoutDefinition($fieldLayout),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function import(array $userSettings, array $settings = []): array
    {
        $photoVolumeId = null;
        if (array_key_exists('photoVolume', $userSettings) && $userSettings['photoVolume'] != null) {
            $volume = Craft::$app->volumes->getVolumeByHandle($userSettings['photoVolume']);
            $photoVolumeId = $volume ? $volume->id : null;
        }
        if (array_key_exists('settings', $userSettings)) {
            Schematic::info('- Saving user settings');
            $userSettings['photoVolumeId'] = $photoVolumeId;
            if (!Craft::$app->systemSettings->saveSettings('users', $userSettings['settings'])) {
                Schematic::warning('- Couldn’t save user settings.');
            }
        }

        if (array_key_exists('fieldLayout', $userSettings)) {
            Schematic::info('- Saving user field layout');
            $fieldLayout = $this->getFieldLayout($userSettings['fieldLayout']);
            $fieldLayout->type = User::class;

            Craft::$app->fields->deleteLayoutsByType(User::class);
            if (!Craft::$app->fields->saveLayout($fieldLayout)) {
                Schematic::warning('- Couldn’t save user field layout.');
            }
        }

        return [];
    }
}
