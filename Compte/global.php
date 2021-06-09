<?php
/**************************
#
**************************/

session_start();
header('Content-type: text/html; charset=UTF-8');

IF ( !file_exists( 'includes/constantes.php' ) )
{
  header('Location: install.php');
}
ELSE IF ( !isset( $_SESSION['password'] ) )
{
  header('Location: connect.php');
} ELSE IF ( $_SESSION['password'] != 'connect' )
{
  header('Location: connect.php');
} ELSE {
  IF ( file_exists( 'install.php' ) ) { rename('install.php','install.bak'); }
  require 'includes/constantes.php';
  require 'includes/template.php';
  require 'includes/fonctions.php';

  // connection a la basse de donnee
  mysql_connect($BD_serv , $BD_login, $BD_pass);
  mysql_select_db($BD_name);
  mysql_query("SET NAMES 'utf8'");

  // Définition du dossier de theme
  $template = new Template('template/');

  // Definiions des fichiers principaux pour l'affichage du site
  require 'includes/constantes_defin.php';
  $template->set_filenames(array(
    'Header' => 'header.htm',
    'Footer' => 'footer.htm'));

  IF ( !isset( $_GET["hid"] ) ) {$hid = 1;} ELSE {$hid = $_GET["hid"];}
  IF ( !isset( $_GET["vid"] ) ) {$vid = 1;} ELSE {$vid = $_GET["vid"];}

  // Definition des valeurs globals
  $template->assign_vars(array(
    'TITRE' => $config['titre'],
    'HID' => $hid,
    'VID' => $vid
  ));
}
?>