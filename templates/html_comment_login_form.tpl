<style>
a.loginza:hover {text-decoration:none;}
a.loginza img {border:0px;margin-right:3px;}
</style>
<script src="http://%loginza_host%/js/widget.js" type="text/javascript"></script>
<script type="text/javascript">
var loginza_auth = document.createElement("div");
loginza_auth.id = "loginza_comment";
loginza_auth.innerHTML = 'Также Вы можете войти используя: <a href="http://%loginza_host%/api/widget?token_url=%returnto_url%" class="loginza">'+
	'<img src="/wp-content/plugins/loginza/img/yandex.png" alt="Yandex" title="Yandex"/>&nbsp;<img src="/wp-content/plugins/loginza/img/google.png" alt="Google" title="Google Accounts"/>&nbsp;'+
	'<img src="/wp-content/plugins/loginza/img/vkontakte.png" alt="Вконтакте" title="Вконтакте"/>&nbsp;<img src="/wp-content/plugins/loginza/img/mailru.png" alt="Mail.ru" title="Mail.ru"/>&nbsp;'+
	'<img src="/wp-content/plugins/loginza/img/loginza.png" alt="Loginza" title="Loginza"/>&nbsp;<img src="/wp-content/plugins/loginza/img/myopenid.png" alt="MyOpenID" title="MyOpenID"/>&nbsp;'+
	'<img src="/wp-content/plugins/loginza/img/openid.png" alt="OpenID" title="OpenID"/>&nbsp;<img src="/wp-content/plugins/loginza/img/webmoney.png" alt="WebMoney" title="WebMoney"/>&nbsp;'+
	'</a>';
var commentForm = document.getElementById("comment");
if (commentForm) {
	commentForm.parentNode.insertBefore(loginza_auth, commentForm);
}
</script>