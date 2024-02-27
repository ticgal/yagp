<?php

/**
 * -------------------------------------------------------------------------
 * YAGP plugin for GLPI
 * Copyright (C) 2019-2024 by the TICgal Team.
 * https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 * -------------------------------------------------------------------------
 * LICENSE
 * This file is part of the YAGP plugin.
 * YAGP plugin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * YAGP plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with YAGP. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 * @package   YAGP
 * @author    the TICgal team
 * @copyright Copyright (c) 2019-2024 TICgal team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 * @since     2019
 * ----------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginYagpSoftware extends CommonDBTM
{
    public static $rightname = 'software';

    /**
     * pluginYagpItemAdd
     *
     * @param  mixed $item
     * @return void
     */
    public static function pluginYagpItemAdd(CommonDBTM $item): void
    {
        $input = $item->input;
        switch ($item->getType()) {
            case 'Item_SoftwareVersion':
                self::replaceSoftwareVersion($input);
                break;
            default:
                break;
        }
    }

    /**
     * replaceSoftwareVersion
     *
     * @param  array $input
     * @return bool
     */
    public static function replaceSoftwareVersion(array $input): bool
    {
        $isv = new Item_SoftwareVersion();
        $sv = new SoftwareVersion();
        $sv->getFromDB($input['softwareversions_id']);
        $softwares_id = $sv->getField('softwares_id');

        $versionToAdd = $sv->getField('name');
        if (self::isVersionString($versionToAdd)) {
            $versionInstalled = $isv->find([
                'itemtype' => $input['itemtype'],
                'items_id' => $input['items_id'],
                'NOT' => [
                    'softwareversions_id' => $input['softwareversions_id']
                ]
            ]);

            foreach ($versionInstalled as $id => $item_sv) {
                $sv->getFromDB($item_sv['softwareversions_id']);
                if (
                    $sv->getField('softwares_id') == $softwares_id &&
                    self::isVersionString($sv->getField('name'))
                ) {
                    $isMajor = version_compare($versionToAdd, $sv->getField('name'), '>')
                        ? true
                        : false;

                    if (
                        $isMajor ||
                        (
                            self::isStableVersion($versionToAdd) &&
                            version_compare($versionToAdd, self::removeVersionFlag($sv->getField('name')), '==')
                        )
                    ) {
                        $isv->delete(['id' => $id]);
                    }
                }
            }
        }

        return false;
    }

    /**
     * isVersionString
     *
     * @param  mixed $version
     * @return bool
     */
    public static function isVersionString(string $version): bool
    {
        $version_pattern = implode(
            '',
            [
                '/^',
                '(?<major>\d+)', // Major release numero, always present
                '\.(?<minor>\d+)', // Minor release numero, always present
                '(\.(?<bugfix>\d+))?', // Bugfix numero, not always present (e.g. GLPI 9.2)
                '(\.(?<tag_fail>\d+))?', // Redo tag operation numero, rarely present (e.g. GLPI 9.4.1.1)
                '(?<stability_flag>-(dev|alpha|beta|rc)\.?\d*)?', // Stability flag, optional
                '$/'
            ]
        );
        $version_matches = [];
        return preg_match($version_pattern, $version, $version_matches) === 1;
    }

    /**
     * isStableVersion
     *
     * @param  mixed $version
     * @return bool
     */
    public static function isStableVersion(string $version): bool
    {
        $version_matches = [];
        if (self::isVersionString($version)) {
            preg_match(
                '/^.*-(dev|alpha|beta|rc)\.?\d*$/',
                $version,
                $version_matches
            );
        }
        return empty($version_matches);
    }

    /**
     * removeVersionFlag
     *
     * @param  mixed $version
     * @return string
     */
    public static function removeVersionFlag(string $version): string
    {
        $version = preg_replace('/-(dev|alpha|beta|rc)\.?\d*$/', '', $version);

        return $version;
    }
}
