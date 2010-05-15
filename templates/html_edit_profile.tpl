<script src="http://%loginza_host%/js/widget.js" type="text/javascript"></script>
<script type="text/javascript">
window.onload = function() {
	var loginza_login_row = document.getElementById('user_login').parentNode.parentNode;
	var loginza_tprofile = loginza_login_row.parentNode;

	var loginza_new_tr = document.createElement("tr");
	loginza_new_tr.innerHTML = '<th><label for="loginza_identity">Прикрепленный аккаунт:</label></th><td>%provider_ico%&nbsp;<b>%identity%</b> <a href="http://%loginza_host%/api/widget?token_url=%returnto_url%" class="loginza">изменить</a></td>';
	
	loginza_tprofile.insertBefore(loginza_new_tr, loginza_login_row.nextSibling);
	LOGINZA.init();
}
</script>