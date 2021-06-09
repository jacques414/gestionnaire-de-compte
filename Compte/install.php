<?php
/*************************************************************
#
#                   Gestion de collection
#
#
#
#
*************************************************************/

require 'includes/template.php';
require 'includes/fonctions.php';

// Définition du dossier de theme
$template = new Template('template/');

// Definiions des fichiers principaux pour l'affichage du site
$template->set_filenames(array(
  'Header' => 'header.htm',
  'Footer' => 'footer.htm'));

// Définition des HID et VID
IF ( !isset( $_GET["hid"] ) ) {$hid = 1;} ELSE {$hid = $_GET["hid"];}
IF ( !isset( $_GET["vid"] ) ) {$vid = 1;} ELSE {$vid = $_GET["vid"];}

// Menu Horizontale
$template->assign_block_vars('Menu_H', array(
  'LINK' => 'index.php',
  'NAME' => 'Index'
));
$template->assign_block_vars('Menu_H', array(
  'LINK' => 'install.php?hid=2',
  'NAME' => 'Installation'
));

// Menu Verticale
IF ( $hid == 2 )
{
  $template->assign_block_vars('Menu_V', array(
    'LINK' => 'install.php?hid=2&vid=2',
    'NAME' => 'Pr&eacute;paratif'
  ));
  $template->assign_block_vars('Menu_V', array(
    'LINK' => 'install.php?hid=2&vid=3',
    'NAME' => 'Configuration'
  ));
  $template->assign_block_vars('Menu_V', array(
    'LINK' => 'install.php?hid=2&vid=4',
    'NAME' => 'Fin'
  ));
}

// Definition des valeurs globals
$template->assign_vars(array(
  'TITRE' => "Collection | Installation"
));

IF ( $hid == 1 )
{
  $template->set_filenames(array(
    'Corpus' => 'install/corpus.htm'));
}
ELSE IF ( $hid == 2 )
{
  IF ( $vid == 1 )
  {
    $template->set_filenames(array(
      'Corpus' => 'install/avant.htm'));
  }
  ELSE IF ( $vid == 2 )
  {
    $template->set_filenames(array(
      'Corpus' => 'install/prep.htm'));
    $template->assign_vars(array(
      'HID' => $hid,
      'VID' => $vid
    ));
  }
  ELSE IF ( $vid == 3 )
  {
    $template->set_filenames(array(
      'Corpus' => 'install/password.htm'));
      
    $config = fopen('includes/constantes.php', 'a+');

    fputs($config, '<?php
/**************************
#
**************************/

// Valeur de la base de données MySQL
$BD_serv = \''.$_GET['server'].'\';
$BD_name = \''.$_GET['BD'].'\';
$BD_login  = \''.$_GET['login'].'\';
$BD_pass = \''.$_GET['password'].'\';
?>');

    fclose($config);
    
    $template->assign_vars(array(
      'HID' => $hid,
      'VID' => $vid
    ));
    
    // connection a la basse de donnee
    mysql_connect($_GET['server'], $_GET['login'], $_GET['password']);
    mysql_select_db($_GET['BD']);
    
    // Création de la base de donnée
    mysql_query("CREATE TABLE IF NOT EXISTS `comptes_classifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `class` varchar(255) NOT NULL
);");
    mysql_query("CREATE TABLE IF NOT EXISTS `comptes_comptes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `compte` varchar(255) NOT NULL
);");
    mysql_query("CREATE TABLE IF NOT EXISTS `comptes_flux` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `date` date NOT NULL,
  `compte` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `class` int(11) NOT NULL,
  `mode` int(11) NOT NULL,
  `debit` Decimal(12,2) NOT NULL,
  `solde` Decimal(12,2) NOT NULL,
  `total` Decimal(12,2) NOT NULL
);");
    mysql_query("CREATE TABLE IF NOT EXISTS `comptes_modes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `mode` varchar(255) NOT NULL
);");
    mysql_query("CREATE TABLE IF NOT EXISTS `config_menu_h` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL
);");
    mysql_query("CREATE TABLE IF NOT EXISTS `config_menu_v` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `parent` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL
);");
    mysql_query("CREATE TABLE  `comptes_planifier` (
  `id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
  `plani` int(3) NOT NULL,
  `etat` int(1) NOT NULL default '0',
  `date` varchar(10) collate latin1_general_cs NOT NULL,
  `compte` tinyint(4) NOT NULL,
  `description` varchar(255) collate latin1_general_cs NOT NULL,
  `class` tinyint(4) NOT NULL,
  `mode` tinyint(4) NOT NULL,
  `debit` decimal(12,2) NOT NULL,
  `solde` decimal(12,2) NOT NULL,
);");
    mysql_query("CREATE TABLE IF NOT EXISTS `config` (
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
);");
    mysql_query("INSERT INTO `config_menu_h` (`id`, `name`, `link`) VALUES
(1, 'Accueil', 'index.php'),
(2, 'Comptes', 'comptes.php'),
(3, '', ''),
(4, '', ''),
(5, '', ''),
(6, '', ''),
(7, '', ''),
(8, '', ''),
(9, '', ''),
(10, 'Configuration', 'config.php');");
    mysql_query("INSERT INTO `config_menu_v` (`id`, `parent`, `name`, `link`) VALUES
(1, 2, 'Flux', 'comptes.php'),
(2, 2, 'Comptes', 'comptes.php'),
(3, 2, 'Classification', 'comptes.php'),
(4, 2, 'Mode', 'comptes.php'),
(5, 10, 'Comptes', 'config.php'),
(6, 2, 'Export', 'comptes.php');");
  }
  ELSE IF ( $vid == 4 )
  {
    include 'includes/constantes.php';
    
    // connection a la basse de donnee
    mysql_connect($BD_serv , $BD_login, $BD_pass);
    mysql_select_db($BD_name);
    
    // Definiions des fichiers principaux pour l'affichage du site
    require 'includes/constantes_defin.php';
    
    mysql_query("INSERT INTO `config` (`name`, `value`) VALUES
('titre', '".$_GET['title']."'),
('password', '".md5($_GET['password'])."'),
('line_flux', '".$_GET['line']."'),
('date', '".date('Y-m-d')."');");

    $template->set_filenames(array(
      'Corpus' => 'install/fin.htm'));
  }
}

// Affichage du code
$template->pparse('Header');
$template->pparse('Corpus');
$template->pparse('Footer');