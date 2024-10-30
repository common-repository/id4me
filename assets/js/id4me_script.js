// Javascript start of authflow

function startAuth(translateString){
	let string = jQuery( "#id4me_identifier" ).val();
	authFlowAjaxCall( string , translateString );


	enableDisableButton(true, '');
}

function writeIntoInputFields(sub, iss, identifier) {
	document.getElementById("id4me_sub").value = sub;
	document.getElementById("id4me_iss").value = iss;
	document.getElementById("id4me_identifier").value = identifier;
}

// enable = false
// disable = true
function enableDisableButton(bool, translateString) {
	if (bool === false) {
		document.getElementById("auth").innerHTML = translateString;
		document.getElementById("auth").disabled = bool;
	} else {
		document.getElementById("auth").innerHTML = '<div class="loader"></div>';
		document.getElementById("auth").disabled = bool;
	}
}





function authFlowAjaxCall(string , translateString){

	var data = {
		'action': 'id4me_ajax_authflow',
		'identifier': string
	};
	jQuery.ajax(
		{
			type: "post",
			dataType: "json",
			data: data,
			url: responseObject.ajax_url,
			success: function (response) {
				if (response.success === false) {
					alert(response.data);
					enableDisableButton(response.success, translateString)
				} else {
					// https://stackoverflow.com/a/17744260
					let openDialog = function(uri, name, options, translateString ,closeCallback) {
						let win = window.open(uri, name, options);
						let interval = window.setInterval(function() {
							try {
								if (win == null || win.closed) {
									window.clearInterval(interval);
									closeCallback(translateString);
								}
							}
							catch (e) {
							}
						}, 1000);
						return win;
					};
					openDialog(response.data,'window' , 'width=500,height=500' , translateString , callback);
				}
			},
			error: function (jqXHR, exception) {
				enableDisableButton(false, translateString);
				ajaxErrorHandlingWithAlert(jqXHR, exception);
			}
		})
	;}

	function callback(translateString){
		enableDisableButton(false, translateString);
	}

function ajaxErrorHandlingWithAlert(errorObject , exception){

	let msg = ajaxErrorHandlingGetMessage(errorObject, exception);
	alert(msg);
}

function ajaxErrorHandlingWithErrorPost(errorObject , exception, errorMessages, fieldID, formID) {
	if (errorMessages != null)
	{
		if (typeof errorMessages === 'string' || errorMessages instanceof String){
			document.getElementById( fieldID ).value = errorMessages;
		}else{
			document.getElementById( fieldID ).value = JSON.stringify(errorMessages);
		}
	}
	else
	{
		let msg = ajaxErrorHandlingGetMessage(errorObject, exception);
		document.getElementById( fieldID ).value = JSON.stringify([msg]);
	}
	document.getElementById('id4me_input_given').value = true;
	document.getElementById( formID ).submit();
}

function ajaxErrorHandlingGetMessage(errorObject, exception) {

	let msg = '';
	if (errorObject != null && errorObject.status === 0) {
		msg = 'Not connect.\n Verify Network.';
	}
	else if (errorObject != null && errorObject.status == 404) {
		msg = 'Requested page not found. [404]';
	}
	else if (errorObject != null && errorObject.status == 500) {

		msg = 'Internal Server Error [500].';
	}
	else if (exception === 'parsererror') {
		msg = 'Requested JSON parse failed.';
	}
	else if (exception === 'timeout') {
		msg = 'Time out error.';
	}
	else if (exception === 'abort') {
		msg = 'Ajax request aborted.';
	}
	else if (errorObject != null) {
		msg = 'Uncaught Error.\n' + errorObject.responseText;
	}
	return msg;
}

function registrationAjaxCall(string) {
	var data = {
		'action': 'id4me_ajax_register',
		'id4me_identifier': string
	};
	jQuery.ajax(
		{
			type: "post",
			dataType: "json",
			data : data,
			url: responseObject.ajax_url,
			success: function(response){
				if ( response.success === false ) {
					ajaxErrorHandlingWithErrorPost(null, null, response.data, 'id4me_errors', 'registerform');
				}
				else {
					let childWindow = window.open( response.data, 'window','width=500,height=500' );
					childWindow.focus();
				}
			},
			error: function (jqXHR, exception) {
				ajaxErrorHandlingWithErrorPost(jqXHR, exception, null, 'id4me_errors', 'registerform');
			}
		})
}

const show = 1;
const hide = 0;

function showFieldsOrHide(showOrHide,item){
	var element =  jQuery('#'+item);
	if (typeof(element) != 'undefined' && element != null)
	{
		if(showOrHide == hide){
			element.css("cssText", "display: none");
		}else if (showOrHide == show){
			let lableId = "id4me-"+item;
			var map = { nickname: "Nickname", given_name: "Given name", family_name: "Family name", website: "Website" }
			let label = map[item];
			jQuery('#id4me-registerdiv').prepend( ' <label style="text-align: left" for="'+item+'" id="'+lableId+'">'+label+'<br><input type="text" name="'+item+'" id="'+item+'"/></label>');
		}

	}
}

function formBuilder(){
	jQuery('#id4me-input').remove();
	jQuery('#id4me-identifier').remove();
	jQuery('#user_email').parent().css("cssText", "display: block !important");
	jQuery('#user_login').parent().css("cssText", "display: block !important");
	jQuery('#id4me-registerdiv').prepend( '<input type="text" name="id4me_sub" id="id4me_sub" hidden/>');
	jQuery('#id4me-registerdiv').prepend( '<input type="text" name="id4me_iss" id="id4me_iss" hidden/>');
	jQuery('#id4me-registerdiv').prepend( '<input type="text" name="id4meIdentifier" id="id4meIdentifier" hidden/>');
	jQuery('#id4me-registerdiv').prepend( '<input type="text" value="true" name="id4me_flag" id="id4me_flag" hidden/>');
	jQuery('#wp-submit').prop("onclick", null);
}

function arrangeFields(showOrHide){
		var inputIds = ['nickname', 'given_name', 'family_name', 'website'];
		inputIds.forEach(function (item, index) {
			showFieldsOrHide(showOrHide, item)
		});
}

function showFilledInRegistrationForm(mail, login , nickname, givenname ,familyname, website, id4meIdentifier, id4meSub, id4meIss) {
	if (document.getElementById("login_error") != null)
		document.getElementById("login_error").remove();
	arrangeFields(show);
	formBuilder();

	let loginName = '';
	if(login === ''){
		loginName = id4meIdentifier;
	}else{
		loginName = login;
	}
	document.getElementById( "user_email" ).value = mail;
	document.getElementById( "user_login" ).value = loginName;
	document.getElementById( "nickname" ).value = nickname;
	document.getElementById( "given_name" ).value = givenname;
	document.getElementById( "family_name" ).value = familyname;
	document.getElementById( "website" ).value = website;
	document.getElementById( "id4me_sub" ).value = id4meSub;
	document.getElementById( "id4me_iss" ).value = id4meIss;
	document.getElementById( "id4meIdentifier" ).value = id4meIdentifier;
	jQuery('#wp-submit').prop("type", 'submit');
}


function report_errors(errors){
	ajaxErrorHandlingWithErrorPost(null, null, errors, 'id4me_errors', 'registerform');
}


function alert_errors(errors) {
	alert(errors.join('.\n'))
}
