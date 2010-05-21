<?php
/*
Copyright 2010 Sergey Arsenichev  (email: s.arsenichev@protechs.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*
Plugin Name: loginza
Plugin URI: http://loginza.ru/wp-plugin
Description: Плагин позволяет использовать аккаунты популярных web сайтов (Вконтакте, Yandex, Google и тп. и OpenID) для авторизации в блоге. Разработан на основе сервиса Loginza.
Version: 1.0.4
Author: Sergey Arsenichev
Author URI: http://loginza.ru
*/
// настройки
define('LOGINZA_SERVER_HOST', 'loginza.ru');
define('LOGINZA_API_AUTHINFO', 'http://'.LOGINZA_SERVER_HOST.'/api/authinfo');
define('LOGINZA_HOME_DIR', dirname(dirname(dirname(dirname(__FILE__)))).'/');
define('LOGINZA_PLUGIN_DIR', realpath(dirname(__FILE__)).'/');
define('LOGINZA_TEMPLATES_DIR',LOGINZA_PLUGIN_DIR.'templates/');
define('LOGINZA_FORM_TAG', 'loginza');

// рабочие классы
require_once LOGINZA_PLUGIN_DIR.'LoginzaWpUser.class.php';

// WP инклуды
if (file_exists(LOGINZA_HOME_DIR.'wp-load.php')) {
  // WP 2.6
  require_once(LOGINZA_HOME_DIR.'wp-load.php');
} else {
  // Before 2.6
  require_once(LOGINZA_HOME_DIR.'wp-config.php');
}
require_once(LOGINZA_HOME_DIR . 'wp-includes/registration.php');

// инициализация плагина
add_action('wp_footer', 'loginza_ui_comment_form');
add_action('login_head', 'loginza_ui_login_form');
add_action('show_user_profile', 'loginza_ui_user_profile');
add_action('parse_request', 'loginza_token_request'); 
add_filter('get_comment_author_link', 'loginza_comment_author_icon');
add_filter('get_avatar', 'loginza_comment_author_avatar');
add_filter('the_content', 'loginza_form_tag');

/**
 * Модификация интерфейса WP
 * Заменяет стандартный интерфейс авторизации в комментариях, на интерфейс Loginza
 * 
 */
function loginza_ui_comment_form () {
	$WpUser = wp_get_current_user();
	// если пользователь авторизирован, то форму не показываем
  	if($WpUser->ID) return;
  	
  	// данные для шаблона
  	$tpl_data = array(
  		'returnto_url' => urlencode( get_option('siteurl').$_SERVER['REQUEST_URI'] ),
  		'loginza_host' => LOGINZA_SERVER_HOST
  	);
  	// модификация текущей формы авторизации
  	echo loginza_fetch_template('html_comment_login_form.tpl', $tpl_data);
}
/**
 * Модификация интерфейса логин формы WP
 * Подставляет в главное окно авторизации (wp-login.php) виджет Loginza
 *
 */
function loginza_ui_login_form () {
	$WpUser = wp_get_current_user();
	
	$return_to = get_option('siteurl');
	if($WpUser->ID) {
		$return_to .= '/?loginza_mapping='.$WpUser->ID;
	}
	// данные для шаблона
  	$tpl_data = array(
  		'returnto_url' => urlencode($return_to),
  		'loginza_host' => LOGINZA_SERVER_HOST
  	);
	echo loginza_fetch_template('html_main_login_form.tpl', $tpl_data);
}
/**
 * Модификация страницы профиля пользователя
 * Добавляет "Прикрепленный аккаунт" и ссылку "изменить"
 *
 * @return unknown
 */
