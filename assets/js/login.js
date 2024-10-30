function setCookie(cname, cvalue) {
	document.cookie = cname + "=" + encodeURI(cvalue) + ";path=/";
}

function deleteCookie( name ) {
	var string = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
	document.cookie = string;
}

function getCookie(cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i = 0; i < ca.length; i++) {
	  var c = ca[i];
	  while (c.charAt(0) == ' ') {
		c = c.substring(1);
	  }
	  if (c.indexOf(name) == 0) {
		return decodeURI(c.substring(name.length, c.length));
	  }
	}
	return "";
}

function showID4me(){
	console.log("show");

	jQuery('.hiddenform').show();
	jQuery('#loginform').children().css("cssText", "display: none !important");
	jQuery('#id4me-logindiv').show();
	jQuery('#id4me-button-anchor').css("display", "none");
}


function registerID4me(){
	console.log("register");

	jQuery('.hiddenform').show();
	jQuery('#registerform').children().css("cssText", "display: none !important");
	jQuery('#id4me-registerdiv').show();
	jQuery('#id4me-button-anchor').css("display", "none");
}

function hideID4me(){
	console.log("hide")
	jQuery('#loginform').children().show();
	jQuery('script').css("cssText", "display: none !important");
	jQuery('.hiddenform').hide();
	jQuery('#id4me-button-anchor').css("display", "inline-block");
}

function showID4meReset(user){
	jQuery('#id4me-button-reset').show();
	jQuery('#id4me-button-text').html(" Login as " + user);
}

function handleCookie(user){
	if(jQuery('#id4me-checkbox').prop('checked') && jQuery('#id4me-input').val()!=""){
		setCookie("id4me.user", jQuery('#id4me-input').val());
		user = getCookie("id4me.user");
		jQuery('#id4me-button-text').html(" Login as " + user);
		jQuery('#id4me-input2').attr("value", user);
	}else{
		jQuery('#id4me-input2').attr("value", jQuery('#id4me-input').val());
		deleteCookie("id4me.user");
		user = "";
		jQuery('#id4me-input').attr("value", user);
		jQuery('#id4me-button-text').html("Log in with ID4me");
		jQuery('#id4me-button-reset').hide();
	}
	return user;
}

function handleButton(user, event){
	//default
	console.log(event.currentTarget.id);
	switch (event.currentTarget.id) {
		case 'id4me-button-reset' :
				if (event.keyCode === 13 || event.type == "click") {
						showID4me();
						jQuery('#id4me-button-reset').hide();
						deleteCookie("id4me.user");
						if (event.type == "click") {jQuery('#id4me-input').focus();}
				}
		break;
		case 'id4me-button-wp' :
				if (event.keyCode === 13 || event.type == "click") {
						hideID4me();
				}
		break;
		case 'id4me-input-signin' :
				user = handleCookie(user);
				jQuery('#id4me-loginform').submit();
				jQuery('#id4me-button-reset').hide();
		break;
 }
 //with cookie
 if (user != "") {
			switch (event.currentTarget.id) {
				case 'id4me-button-anchor' :
						if (event.keyCode === 13 || event.type == "click") {
								jQuery('#id4me-loginform').submit();
						}
				break;
				case 'id4me-button-wp' :
						if (event.keyCode === 13 || event.type == "click") {
								showID4meReset(user);
						}
				break;
	   }
 }else{
		switch (event.currentTarget.id) {
				case 'id4me-input-registration':
					if (event.keyCode === 13) {
						event.preventDefault();
						startRegistration();
					}
				case 'id4me-button-anchor' :
						if (event.keyCode === 13 || event.type == "click") {
								showID4me();
								registerID4me();
								if (event.type == "click") {jQuery('#id4me-input').focus();}
						}
				break;
				case 'id4me-button-wp' :
						if (event.keyCode === 13 || event.type == "click") {
								jQuery('#id4me-button-reset').hide();
						}
				break;
				case 'id4me-checkbox' :
						if (event.keyCode === 13) {
							jQuery('#id4me-checkbox').attr('checked', true);
							jQuery('#id4me-input-signin').focus();
						}
				break;
		}
 	}
}

jQuery(document).ready(function(){
	//default start
	var user = '';
	var urlParams = new URLSearchParams(window.location.search);
	if(urlParams.get('action') != "register") {
		var user = getCookie("id4me.user");
	}
	//arrangeFields(hide);

	if (user != '')
	{
		jQuery('#id4me-input').attr("value", user);
		jQuery('#id4me-input2').attr("value", user);
	}
	//cookie, error start
	if (user != "") {showID4meReset(user)}
	if (jQuery(".id4me_error").is(":visible") == true){
			showID4me();
			jQuery('#id4me-button-reset').hide();
			jQuery('#id4me-input2').attr("value", jQuery('#id4me-input').val());
	}

	//handle buttons
	jQuery('#id4me-button-anchor').click(function(event) {
			jQuery('#id4me-input2').attr("value", jQuery('#id4me-input').val());
			handleButton(user, event);});
	jQuery('#id4me-button-anchor').keydown(function(event) {
			jQuery('#id4me-input2').attr("value", jQuery('#id4me-input').val());
			handleButton(user, event);});
	jQuery('#id4me-input-registration').keydown(function(event) {
		handleButton(user, event);
	});
	jQuery('#id4me-button-reset').click(function(event) {
			handleButton(user, event);});
	jQuery('#id4me-button-reset').keydown(function(event) {
			handleButton(user, event);});
	jQuery('#id4me-button-wp').click(function(event) {
			user = handleCookie();
			handleButton(user, event);});
	jQuery('#id4me-button-wp').keydown(function(event) {
			user = handleCookie();
			handleButton(user, event);});
	jQuery('#id4me-checkbox').keydown(function(event) {
			handleButton(user, event);});
	jQuery('#id4me-input-signin').click(function(event){
			user = handleCookie(user);
			handleButton(user, event);});

	//On Input autocomplete
	jQuery('#id4me-input').on('input', function() {
			jQuery('#id4me-button-reset').hide();
			user = handleCookie(user);
	});

	if(urlParams.get('action') == "register" && jQuery( "#id4me_input_given" ).val() == 'true') {
		showID4me();
		registerID4me();
	}
});

function getURL() {
	return window.location.href;
}

function startRegistration() {
	let string = jQuery( "#id4me-input-registration" ).val();
	registrationAjaxCall(string);
}

