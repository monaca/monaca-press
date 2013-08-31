<?php
/*
Plugin Name: MonacaPress
Plugin URI: http://press.monaca.mobi/
Description: MonacaPress is Plugin for Wordpress. This Plugin can Upload some source code to monaca.mobi.
Version: 0.1
Author: YUKI OKAMOTO (HN:Justice)
Author URI: http://monaca.mobi/
License: GPL2
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
    'info' => '投稿アプリ',
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

function monaca_press_setting() 
{
  // フォーム処理
  if ($_POST['submit']) {
    // 設定保存処理
    if ($_POST['save'] === '1') {
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
    // プロジェクト転送処理
    $projects = get_option('monaca_press_projects');
    $project_path = $projects[$_POST['project']]['path'];
    $project_path = dirname($project_path)."/project/";

    $monaca_uploader = new monaca_uploader($_POST['email'], $_POST['password'], $_POST['webdav'], $project_path);
    $monaca_uploader->upload();
  }

  // 設定の読み込み
  $setting = array('save' => '', 'email' => '', 'password' => '', 'webdav' => '');
  $setting['save'] = get_option('monaca_setting_save');
  $setting['email'] = get_option('monaca_setting_email');
  $setting['password'] = get_option('monaca_setting_password');
  $setting['webdav'] = get_option('monaca_setting_webdav');

  // 表示処理
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
    $this->webdav = $webdav;
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
      $url = $this->webdav.'www/'.substr($target, strlen($this->project_path));

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
    curl_setopt($this->ch, CURLOPT_INFILESIZE, 0);
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "MKCOL");
    curl_setopt($this->ch, CURLOPT_URL, $url);
    curl_exec($this->ch);
  }
}