function loginza_ui_user_profile () {
	$user = wp_get_current_user();
	if(!$user->ID) {
		return false;
	}
	$tpl_data = array();
	if ($user->{LOGINZA_WP_USER_META_IDENTITY}) {
		$tpl_data = array(
			'identity' => $user->{LOGINZA_WP_USER_META_IDENTITY},
			'provider' => $user->{LOGINZA_WP_USER_META_PROVIDER},
			'provider_ico' => loginza_get_provider_ico ($user->{LOGINZA_WP_USER_META_IDENTITY}),
		);
	} else {
		$tpl_data = array(
			'identity' => '<i>(пусто)</i>',
			'provider' => '',
			'provider_ico' => '',
		);
	}
	$tpl_data['returnto_url'] = urlencode( get_option('siteurl').'/?loginza_mapping='.$user->ID.'&loginza_return='.urlencode($_SERVER['REQUEST_URI']) );
	$tpl_data['loginza_host'] = LOGINZA_SERVER_HOST;
	echo loginza_fetch_template('html_edit_profile.tpl', $tpl_data);
}
/**
 * Фильтр вывода автора комментария
 * Выводит иконку провайдера (ВКонтакте, Яндекс и тп.), через которого был авторизирован пользователь.
 *
 * @param unknown_type $author
 * @return unknown
 */
function loginza_comment_author_icon ($author) {
	global $comment;
	
	// получаем идентификатор Loginza пользователя
	$identity = LoginzaWpUser::getIdentityByUser($comment->user_id);
	
	// ищем имя хоста
	if ($identity) {
		return loginza_get_provider_ico ($identity).'&nbsp;'.$author;
	}
	
	return $author;
}
/**
 * Добавление аватарки в комментарий
 * Если у пользователя есть аватарка, то выводит ее в комментарии
 *
 * @param string $avatar
 * @return string
 */
function loginza_comment_author_avatar ($avatar) {
	global $comment;
	
	// получаем аватар Loginza пользователя
	$loginza_avatar = LoginzaWpUser::getAvatarByUser($comment->user_id);
	
	if (!empty($loginza_avatar)) {
		return preg_replace('/src=([^\s]+)/i', 'src="'.$loginza_avatar.'"', $avatar);
	}
	return $avatar;
}
/**
 * Получает иконку провайдера
 * Возвращает html код иконки провайдера по идентификатору учетной записи. 
 * По умолчанию, если хост не определен, возвращает OpenID иконку.
 *
 * @param string $identity
 * @return string
 */
function loginza_get_provider_ico ($identity) {
	// соответствие хоста провайдера к имени иконки
	$providers = array(
	'yandex.ru' => 'yandex.png',
	'ya.ru' => 'yandex.png',
	'vkontakte.ru' => 'vkontakte.png',
	'vk.com' => 'vkontakte.png',
	'loginza.ru' => 'loginza.png',
	'myopenid.com' => 'myopenid.png',
	'livejournal.com' => 'livejournal.png',
	'google.ru' => 'google.png',
	'google.com' => 'google.png',
	'flickr.com' => 'flickr.png',
	'mail.ru' => 'mailru.png',
	'rambler.ru' => 'rambler.png',
	'webmoney.ru' => 'webmoney.png',
	'webmoney.com' => 'webmoney.png',
	'wmkeeper.com' => 'webmoney.png',
	'wordpress.com' => 'wordpress.png',
	'blogspot.com' => 'blogger.png',
	'diary.ru' => 'diary',
	'bestpersons.ru' => 'bestpersons.png',
	'facebook.com' => 'facebook.png',
	'twitter.com' => 'twitter.png'
	);
	
	if (preg_match('/^https?:\/\/([^\.]+\.)?([a-z0-9\-\.]+\.[a-z]{2,5})/i', $identity, $matches)) {
		$icon_dir = get_option('siteurl').'/wp-content/plugins/loginza/img/';
		$provider_key = $matches[2];
		
		// если есть иконка для провайдера
		if (array_key_exists($provider_key, $providers)) {
			return '<img src="'.$icon_dir.$providers[$provider_key].'" alt="'.$provider_key.'" align="top" class="loginza_provider_ico"/>';
		}
	}
	return '<img src="'.$icon_dir.'openid.png" alt="OpenID" align="top" class="loginza_provider_ico"/>';
}
/**
 * Обработка тегов для вставки авторизации Loginza в страницы блога
 * Доступные теги:
 * [loginza]текст ссылки[/loginza]
 * [loginza:iframe]
 * [loginza:icons]
 *
 * @param string $message Содержимое страницы
 * @return string Содержимое страницы после обработки тегов
 */
