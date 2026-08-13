<?php

/**
 * -------------------------------------------------------------------------
 * YAGP plugin for GLPI
 * Copyright (C) 2019-2025 by the TICgal Team.
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
 * -------------------------------------------------------------------------
 * @package   yagp
 * @author    the TICGAL team
 * @copyright Copyright (c) 2025 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://www.tic.gal
 * @since     2019
 * -------------------------------------------------------------------------
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PluginYagpUser extends CommonDBTM
{
    /**
     * Map a locked field key (PluginYagpProfile::getLockFieldsCatalog()) to the
     * $input keys it must strip on User::prepareInputForUpdate(). A single field
     * can cover more than one input key (picture upload + its "clear" checkbox).
     *
     * @return array<string, string[]>
     */
    private static function getInputKeysForField(): array
    {
        return [
            'firstname'             => ['firstname'],
            'realname'              => ['realname'],
            'phone'                 => ['phone'],
            'phone2'                => ['phone2'],
            'mobile'                => ['mobile'],
            'language'              => ['language'],
            'timezone'              => ['timezone'],
            'registration_number'   => ['registration_number'],
            'locations_id'          => ['locations_id'],
            'nickname'              => ['nickname'],
            'picture'               => ['picture', '_blank_picture'],
            'profiles_id'           => ['profiles_id'],
            'entities_id'           => ['entities_id'],
        ];
    }

    /**
     * PRE_ITEM_UPDATE hook on User::class. Strips any field locked for the
     * current session's active profile from the submitted input, but only
     * when users edit their own account (an admin editing someone else's
     * account is never affected by this).
     *
     * @param User $item
     *
     * @return User
     */
    public static function preItemUpdate(User $item): User
    {
        if (!is_array($item->input) || (int) ($item->fields['id'] ?? 0) !== (int) Session::getLoginUserID()) {
            return $item;
        }

        $blocked = [];
        $input_keys = self::getInputKeysForField();
        foreach (PluginYagpProfile::getLockedFieldsForSession() as $field) {
            foreach ($input_keys[$field] ?? [] as $key) {
                if (array_key_exists($key, $item->input)) {
                    unset($item->input[$key]);
                    $blocked[] = $field;
                }
            }
        }

        if (!empty($blocked)) {
            self::logBlockedAttempt('User', $item->fields['id'], array_unique($blocked));
            Session::addMessageAfterRedirect(
                __("Some fields could not be updated: they are locked by your profile.", 'yagp'),
                false,
                WARNING,
            );
        }

        return $item;
    }

    /**
     * PRE_ITEM_ADD hook on UserEmail::class. Cancels adding a new secondary
     * email for the logged-in user when the "email" field is locked.
     *
     * @param UserEmail $item
     *
     * @return UserEmail
     */
    public static function preItemAddEmail(UserEmail $item): UserEmail
    {
        if (
            is_array($item->input)
            && (int) ($item->input['users_id'] ?? 0) === (int) Session::getLoginUserID()
            && in_array('email', PluginYagpProfile::getLockedFieldsForSession(), true)
        ) {
            self::logBlockedAttempt('UserEmail add', Session::getLoginUserID(), ['email']);
            Session::addMessageAfterRedirect(
                __("Email is locked by your profile.", 'yagp'),
                false,
                WARNING,
            );
            $item->input = false;
        }

        return $item;
    }

    /**
     * PRE_ITEM_DELETE hook on UserEmail::class. Cancels removing an existing
     * email of the logged-in user when the "email" field is locked.
     * Setting $item->input to a non-array value is the documented way to
     * cancel a delete from CommonDBTM::delete().
     *
     * @param UserEmail $item
     *
     * @return UserEmail
     */
    public static function preItemDeleteEmail(UserEmail $item): UserEmail
    {
        if (
            (int) ($item->fields['users_id'] ?? 0) === (int) Session::getLoginUserID()
            && in_array('email', PluginYagpProfile::getLockedFieldsForSession(), true)
        ) {
            self::logBlockedAttempt('UserEmail delete', Session::getLoginUserID(), ['email']);
            Session::addMessageAfterRedirect(
                __("Email is locked by your profile.", 'yagp'),
                false,
                WARNING,
            );
            $item->input = false;
        }

        return $item;
    }

    /**
     * Optional audit trail of blocked edit attempts, controlled by the
     * "lockfields_auditlog" config switch so it doesn't grow unbounded by
     * default.
     *
     * @param string $context
     * @param int    $users_id
     * @param array  $fields
     *
     * @return void
     */
    private static function logBlockedAttempt(string $context, int $users_id, array $fields): void
    {
        $config = PluginYagpConfig::getInstance();
        if (!$config->fields['lockfields_auditlog']) {
            return;
        }

        Toolbox::logInFile(
            'yagp_lockfields',
            sprintf(
                "%s - user #%d attempted to change locked field(s): %s\n",
                $context,
                $users_id,
                implode(', ', $fields),
            ),
        );
    }
}
