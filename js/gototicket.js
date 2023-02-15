
$(document).ready(function () {
	if ($(window).width() > 700) {
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