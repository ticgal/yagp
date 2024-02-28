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

$(document).ready(function() {
    if (window.location.href.indexOf("/front/software.form.php?id=") > -1) {
        function sortSoftwareVersion() {
            var tbody = $('[id^="tab-SoftwareVersion_"] table.table tbody');
            var versions = tbody.children();

            // get header and versions
            var header = versions.eq(0);
            var versionsArray = [];
            versions.each(function(index, element) {
                if (index > 0) {
                    var version = $(element).children().eq(0).text();
                    versionsArray.push({
                        version: version,
                        element: element
                    });
                }
            });

            if (versionsArray.length === 0) {
                return;
            }

            var standardVersions = [];
            var nonStandardVersions = [];
            versionsArray.forEach(function(version, index) {
                var versionString = version.version;
                var versionPattern = /^(\d+\.){1,3}\d+$/;
                if (versionString.match(versionPattern)) {
                    standardVersions.push(version);
                } else {
                    nonStandardVersions.push(version);
                }
            });

            // Order standard versions, highests first
            standardVersions.sort(function(a, b) {
                var aVersion = a.version.split('.');
                var bVersion = b.version.split('.');
                for (var i = 0; i < aVersion.length; i++) {
                    if (parseInt(aVersion[i]) > parseInt(bVersion[i])) {
                        return -1;
                    } else if (parseInt(aVersion[i]) < parseInt(bVersion[i])) {
                        return 1;
                    }
                }
                return 0;
            });

            // replace versionsArray with header + sortered standardVersions + nonStandardVersions
            versionsArray = [header[0]].concat(standardVersions.map(function(version) {
                return version.element;
            })).concat(nonStandardVersions.map(function(version) {
                return version.element;
            }));

            // replace tbody content
            tbody.empty();
            versionsArray.forEach(function(version) {
                tbody.append(version);
            });
        }

        // setinterval until find tab-SoftwareVersion_1 (loaded by ajax)
        var interval = setInterval(() => {
            if ($('[id^="tab-SoftwareVersion_"] table.table tbody').length > 0) {
                clearInterval(interval);
                sortSoftwareVersion();
            }
        }, 100);
    }
});
