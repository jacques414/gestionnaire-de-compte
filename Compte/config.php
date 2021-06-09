<?php
/*************************************************************
#
#                   Gestion de collection
#
#
#
#
*************************************************************/

require 'includes/global.php';

$template->set_filenames(array(
  'Corpus' => 'corpus.htm'));

// Définition du menu Vertical
include 'includes/menu_v.inc';

IF ( $vid == 5 )
{
  IF ( !isset( $_GET['action'] ) )
  {
    $template->set_filenames(array(
      'Corpus' => 'config_comptes.htm'));
      
    // Definition des valeurs globals
    $template->assign_vars(array(
      'LINE_FLUX' => $config['line_flux']
    ));
  }
  ELSE IF ( $_GET['action'] == 'upd' )
  {
    $template->set_filenames(array(
      'Corpus' => 'resultat.htm'));
    
    $sql = mysql_query("UPDATE config SET value = \"".$_POST['title']."\" WHERE name = 'titre';");
    $sql = mysql_query("UPDATE config SET value = ".$_POST['line']." WHERE name = 'line_flux'");
    
    IF ( $sql = 1 ) { $result = 'Mise &agrave; jour faite sans probleme.'; } ELSE { $result = 'Echec de la mise &agrave; jour.'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  ELSE IF ( $_GET['action'] == 'upd_password' )
  {
    $template->set_filenames(array(
      'Corpus' => 'resultat.htm'));
    
    $sql = mysql_query("UPDATE config SET value = \"".md5( $_POST['password'] )."\" WHERE name = 'password';");
    
    IF ( $sql = 1 ) { $result = 'Mot de passe Modifier.'; } ELSE { $result = 'Echec de la modification.'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
}

// Affichage du code
$template->pparse('Header');
$template->pparse('Corpus');
$template->pparse('Footer');
?>