function loginza_form_tag ($message) {
	if (!empty($message)) {
		$tpl_data = array ('loginza_host' => LOGINZA_SERVER_HOST, 'returnto_url' => urlencode(get_option('siteurl')));
		$message .= loginza_fetch_template('html_widget_js.tpl', $tpl_data);
		// [loginza]текст ссылки[/loginza]
		$message = preg_replace('/\['.LOGINZA_FORM_TAG.'\](.+)\[\/'.LOGINZA_FORM_TAG.'\]/is', '<a href="http://'.LOGINZA_SERVER_HOST.'/api/widget?token_url='.$tpl_data['returnto_url'].'" class="loginza">\1</a>', $message);
		// [loginza:iframe]
		$message = preg_replace('/\['.LOGINZA_FORM_TAG.'\:iframe\]/is', loginza_fetch_template('html_iframe_form.tpl', $tpl_data), $message);
		// [loginza:icons]
		$message = preg_replace('/\['.LOGINZA_FORM_TAG.'\:icons\]/is', loginza_fetch_template('html_icons_form.tpl', $tpl_data), $message);
	}
	return $message;
}
/**
 * Работа с шаблонами
 *
 * @param string $template Имя файла шаблона
 * @param array $data Значения для подстановки в шаблон
 * @return string Шаблон с подставленными значениями
 */
function loginza_fetch_template ($template, $data=null) {
	if (is_array($data)) {
		$data = loginza_fetch_template_data($data);
		return strtr(file_get_contents(LOGINZA_TEMPLATES_DIR.$template), $data);
	}
	
	return file_get_contents(LOGINZA_TEMPLATES_DIR.$template);
}
/**
 * Предобработка данных шаблона
 * Изменяет ключи массива (key -> %key%)
 *
 * @param array $data
 * @return array
 */
function loginza_fetch_template_data ($data) {
	$result = array();
	foreach ($data as $k => $v) {
		$result["%$k%"] = $v;
	}
	return $result;
}
/**
 * Обработка авторизации Loginza
 * Получает значение token и извлекает по нему профиль пользователя
 * через API запрос к Loginza.
 *
 */
function loginza_token_request () {
	global $wpdb;
	//var_dump($_REQUEST);
	if (empty($_REQUEST['token'])) {
		return;
	}
	
	// получение профиля
	$profile = file_get_contents(LOGINZA_API_AUTHINFO.'?token='.$_POST['token']);
	$profile = json_decode($profile);
	
	// проверка на ошибки
	if (!is_object($profile) || !empty($profile->error_message) || !empty($profile->error_type)) {
		return;
	}
	
	// получаем текущего пользователя
	$WpUser = wp_get_current_user();
	
	// если юзер авторизирован, прикрепляем к нету его идентификатор
	if ($WpUser->ID && @$_REQUEST['loginza_mapping'] == $WpUser->ID) {
		// проверяем если данный идентификатор в базе
		$wpuid = LoginzaWpUser::getUserByIdentity($profile->identity, $wpdb);
		// такой идентификатор не прикреплен ни к кому
		if (!$wpuid) {
			// прикрепляем к нему идентификатор
			LoginzaWpUser::setIdentity($WpUser->ID, $profile);
		}
	} elseif (!$WpUser->ID) {
		// проверяем если данный идентификатор в базе
		$wpuid = LoginzaWpUser::getUserByIdentity($profile->identity, $wpdb);
		
		if (!$wpuid) {
			// идентификатора нет, новый пользователь
			$wpuid = LoginzaWpUser::create($profile);
		}
		
		// авторизируем нового пользователя
  		wp_set_auth_cookie($wpuid, true, false);
  		wp_set_current_user($wpuid);
	}
	
	if (!empty($_REQUEST['loginza_return'])) {
		$return_to = $_REQUEST['loginza_return'];
	} else {
		$return_to = $_SERVER['REQUEST_URI'];
	}
	// редирект
	wp_safe_redirect(get_option('siteurl').$return_to);
	die();
}
?>