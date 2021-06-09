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
//include 'includes/menu_v.inc';

// Affichage du code
$template->pparse('Header');
$template->pparse('Corpus');
$template->pparse('Footer');
?>