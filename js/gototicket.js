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

$(document).ready(function () {
	if ($(window).width() > 700 && !document.getElementById('yagp_form')) {
		if ($("body").hasClass("vertical-layout")) {
			$('header div div:first').before("<div class ='ms-lg-auto d-none d-lg-block flex-grow-1 flex-lg-grow-0'><form id='yagp_form' style='margin-right:1%;width:140px;'><div class='input-group input-group-flat'><input id='goto' class='form-control' type='number' placeholder='id'><span class='input-group-text'><button type='submit' class='btn btn-link p-0 m-0'><span class='fas fa-external-link-alt' aria-hidden='true'></span></button></span></div></form></div>");
		} else {
			$('div.navbar div form[role="search"]').parent().before("<div class ='ms-lg-auto d-none d-lg-block flex-grow-1 flex-lg-grow-0'><form id='yagp_form' style='margin-right:1%;width:140px;'><div class='input-group input-group-flat'><input id='goto' class='form-control' type='number' placeholder='id'><span class='input-group-text'><button type='submit' class='btn btn-link p-0 m-0'><span class='fas fa-external-link-alt' aria-hidden='true'></span></button></span></div></form></div>");
		}

		var color = $('#champRecherche input').css("color");
		var background = $('#champRecherche input').css("background-color");
		var border = $('#champRecherche input').css("border");
		var height = $('#champRecherche input').css("height");
		var width = $('#champRecherche input').css("width");
		$('#goto').css({ "color": color, "background-color": background, "border": border, "height": height, "width": width });
		var radius = $('#champRecherche button').css("border-radius");
		var background = $('#champRecherche button').css("background-color");
		var border = $('#champRecherche button').css("border");
		var height = $('#champRecherche button').css("height");
		var align = $('#champRecherche button').css("vertical-align");
		$('#yagp_form button').css({ "border-radius": radius, "background-color": background, "border": border, "height": height, "vertical-align": align });
		$('#yagp_form').submit(function (e) {
			e.preventDefault(e);
			var id = $('#goto').val();
			window.location.href = CFG_GLPI.url_base + "/index.php?redirect=ticket_" + id + "&noAUTO=1";
		});
	}

});