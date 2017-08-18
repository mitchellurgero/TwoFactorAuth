/**
 * GNU social - a federating social network
 *
 * Plugin for two-factor authentication
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugin
 * @author    Saul St John <saul.stjohn@gmail.com>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 */

function totp_qr_popup()
{
	$('<img src="/main/2fa/totp/reg.png" />').load(function() {
		$('<div title="TOTP QR code"/>')
			.append(this)
			.dialog({
				modal: true,
				closeOnEscape: true,
				height: "auto",
				width: "auto",
			});
	});
}

function totp_key_popup()
{
	$.getJSON("/main/2fa/totp/reg.js", function(data) {
		$('<div title="' + data.label + '\'s TOTP key"/>')
			.html("<center>" + data.key + "</center>")
			.dialog({
				modal: true,
				closeOnEscape: true,
				height: "auto",
				width: "auto",
			});
	});
}

function totp_do_reset(dialog)
{
	$.post("/settings/2fa", {
			reset_totp_key: "Reset Key",
			token: $("#token-two_factor_auth_settings").val(),
		}, function(data) {
			$(dialog).dialog("close");
		}
	);
}

function totp_reset_confirm()
{
	$("<div title='Warning'/>")
		.html("<p>Resetting your TOTP key will invalidate any existing"
			+ " token generators configured for this account.</p>"
			+ "<p>Are you sure you want to continue?</p>")
		.dialog({
			modal: true,
			closeOnEscape: true,
			buttons: [
			{
				text: "Yes",
				click: function() {
					totp_do_reset(this);
				}
			}, {
				text: "No",
				click: function() {
					$(this).dialog("close");
				}
			}],
		});
}

function u2f_details_popup()
{
	$.getJSON(this, function(data) {
		var inner = "<form class='form_settings'><fieldset><fieldset>"
				+ "<legend>Enrollment Record</legend><table>";
		for (var field of ['keyHandle', 'publicKey', 'counter', 
				'applicationId', 'enrolled_str']) {
			inner += "<tr><td><label>"
				+ field.replace(
					/([a-z]*)([A-Z][a-z]*)?(_[a-z]*)?/,
					function(match, p1, p2) {
						return p1[0].toUpperCase()
							+ p1.slice(1)
							+ (p2 !== undefined ?
							   " " + p2 : "");
					})
				+ "</label></td><td>" 
				+ data[field] 
				+ "</td></tr>";
		}
		var certVersion = parseInt(data.certificateDetails.version);
		var serialNumber = parseInt(data.certificateDetails.serialNumber);
		inner += "</table></fieldset><fieldset>"
			+ "<legend>Attestation Certificate</legend>"
			+ "<table>"
			+ "<tr><td><label>Version:</label></td><td>"
			+ (certVersion + 1).toString(10)
			+ " (0x" + certVersion.toString(16) + ")"
			+ "</td></tr><tr>"
			+ "<td><label>Serial Number:</label></td><td>"
			+ serialNumber.toString(10)
			+ " (0x" + serialNumber.toString(16) + ")"
			+ "</td></tr><tr><td><label>Issuer:</label></td><td>"
			+ "CN=" + data.certificateDetails.issuer.CN
			+ "</td></tr><tr><td><label>Valid from:</label></td>"
			+ "<td>" + data.certificateDetails.validFrom_str
			+ "</td></tr><tr><td><label>Valid until:</label></td>"
			+ "<td>" + data.certificateDetails.validTo_str
			+ "</td></tr><tr><td><label>Signature Type:</label>"
			+ "</td><td>" + data.certificateDetails.signatureTypeLN
			+ "</td></tr><tr><td><label>Subject:</label></td><td>"
			+ "CN=" + data.certificateDetails.subject.CN
			+ "</td></tr>"
			+ "</table></fieldset>"
			+ "</fieldset></form>";

		var dlg = $('<div title="U2F Device Details"/>')
			.html(inner);

		dlg.find('tr td:first-child').css('padding-right', '20px');
		dlg.dialog({modal: true,
				closeOnEscape: true,
				width: 'fit',
		});
	});
	return false;
}


function fido2_details_popup()
{
	$.getJSON(this, function(data) {
		var inner = "<form class='form_settings'><fieldset><fieldset>"
				+ "<legend>Enrollment Record</legend><table>";
		for (var field of ['id', 'algorithm', 'type', 'enrolled_str']) {
			inner += "<tr><td><label>"
				+ field.replace(
					/([a-z]*)([A-Z][a-z]*)?(_[a-z]*)?/,
					function(match, p1, p2) {
						return p1[0].toUpperCase()
							+ p1.slice(1)
							+ (p2 !== undefined ?
							   " " + p2 : "");
					})
				+ "</label></td><td>" 
				+ data[field] 
				+ "</td></tr>";
		}
        
		inner += "</table></fieldset><fieldset>"
			+ "<legend>Public Key</legend>"
			+ "<table>";
            
        for (var field of ['kty', 'alg', 'ext', 'n', 'e']) {
			inner += "<tr><td><label>"
				+ field.replace(
					/([a-z]*)([A-Z][a-z]*)?(_[a-z]*)?/,
					function(match, p1, p2) {
						return p1[0].toUpperCase()
							+ p1.slice(1)
							+ (p2 !== undefined ?
							   " " + p2 : "");
					})
				+ "</label></td><td>" 
				+ data["publicKey"][field] 
				+ "</td></tr>";
		}
	    inner += "</table></fieldset>"
		  + "</fieldset></form>";
        
		var dlg = $('<div title="FIDO 2.0 Credential Details"/>')
			.html(inner);

		dlg.find('tr td:first-child').css('padding-right', '20px');
		dlg.dialog({modal: true,
				closeOnEscape: true,
				width: 'fit',
		});
	});
	return false;
}

