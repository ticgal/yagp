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

$(document).ready(function() {
    var path = window.location.pathname;
    var onMyForm = path.endsWith('/front/preference.php') || path.endsWith('/front/user.form.php');
    if (!onMyForm) {
        return;
    }

    var yagpLockedFields = null;

    // The "Main" tab (and every other user tab) is injected asynchronously,
    // so the id input isn't in the DOM yet at document.ready time. Poll for
    // it, same as blockdate.js does for the ticket date field.
    var waitForId = setInterval(function() {
        var id = $("input[name='id']").val();
        if (!id) {
            return;
        }
        clearInterval(waitForId);

        $.getJSON(CFG_GLPI['root_doc'] + '/plugins/yagp/ajax/lockfields.php', {id: id}, function(data) {
            yagpLockedFields = (data && data.locked) || [];
            yagpApplyLockedFields(yagpLockedFields);
        });
    }, 100);

    // Switching tabs (or coming back to "Main") reloads the form via ajax,
    // wiping out any disabled/class attributes applied earlier. Re-apply
    // the already-fetched list every time an ajax call finishes.
    $(document).ajaxComplete(function() {
        if (yagpLockedFields && yagpLockedFields.length) {
            yagpApplyLockedFields(yagpLockedFields);
        }
    });
});

function yagpApplyLockedFields(fields) {
    fields.forEach(yagpLockField);
}

/**
 * Visually locks a single user-profile field. This is UX only: the
 * authoritative check happens server-side (PRE_ITEM_UPDATE/PRE_ITEM_ADD
 * hooks), so a mismatch here never becomes a security issue.
 *
 * @param {string} field
 */
function yagpLockField(field) {
    if (field === 'email') {
        yagpLockEmailFields();
        return;
    }

    var $el = $("[name='" + field + "']");
    // Idempotent: skip fields already locked so we never redo work on every
    // ajaxComplete re-apply.
    if ($el.length === 0 || $el.hasClass('yagp-locked-field')) {
        return;
    }

    // Only prop('disabled') + a CSS class: no synthetic events. A select2
    // dropdown re-emits its own "change" event while syncing its widget,
    // which reaches GLPI's real change handlers (e.g. the language field
    // reloads the page on change) even when triggered with a namespace -
    // so we never trigger anything on these fields, just disable them.
    $el.addClass('yagp-locked-field')
        .prop('disabled', true)
        .attr('title', yagpLockedTooltip());
}

function yagpLockEmailFields() {
    var $inputs = $("input[name^='_useremails'], input[name='_default_email']").not('.yagp-locked-field');
    // GLPI renders this "+" icon with a plain onclick="" attribute (see
    // CommonDBChild::showAddChildButtonForItemForm), so jQuery on/off can't
    // intercept it - hiding the element is the only reliable way to block it.
    var $addBtn = $('#adduseremailbutton').not('.yagp-locked-action');

    if ($inputs.length === 0 && $addBtn.length === 0) {
        return;
    }

    $inputs
        .addClass('yagp-locked-field')
        .prop('disabled', true)
        .attr('title', yagpLockedTooltip());

    $addBtn
        .addClass('yagp-locked-action')
        .attr('title', yagpLockedTooltip())
        .hide();
}

function yagpLockedTooltip() {
    return 'Locked by your profile';
}
