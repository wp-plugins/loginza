<script type="text/javascript">
window.onload = function() {
	var loginza_wp_login = document.getElementById('loginform');
	loginza_wp_login.style.width = "359px";
	var loginza_loading = document.createElement("div");
	loginza_loading.id = "loginza_loading";
	loginza_loading.style.width = "359px";
	loginza_loading.style.marginTop = "112px";
	loginza_loading.style.paddingBottom = "122px";
	loginza_loading.style.textAlign = "center";
	loginza_loading.innerHTML = '<img src="/wp-content/plugins/loginza/img/loading.gif" alt="Loading..."/>';
	
	var loginza_iframe = document.createElement("iframe");
	loginza_iframe.id = "loginza_iframe";
	loginza_iframe.src = "https://%loginza_host%/api/widget?overlay=wp_plugin&token_url=%returnto_url%";
	loginza_iframe.style.display = "none";
	loginza_iframe.style.width = "359px";
	loginza_iframe.style.height = "300px";
	loginza_iframe.scrolling = "no";
	loginza_iframe.frameBorder = "no";
	loginza_iframe.onload = function () {
		this.style.display = "";
		loginza_loading.style.display = "none";
	}
	var loginza_header = document.createElement("div");
	loginza_header.id = "loginza_header";
	loginza_header.innerHTML ="<h3>Или войдите с используя Ваш логин и пароль:</h3><br/>";
	
	loginza_wp_login.insertBefore(loginza_header, loginza_wp_login.firstChild);
	loginza_wp_login.insertBefore(loginza_iframe, loginza_wp_login.firstChild );
	loginza_wp_login.insertBefore(loginza_loading, loginza_wp_login.firstChild );
}
</script>