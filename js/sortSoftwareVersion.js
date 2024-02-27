/**
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