function build_codes_list(data)
{
	var list = "<ul style=\"" 
		+ "columns:2;-webkit-columns:2;-moz-columns:2;"
		+ "list-style-type:none;"
		+ "\">";
	for (var code of data.codes) {
		list += "<li><b>"
			+ code.substring(0, 3)
			+ "-"
			+ code.substring(3, 5)
			+ "-"
			+ code.substring(5)
			+ "</b></li>";
	}

	return list;
}
	

function backup_codes_popup()
{
	$.getJSON(this, function(data) {
		$('<div title="' + data.user + '\'s Backup Codes"/>')
			.html(build_codes_list(data))
			.dialog({
				modal: true,
				closeOnEscape: true,
			});

	});
	return false;
}

function do_backup_codes_reset(dlg)
{
	$.getJSON($("#reset_backup_codes").prop('href'))
		.success(function (data) {
			var code_list = build_codes_list(data);
			$(dlg).dialog("close");
			$('<div title="' + data.user + '\'s Backup Codes"/>')
				.html(code_list)
				.dialog({
					modal: true,
					closeOnEscape: true,
				});
		});
}

function backup_codes_reset()
{
	$('<div title="Warning!"/>')
		.html("<p>Resetting your secondary authentication backup "
			+ "codes you may have stored.</p><p>Are you sure "
			+ "you want to continue?</p>")
		.dialog({
			modal: true,
			closeOnEscape: true,
			buttons: [
			{
				text: "Yes",
				click: function() {
					do_backup_codes_reset(this);
				}
			}, {
				text: "No",
				click: function() {
					$(this).dialog("close");
				}
			}],
		});
	return false;
}

function display_app_pw(pw)
{
	var display_string = '<h5 style="text-align:center;"><b>'
		+ pw.substring(0,4) + " "
		+ pw.substring(4,8) + " "
		+ pw.substring(8,12) + " "
		+ pw.substring(12,16) + "</b></h5>";
	$('<div title="Generated Password" />')
		.html(display_string)
		.dialog({
			buttons: [{
				text: 'Dismiss',
				click: function() {
					$(this).dialog('close');
				},
			}],
			close: function() {
				location.reload();
			},
			closeOnEscape: true,
			modal: true,
		});
}

function generate_app_pw(dlg)
{
	var app_name = $(dlg).find("#app_name").val().trim();
	$(dlg).find(".error").remove();
	if (app_name == "") {
		$(dlg).append('<div class="error">'
			+ 'Name cannot be blank'
			+ '</div>');
	} else {
		$.ajax({
			method: "POST",
			dataType: "json",
			data: {
				token: $("#token-two_factor_auth_settings").val(),
				app: app_name,
			},
			url: $("#new_app_pw").prop('href'),
		}).then(null, function(xhr, ts, error) {
			return $.Deferred().reject(xhr.responseJSON, ts, xhr);
		}).always(function(data, textStatus, xhr) {
			if (data.success) {
				$(dlg).dialog("close");
				display_app_pw(data.password);
			} else {
				$(dlg).append('<div class="error">'
					+ xhr.responseJSON.result.error
					+ "</div>");
			}
		});
	}
}

function new_app_popup()
{
	var inner = 
		"<form class='form_settings'><fieldset>"
		+ "<label for=\"app_name\">Application name:</label>"
		+ '<input id="app_name" type="text" maxlength="64"/>'
		+ "</fieldset></form>";
	$('<div title="New app password" />')
		.html(inner)
		.dialog({
			modal: true,
			closeOnEscape: true,
			buttons: [{
				text: "Generate",
				click: function() {
					generate_app_pw(this);
				},
			}],
		});
	return false;
}

function do_revoke_app(dlg, app_name, revocation_url)
{
	$.ajax({
		method: "POST",
		dataType: "json",
		data: {
			token: $("#token-two_factor_auth_settings").val(),
			app: app_name,
		},
		url: revocation_url,
	}).then(null, function(xhr, ts, error) {
		return $.Deferred().reject(xhr.responseJSON, ts, xhr);
	}).always(function(data, textStatus, xhr) {
		if (data.success) {
			location.reload();
		} else {
			$(dlg).append('<div class="error">'
				+ data.result.error
				+ '</div>');
		}
	});
}

function revoke_app_popup()
{
	var revocation_url = this;
	var app_name = 
		$(this).closest('tr').find('div.app-name').text();

	$('<div title="Warning!" />')
		.html("<p>Revoking this application password will prevent the "
			+ "\"" + app_name + "\" application from logging in."
			+ "</p><p>Are you sure you want to continue?</p>")
		.dialog({
			modal: true,
			closeOnEscape: true,
			buttons: [{
				text: "Yes",
				click: function() {
					do_revoke_app(
						this, app_name, revocation_url
					);
				}
			    }, {
				text: "No",
				click: function() {
					$(this).dialog("close");
				}
			}],
		});
	return false;
}

function prepare()
{
	$('#show_totp_qr').click(totp_qr_popup);
	$('#show_totp_key').click(totp_key_popup);
	$('#reset_totp_key').click(totp_reset_confirm);
	$('.u2f-details-link').click(u2f_details_popup);
    $('.fido2-details-link').click(fido2_details_popup);
	$('#show_backup_codes').click(backup_codes_popup);
	$('#reset_backup_codes').click(backup_codes_reset);
	$('#new_app_pw').click(new_app_popup);
	$('.app-revoke').click(revoke_app_popup);
}

$(prepare);
