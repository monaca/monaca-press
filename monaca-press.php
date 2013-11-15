<?php
/*
Plugin Name: MonacaPress
Plugin URI: https://github.com/monaca/monaca-press
Description: MonacaPress plug-in is WordPress app development support tool.
Version: 0.1.0
Author: YUKI OKAMOTO (HN:Justice)
Author URI: http://monaca.mobi/
Tags: iOS,Android,mobile,app
Requires at least: 3.6.0
Tested up to: 3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('admin_menu', 'monaca_press_admin_menu');
register_activation_hook( __FILE__, 'monaca_press_activate');

register_uninstall_hook(__FILE__, 'monaca_press_uninstall_hook');

function monaca_press_uninstall_hook()
{
  delete_option('monaca_press_projects');
}

function monaca_press_activate() 
{
  $projects = get_option('monaca_press_projects');
  $projects['monaca-post'] = array(
    'path' => __file__,
    'info' => 'MonacaPost is Mobile Application for Post (and "Page" and "Custom Post Type").
    after you shoud check "js/config.js" and rewreite some configration.
    see http://press.monaca.mobi/ best regards.'
  );
  update_option('monaca_press_projects', $projects);
}

function monaca_press_admin_menu() 
{
  add_menu_page(
    'MonacaPress',
    'MonacaPress',
    'administrator',
    'monaca_press_admin_menu',
    'monaca_press_setting'
  );
}

/**
 *  Check Paramas and upload project files. 
 */
function monaca_press_setting() 
{
  if (isset($_POST['submit']) && $_POST['submit']) {
    // check email
    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
      wp_die('need valid email');
    }
    // check password
    if (!isset($_POST['password']) || strlen($_POST['password']) == 0) {
      wp_die('need password');
    }
    // check webdav url 
    if (!isset($_POST['webdav']) || !preg_match('/^https:\/\/dav-.*monaca.mobi$/', $_POST['webdav'])) {
      wp_die('webdav url format must be https://dav-******.monaca.mobi');
    }
    // check project path
    if (!isset($_POST['project']) || strlen($_POST['project']) == 0) {
      wp_die('need project');
    }

    // check project dir
    $projects = get_option('monaca_press_projects');
    $project_path = $projects[$_POST['project']]['path'];
    $project_path = dirname($project_path)."/project/";
    if (!is_dir($project_path)) {
      wp_die('project path is dead "' .$project_path. '".');
    }

    // save params 
    if (isset($_POST['save']) && $_POST['save'] === '1') {
      update_option('monaca_setting_email', $_POST['email']);
      update_option('monaca_setting_password', $_POST['password']);
      update_option('monaca_setting_webdav', $_POST['webdav']);
      update_option('monaca_setting_save', 1);
    } else {
      delete_option('monaca_setting_email');
      delete_option('monaca_setting_password');
      delete_option('monaca_setting_webdav');
      delete_option('monaca_setting_save');
    }

    // upload
    $monaca_uploader = new monaca_uploader($_POST['email'], $_POST['password'], $_POST['webdav'], $project_path);
    $monaca_uploader->upload();
  }

  // load params
  $setting = array('save' => '', 'email' => '', 'password' => '', 'webdav' => '');
  $setting['save'] = get_option('monaca_setting_save');
  $setting['email'] = get_option('monaca_setting_email');
  $setting['password'] = get_option('monaca_setting_password');
  $setting['webdav'] = get_option('monaca_setting_webdav');

  // display setting menu
  include 'setting.php';
}

class monaca_uploader
{
  private $email;
  private $password;
  private $webdav;
  private $project_path;

  function __construct ($email, $password, $webdav, $project_path)
  {
    $this->email = $email;
    $this->password = $password;
    $this->webdav = rtrim($webdav, '/');
    $this->project_path = $project_path;
    $this->ch = curl_init();

    curl_setopt($this->ch, CURLOPT_USERPWD, $this->email . ":" . $this->password);
    curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($this->ch, CURLOPT_USERAGENT, "MonacaPress");

  }

  function upload()
  {
    $this->getPathAndUpload($this->project_path);
  }

  public function getPathAndUpload ($path)  
  {  
    $list = scandir($path); 
    
    foreach($list as $child_path){  
      if ('.' == $child_path || '..' == $child_path) {  
          continue;  
      }  

      $target = rtrim($path, '/').'/'.$child_path;  
      $url = $this->webdav.'/www/'.substr($target, strlen($this->project_path));

      if (is_dir($target)) {  
        $this->uploadDir($target, $url);
        $this->getPathAndUpload($target);  
      } elseif (is_file($target)) {  
        $this->uploadFile($target, $url);
      }  
    }  
  }

  public function uploadFile($target, $url)
  {
    $fp = fopen($target, 'r');
    $filesize = filesize($target);

    curl_setopt($this->ch, CURLOPT_PUT, TRUE);
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($this->ch, CURLOPT_INFILE, $fp);
    curl_setopt($this->ch, CURLOPT_INFILESIZE, $filesize);
    curl_setopt($this->ch, CURLOPT_URL, $url);
    curl_exec($this->ch);

    fclose($fp);
  }
  public function uploadDir($target, $url)
  {
    $fp = fopen($target, 'r');

    curl_setopt($this->ch, CURLOPT_INFILESIZE, 0);
    curl_setopt($this->ch, CURLOPT_INFILE, $fp);
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "MKCOL");
    curl_setopt($this->ch, CURLOPT_URL, $url);
    curl_exec($this->ch);
  }
}
