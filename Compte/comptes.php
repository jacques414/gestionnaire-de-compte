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

// Définition du menu Vertical
include 'includes/menu_v.inc';

// Affichage de la page Flux
IF ( $vid == 1 )
{
  // Page des Actions
  IF ( !isset( $_GET['action'] ) )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_flux.htm'));
    
    // Création du formulaire
    $TRI_COMPTES = '<option value="-1">Vide</option>';
    $TRI_MODES = '<option value="-1">Vide</option>';
    $TRI_CLASS = '<option value="-1">Vide</option>';
    
    // Création du formulaire
    $sql_compte = mysql_query("
      SELECT *
      FROM comptes_comptes
      ORDER BY compte ASC");
    WHILE ( $compte = mysql_fetch_array( $sql_compte ) )
    {$COMPTES .= "<option value=\"".$compte['id']."\">".$compte['compte']."</option>";}
    
    $sql_mode = mysql_query("
      SELECT *
      FROM comptes_modes
      ORDER BY mode ASC");
    WHILE ( $mode = mysql_fetch_array( $sql_mode ) )
    {$MODES .= "<option value=\"".$mode['id']."\">".$mode['mode']."</option>";}
    
    $sql_class = mysql_query("
      SELECT *
      FROM comptes_classifications
      ORDER BY class ASC");
    WHILE ( $class = mysql_fetch_array( $sql_class ) )
    {$CLASS .= "<option value=\"".$class['id']."\">".$class['class']."</option>";}
    
    IF ( $TRI_COMPTES == '<option value="-1">Vide</option>' )
    { $TRI_CLASS = $TRI_CLASS.'\n'.$CLASS; $TRI_COMPTES = $TRI_COMPTES.'\n'.$COMPTES; $TRI_MODES = $TRI_MODES.'\n'.$MODES; }
    $template->assign_vars( array(
      'DATE' => UsToFr(Date('Y-m-d')),
      'COMPTES' => $COMPTES,
      'MODES' => $MODES,
      'CLASSIFICATIONS' => $CLASS,
      'tri_COMPTES' => $TRI_COMPTES,
      'tri_MODES' => $TRI_MODES,
      'tri_CLASSIFICATIONS' => $TRI_CLASS,
      'tri_DATE' => $TRI_DATE
    ));
  }
  // Ajout d'une nouvelle transaction
  ELSE IF ( $_GET['action'] == 'new' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    // Selection de la date max
    $sql = mysql_fetch_array( mysql_query("
      SELECT MAX(date) AS Date_Max
      FROM comptes_flux;") );
    $sql_max = $sql['Date_Max'];
    
    // Enregistrement nouveau
    $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <=\"".FrToUs($_POST['date'])."\" AND compte = ".$_POST['compte'].";" ) );
    $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] + $_POST['solde'] - $_POST['debit'] , 2) ;
    $sql_add = mysql_query("INSERT INTO comptes_flux VALUES ( '' , '".FrToUs($_POST['date'])."', ".$_POST['compte'].", '". $_POST['desc'] ."', ".$_POST['class'].", ".$_POST['mode'].", ". str_replace(',','.',$_POST['debit']) .", ". str_replace(',','.',$_POST['solde']) .", ".$somme." )");
    
    IF ( $_POST['date'] < $sql_max )
    // Boucle pour mettre à jour tout les enregistrements suivants
    {
      $sql_date = mysql_query("
        SELECT Date, count(id) AS NbrId
        FROM comptes_flux
        WHERE Date >\"".FrToUs($_POST['date'])."\" AND compte = ".$_POST['compte']."
        GROUP BY Date
        ORDER BY Date ASC");
      
      WHILE ( $Date = mysql_fetch_array( $sql_date ) )
      {
        IF ( $Date['NbrId'] == 1 )
        {
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <=\"".FrToUs($Date['Date'])."\" AND compte = ".$_POST['compte'].";" ) );
          $sql_id = mysql_fetch_array( mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$_POST['compte'].";" ) );
          $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$sql_id['id'] );
        }
        ELSE
        {
          $sql_id = mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\"; AND compte = ".$_POST['compte']."" );
          WHILE ( $IDs = mysql_fetch_array( $sql_id ) )
          {
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, Sum(debit) AS debits
FROM (SELECT id, Date, solde, debit FROM comptes_flux AS Q1 WHERE Date <= \"".FrToUs($Date['Date'])."\" AND compte = ".$_POST['compte'].")  AS Q2 WHERE id <= ".$IDs['id'] ) );
            $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] , 2 );
            $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$IDs['id'] );
          }
        }
      }
    }
    
    IF ( $sql_add == 1 ) { $result = 'Ajout r&eacute;alis&eacute; avec succ&eacute;s<br />'; } ELSE { $result = 'Echec de l\'ajout<br />'; }
    IF ( $sql_upd == 1 ) { $result .= 'Mise &agrave; jour r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result .= 'Echec de la mise &agrave; jour'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Recalcul des comptes
  ELSE IF ( $_GET['action'] == 'upd_all' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm',
    'Header' => 'iheader.htm',
    'Footer' => 'ifooter.htm'));
    
    $sql_compte = mysql_query("SELECT * FROM comptes_comptes");
    WHILE ( $compte = mysql_fetch_array( $sql_compte ) )
    {
      $sql_date = mysql_query("
        SELECT Date, count(id) AS NbrId
        FROM comptes_flux
        WHERE compte = ".$compte['id']."
        GROUP BY Date
        ORDER BY Date ASC");
      
      WHILE ( $Date = mysql_fetch_array( $sql_date ) )
      {
        IF ( $Date['NbrId'] == 1 )
        {
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <=\"".FrToUs($Date['Date'])."\" AND compte = ".$compte['id'].";" ) );
          $sql_id = mysql_fetch_array( mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$compte['id'].";" ) );
          $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$sql_id['id'] );
        }
        ELSE
        {
          $sql_id = mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$compte['id'].";" );
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <\"".FrToUs($Date['Date'])."\" AND compte = ".$compte['id'].";" ) );
          $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          WHILE ( $IDs = mysql_fetch_array( $sql_id ) )
          {
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT solde, debit FROM comptes_flux WHERE id = ".$IDs['id'].";" ) );
            $somme = round($somme + $sql_somme['solde'] - $sql_somme['debit'] , 2 );
            $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$IDs['id'] );
          }
        }
      }
    }
    
    IF ( $sql_upd == 1 ) { $result = '<script language="Javascript">window.setTimeout("location=(\'comptes.php?action=flux&hid='.$hid.'&vid='.$vid.'\');",1000)</script><a href="comptes.php?hid='.$hid.'&vid='.$vid.'" target="_top">Recalcul &eacute;ffectu&eacute;</a>'; } ELSE { $result = 'Echec de la Mise &agrave; jour'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Affichage dans l'iFrame
  ELSE IF ( $_GET['action'] == 'flux' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_iflux.htm',
    'Header' => 'iheader.htm',
    'Footer' => 'ifooter.htm'));
    
    $page = $_GET['page'];
    IF ( !isset( $_GET['page'] ) ) { $page = 1; }
    
    // Selection SQL du compte
    IF ( $_GET['tri'] == 'on' )
    {
      $tri = "WHERE ";
      // Filtrage par compte
      IF ( $_GET['tri_compte'] != 0 )
      {
        IF ( $_GET['tri_compte'] == -1 )
        { $tri .= "compte = '0'"; }
        ELSE
        { $tri .= "compte = '".$_GET['tri_compte']."'"; }
      }
      // Filtrage par classification
      IF ( $_GET['tri_class'] != 0 )
      {
        IF ( $_GET['tri_compte'] != 0 )
        { $tri .= " AND "; }
        
        IF ( $_GET['tri_class'] == -1 )
        { $tri .= "class = '0'"; }
        ELSE
        { $tri .= "class = '".$_GET['tri_class']."'"; }
      }
      // Filtrage par mode de payement
      IF ( $_GET['tri_mode'] != 0 )
      {
        IF ( $_GET['tri_compte'] != 0 OR $_GET['tri_class'] != 0 )
        { $tri .= " AND "; }
        
        IF ( $_GET['tri_mode'] == -1 )
        { $tri .= "mode = '0'"; }
        ELSE
        { $tri .= "mode = '".$_GET['tri_mode']."'"; }
      }
      // Filtrage par Date
      IF ( $_GET['tri_date'] != "aaaa-mm-jj" )
      {
        IF ( $_GET['tri_compte'] != 0 OR $_GET['tri_class'] != 0 OR $_GET['tri_mode'] != 0 )
        { $tri .= " AND "; }
        
        $TRI_DATE = $_GET['tri_date'];
        $tri .= "date >= '".FrToUs($_GET['tri_date'])."'"; }
      // Si pas de filtrage
      IF ( $_GET['tri_compte'] == 0 AND $_GET['tri_class'] == 0 AND $_GET['tri_mode'] == 0 AND $_GET['tri_date'] == "aaaa-mm-jj" ) { $tri = ''; }
      
      $sql = mysql_query("
        SELECT *
        FROM comptes_flux
        ".$tri."
        ORDER BY Date DESC, id DESC
        LIMIT ". ($page-1)*$config['line_flux'] .", ".$config['line_flux']);
    }
    ELSE
    {
      $sql = mysql_query("
        SELECT *
        FROM comptes_flux
        ORDER BY Date DESC, id DESC
        LIMIT ". ($page-1)*$config['line_flux'] .", ".$config['line_flux']);
    }
    
    // Page Précédente
    $page_p = '&lt'; IF ( $page != 1 ) { $page_tps = $_GET['page'] - 1; $page_p = '<a href="comptes.php?action=flux&hid=2&vid=1&page='.$page_tps.'&tri=on&tri_date='.$_GET['tri_date'].'&tri_class='.$_GET['tri_class'].'&tri_compte='.$_GET['tri_compte'].'&tri_mode='.$_GET['tri_mode'].'" style="text-decoration: none;" target="iFrame">&lt;</a>'; }
    // Page suivante
    $sql_page = mysql_fetch_array( mysql_query( "SELECT count(id) AS page FROM comptes_flux ".$tri ) ); $page_max = ceil( $sql_page['page'] / $config['line_flux'] );
    $page_s = '&gt'; IF ( $page != $page_max ) { $page_tps = $page + 1 ; $page_s = '<a href="comptes.php?action=flux&hid=2&vid=1&page='.$page_tps.'&tri=on&tri_date='.$_GET['tri_date'].'&tri_class='.$_GET['tri_class'].'&tri_compte='.$_GET['tri_compte'].'&tri_mode='.$_GET['tri_mode'].'" style="text-decoration: none;" target="iFrame">&gt;</a>'; }
    $page = $page_p.' '.$page_s;
  
    // Afficahge des lignes de compte
    WHILE ( $row = mysql_fetch_array( $sql ) )
    {
      // Color les lignes
      $i++;
      IF ( $i/2 == floor($i/2) ) { $color = "#CCCCCC"; } ELSE { $color = "#666666"; } // floor pour arrondi inf et ceil pour arrondi sup
      
      $mode = mysql_fetch_array( mysql_query("
      SELECT *
      FROM comptes_modes
      WHERE id = ".$row['mode']) );
      
      $compte = mysql_fetch_array( mysql_query("
      SELECT *
      FROM comptes_comptes
      WHERE id = ".$row['compte']) );
      
      $class = mysql_fetch_array( mysql_query("
      SELECT *
      FROM comptes_classifications
      WHERE id = ".$row['class']) );
      
      $template->assign_block_vars('flux', array(
        'id' => $row['id'],
        'date' => UsToFr($row['date']),
        'compte' => $compte['compte'],
        'desc' => $row['description'],
        'class' => $class['class'],
        'mode' => $mode['mode'],
        'debit' => $row['debit'],
        'solde' => $row['solde'],
        'total' => $row['total'],
        'color' => $color
      ));
      $template->assign_vars( array(
        'PAGE' => $page));
    }
  }
  // Si c'est une mise à jour ou un suppressions
  ELSE IF ( isset( $_GET['id'] ) )
  {
    // Suppresion
    IF ( $_GET['action'] == 'del' )
    {
      $template->set_filenames(array(
      'Corpus' => 'comptes_resultat.htm',
      'Header' => 'iheader.htm',
      'Footer' => 'ifooter.htm'));
      
      $Flux = mysql_fetch_array( mysql_query ( "SELECT * FROM comptes_flux WHERE id = ".$_GET['id'] ) );
      $sql = mysql_query("
        DELETE FROM comptes_flux WHERE id = ".$_GET['id']);
      
      $sql_date = mysql_query("
        SELECT Date, count(id) AS NbrId
        FROM comptes_flux
        WHERE Date >\"".$Flux['date']."\"
        GROUP BY Date
        ORDER BY Date ASC");
      
      WHILE ( $Date = mysql_fetch_array( $sql_date ) )
      {
        IF ( $Date['NbrId'] == 1 )
        {
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <= \"".FrToUs($Date['Date'])."\" AND compte = ".$Flux['compte'].";" ) );
          $sql_id = mysql_fetch_array( mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\";" ) );
          $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$sql_id['id'] );
        }
        ELSE
        {
          $sql_id = mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$Flux['compte'].";" );
          WHILE ( $IDs = mysql_fetch_array( $sql_id ) )
          {
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, Sum(debit) AS debits
  FROM (SELECT id, Date, solde, debit FROM comptes_flux AS Q1 WHERE Date <= \"".FrToUs($Date['Date'])."\" AND compte = ".$Flux['compte']." )  AS Q2 WHERE id <= ".$IDs['id'] ) );
            $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
            $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$IDs['id'] );
          }
        }
      }
      
      IF ( $sql == 1 ) { $result = '<script language="Javascript">window.setTimeout("location=(\'comptes.php?action=flux&hid='.$hid.'&vid='.$vid.'\');",1000)</script><a href="comptes.php?hid='.$hid.'&vid='.$vid.'" target="_top">Action supprimer avec succ&eacute;s</a>'; } ELSE { $result = 'Echec de la suppression'; }
      $template->assign_vars( array( 'RESULTAT' => $result ) );
    }
    // Mise à jour
    ELSE IF ( $_GET['action'] == 'upd' )
    {
      $template->set_filenames(array(
      'Corpus' => 'comptes_resultat.htm',
      'Header' => 'iheader.htm',
      'Footer' => 'ifooter.htm'));
      
      // Mise à jour
//      $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date < \"".$_POST['date']."\" AND compte = ".$_POST['compte'].";" ) );
//      $somme = round($sql_somme['soldes'] - $sql_somme['debits'] + $_POST['solde'] - $_POST['debit'] , 2 );
//      , `total` = '".$somme."'
      $sql_query = mysql_fetch_array( mysql_query("SELECT * FROM comptes_flux WHERE id = ".$_GET['id']) );
      $sql = mysql_query("UPDATE comptes_flux SET `date` = '".FrToUs($_POST['date'])."', `compte` = '".$_POST['compte']."', `description` = '". $_POST['Description'] ."', `class` = '".$_POST['class']."', `mode` = '".$_POST['mode']."', `debit` = '". str_replace(',','.',$_POST['debit']) ."', `solde` = '". str_replace(',','.',$_POST['solde']) ."' WHERE `id` = ".$_GET['id']." ");
      
      // Pas de changement de compte
      $sql_date = mysql_query("
        SELECT Date, count(id) AS NbrId
        FROM comptes_flux
        WHERE Date >=\"".FrToUs($_POST['date'])."\" AND compte = ".$_POST['compte']."
        GROUP BY Date
        ORDER BY Date ASC");
      
      WHILE ( $Date = mysql_fetch_array( $sql_date ) )
      {
        IF ( $Date['NbrId'] == 1 )
        {
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <=\"".FrToUs($Date['Date'])."\" AND compte = ".$_POST['compte'].";" ) );
          $sql_id = mysql_fetch_array( mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$_POST['compte'].";" ) );
          $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$sql_id['id'] );
        }
        ELSE
        {
          $sql_id = mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$_POST['compte'].";" );
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <\"".FrToUs($Date['Date'])."\" AND compte = ".$_POST['compte'].";" ) );
          $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          WHILE ( $IDs = mysql_fetch_array( $sql_id ) )
          {
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT solde, debit FROM comptes_flux WHERE id = ".$IDs['id'].";" ) );
            $somme = round($somme + $sql_somme['solde'] - $sql_somme['debit'] , 2 );
            $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$IDs['id'] );
          }
        }
      }
      // Changement de compte
      IF ( $_POST['compte'] != $sql_query['compte'] )
      {
        $sql_date = mysql_query("
          SELECT Date, count(id) AS NbrId
          FROM comptes_flux
          WHERE Date >=\"".FrToUs($_POST['date'])."\" AND compte = ".$sql_query['compte']."
          GROUP BY Date
          ORDER BY Date ASC");
        
        WHILE ( $Date = mysql_fetch_array( $sql_date ) )
        {
          IF ( $Date['NbrId'] == 1 )
          {
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <=\"".FrToUs($Date['Date'])."\" AND compte = ".$sql_query['compte'].";" ) );
            $sql_id = mysql_fetch_array( mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$sql_query['compte'].";" ) );
            $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
            $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$sql_id['id'] );
          }
          ELSE
          {
            $sql_id = mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".FrToUs($Date['Date'])."\" AND compte = ".$sql_query['compte'].";" );
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <\"".FrToUs($Date['Date'])."\" AND compte = ".$sql_query['compte'].";" ) );
            $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
            WHILE ( $IDs = mysql_fetch_array( $sql_id ) )
            {
              $sql_somme = mysql_fetch_array( mysql_query( "SELECT solde, debit FROM comptes_flux WHERE id = ".$IDs['id'].";" ) );
              $somme = round($somme + $sql_somme['solde'] - $sql_somme['debit'] , 2 );
              $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$IDs['id'] );
            }
          }
        }
      
      }
      
      IF ( $sql == 1 ) { $result = '<script language="Javascript">window.close();</script>'; } ELSE { $result = 'Echec de la Mise &agrave; jour'; }
      $template->assign_vars( array( 'RESULTAT' => $result ) );
    }
  }
}
// Affichage de la page Comptes
ELSE IF ( $vid == 2 )
{
  // Si aucune action n'est executer
  IF ( !isset( $_GET['action'] ) )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_comptes.htm'));
    
    $sql = mysql_query("
      SELECT *
      FROM comptes_comptes");
    
    WHILE ( $row = mysql_fetch_array( $sql ) )
    {
      $template->assign_block_vars('comptes', array(
        'ID' => $row['id'],
        'Name' => $row['compte']
      ));
    }
  }
  // Si un nouveu compte est ajouter
  ELSE IF ( $_GET['action'] == 'new' )  
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    $sql = mysql_query("
      INSERT INTO comptes_comptes VALUES ( '' , '". $_POST['name'] ."' )");
    
    IF ( $sql == 1 ) { $result = 'Ajout r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result = 'Echec de l\'ajout'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Mise à jour d'un compte
  ELSE IF ( $_GET['action'] == 'upd_' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    // Mise à jour
    $sql = mysql_query("UPDATE comptes_comptes SET `compte` = '". $_GET['name']."' WHERE `id` = ".$_GET['id']." ");
    
    IF ( $sql == 1 ) { $result = 'Mise &agrave; jour r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result = 'Echec de la Mise &agrave; jour'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Suppression d'un compte
  ELSE IF ( $_GET['action'] == 'del' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    $sql_upd = mysql_query("
      UPDATE comptes_flux SET compte = 0 WHERE compte = ".$_GET['id']);
    $sql_del = mysql_query("
      DELETE FROM comptes_comptes WHERE id = ".$_GET['id']);
    
    IF ( $sql_upd == 1 ) { $result = 'Mise &agrave; jour r&eacute;alis&eacute; avec succ&eacute;s<br />'; } ELSE { $result = 'Echec de la mise &agrave; jour<br />'; }
    IF ( $sql_del == 1 ) { $result .= 'Suppression r&eacute;alis&eacute; avec succ&eacute;s<br />'; } ELSE { $result .= 'Echec de le suppression<br />'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
}
// Affichage des Classifications
ELSE IF ( $vid == 3 )
{
  // Si aucune action n'est executer
  IF ( !isset( $_GET['action'] ) )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_classifications.htm'));
    
    $sql = mysql_query("
      SELECT *
      FROM comptes_classifications");
    
    WHILE ( $row = mysql_fetch_array( $sql ) )
    {
      $template->assign_block_vars('class', array(
        'ID' => $row['id'],
        'Name' => $row['class']
      ));
    }
  }
  // Si un nouvelle classification est ajouter
  ELSE IF ( $_GET['action'] == 'new' )  
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    $sql = mysql_query("
      INSERT INTO comptes_classifications VALUES ( '' , '". $_POST['name'] ."' )");
    
    IF ( $sql == 1 ) { $result = 'Ajout r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result = 'Echec de l\'ajout'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Mise à jour d'une classification
  ELSE IF ( $_GET['action'] == 'upd' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    // Mise à jour
    $sql = mysql_query("UPDATE comptes_classifications SET `class` = '". $_GET['name'] ."' WHERE `id` = ".$_GET['id']." ");
    
    IF ( $sql == 1 ) { $result = 'Mise &agrave; jour r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result = 'Echec de la Mise &agrave; jour'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Suppression d'un classification
  ELSE IF ( $_GET['action'] == 'del' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    $sql_upd = mysql_query("
      UPDATE comptes_flux SET class = 0 WHERE class = ".$_GET['id']);
    $sql_del = mysql_query("
      DELETE FROM comptes_classifications WHERE id = ".$_GET['id']);
    
    IF ( $sql_upd == 1 ) { $result = 'Mise &agrave; jour r&eacute;alis&eacute; avec succ&eacute;s<br />'; } ELSE { $result = 'Echec de la mise &agrave; jour<br />'; }
    IF ( $sql_del == 1 ) { $result .= 'Suppression r&eacute;alis&eacute; avec succ&eacute;s<br />'; } ELSE { $result .= 'Echec de le suppression<br />'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
}
// Affichage de la page des modes de payement
ELSE IF ( $vid == 4 )
{
  // Si aucune action n'est executer
  IF ( !isset( $_GET['action'] ) )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_modes.htm'));
    
    $sql = mysql_query("
      SELECT *
      FROM comptes_modes");
    
    WHILE ( $row = mysql_fetch_array( $sql ) )
    {
      $template->assign_block_vars('modes', array(
        'ID' => $row['id'],
        'Name' => $row['mode']
      ));
    }
  }
  // Si un nouveau mode de payement est ajouter
  ELSE IF ( $_GET['action'] == 'new' )  
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    $sql = mysql_query("
      INSERT INTO comptes_modes VALUES ( '' , '". $_POST['name'] ."' )");
    
    IF ( $sql == 1 ) { $result = 'Ajout r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result = 'Echec de l\'ajout'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Mise à jour d'un mode de payement
  ELSE IF ( $_GET['action'] == 'upd' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    // Mise à jour
    $sql = mysql_query("UPDATE comptes_modes SET `mode` = '". $_GET['name'] ."' WHERE `id` = ".$_GET['id']." ");
    
    IF ( $sql == 1 ) { $result = 'Mise &agrave; jour r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result = 'Echec de la Mise &agrave; jour'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Suppression d'un mode de payement
  ELSE IF ( $_GET['action'] == 'del' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    $sql_upd = mysql_query("
      UPDATE comptes_flux SET mode = 0 WHERE mode = ".$_GET['id']);
    $sql_del = mysql_query("
      DELETE FROM comptes_modes WHERE id = ".$_GET['id']);
    
    IF ( $sql_upd == 1 ) { $result = 'Mise &agrave; jour r&eacute;alis&eacute; avec succ&eacute;s<br />'; } ELSE { $result = 'Echec de la mise &agrave; jour<br />'; }
    IF ( $sql_del == 1 ) { $result .= 'Suppression r&eacute;alis&eacute; avec succ&eacute;s<br />'; } ELSE { $result .= 'Echec de le suppression<br />'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
}
// Affichage des actions récurentes
ELSE IF ( $vid == 6 )
{
  // Si aucune action n'est executer
  IF ( !isset( $_GET['action'] ) )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_plan.htm'));
    
    // Création du formulaire
    $sql_compte = mysql_query("
      SELECT *
      FROM comptes_comptes
      ORDER BY compte ASC");
    WHILE ( $compte = mysql_fetch_array( $sql_compte ) )
    {$COMPTES .= "<option value=\"".$compte['id']."\">".$compte['compte']."</option>";}
    
    $sql_mode = mysql_query("
      SELECT *
      FROM comptes_modes
      ORDER BY mode ASC");
    WHILE ( $mode = mysql_fetch_array( $sql_mode ) )
    {$MODES .= "<option value=\"".$mode['id']."\">".$mode['mode']."</option>";}
    
    $sql_class = mysql_query("
      SELECT *
      FROM comptes_classifications
      ORDER BY class ASC");
    WHILE ( $class = mysql_fetch_array( $sql_class ) )
    {$CLASS .= "<option value=\"".$class['id']."\">".$class['class']."</option>";}
    
    $template->assign_vars( array(
      'DATE' => UsToFr(Date('Y-m-d')),
      'COMPTES' => $COMPTES,
      'MODES' => $MODES,
      'CLASSIFICATIONS' => $CLASS
    ));
  }
  // Affichage dans l'iFrame
  ELSE IF ( $_GET['action'] == 'flux' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_iplan.htm',
    'Header' => 'iheader.htm',
    'Footer' => 'ifooter.htm'));
    
    $page = $_GET['page'];
    IF ( !isset( $_GET['page'] ) ) { $page = 1; }

    // Selection SQL du compte
    IF ( $_GET['tri'] == 'on' )
    {
      $tri = "WHERE ";
      // Filtrage par compte
      IF ( $_GET['tri_compte'] != 0 )
      {
        IF ( $_GET['tri_compte'] == -1 )
        { $tri .= "compte = '0'"; }
        ELSE
        { $tri .= "compte = '".$_GET['tri_compte']."'"; }
      }
      // Filtrage par classification
      IF ( $_GET['tri_class'] != 0 )
      {
        IF ( $_GET['tri_compte'] != 0 )
        { $tri .= " AND "; }
        
        IF ( $_GET['tri_class'] == -1 )
        { $tri .= "class = '0'"; }
        ELSE
        { $tri .= "class = '".$_GET['tri_class']."'"; }
      }
      // Filtrage par mode de payement
      IF ( $_GET['tri_mode'] != 0 )
      {
        IF ( $_GET['tri_compte'] != 0 OR $_GET['tri_class'] != 0 )
        { $tri .= " AND "; }
        
        IF ( $_GET['tri_mode'] == -1 )
        { $tri .= "mode = '0'"; }
        ELSE
        { $tri .= "mode = '".$_GET['tri_mode']."'"; }
      }
      // Filtrage par Date
      IF ( $_GET['tri_date'] != "aaaa-mm-jj" )
      {
        IF ( $_GET['tri_compte'] != 0 OR $_GET['tri_class'] != 0 OR $_GET['tri_mode'] != 0 )
        { $tri .= " AND "; }
        
        $TRI_DATE = $_GET['tri_date'];
        $tri .= "date >= '".$_GET['tri_date']."'"; }
      // Si pas de filtrage
      IF ( $_GET['tri_compte'] == 0 AND $_GET['tri_class'] == 0 AND $_GET['tri_mode'] == 0 AND $_GET['tri_date'] == "aaaa-mm-jj" ) { $tri = ''; }
      
      $sql = mysql_query("
        SELECT *
        FROM comptes_planifier
        ".$tri."
        ORDER BY plani ASC, Date ASC, id ASC
        LIMIT ". ($page-1)*$config['line_flux'] .", ".$config['line_flux']);
    }
    ELSE
    {
      $sql = mysql_query("
        SELECT *
        FROM comptes_planifier
        ORDER BY plani ASC, Date ASC, id ASC
        LIMIT ". ($page-1)*$config['line_flux'] .", ".$config['line_flux']);
    }
    
    // Page Précédente
    $page_p = '&lt'; IF ( $page != 1 ) { $page_tps = $_GET['page'] - 1; $page_p = '<a href="comptes.php?action=flux&hid=2&vid=1&page='.$page_tps.'&tri=on&tri_date='.$_GET['tri_date'].'&tri_class='.$_GET['tri_class'].'&tri_compte='.$_GET['tri_compte'].'&tri_mode='.$_GET['tri_mode'].'" style="text-decoration: none;" target="iFrame">&lt;</a>'; }
    // Page suivante
    $sql_page = mysql_fetch_array( mysql_query( "SELECT count(id) AS page FROM comptes_flux ".$tri ) ); $page_max = ceil( $sql_page['page'] / $config['line_flux'] );
    $page_s = '&gt'; IF ( $page != $page_max ) { $page_tps = $page + 1 ; $page_s = '<a href="comptes.php?action=flux&hid=2&vid=1&page='.$page_tps.'&tri=on&tri_date='.$_GET['tri_date'].'&tri_class='.$_GET['tri_class'].'&tri_compte='.$_GET['tri_compte'].'&tri_mode='.$_GET['tri_mode'].'" style="text-decoration: none;" target="iFrame">&gt;</a>'; }
    $page = $page_p.' '.$page_s;

    // Afficahge des lignes de compte
    WHILE ( $row = mysql_fetch_array( $sql ) )
    {
      // Color les lignes
      $i++;
      IF ( $i/2 == floor($i/2) ) { $color = "#CCCCCC"; } ELSE { $color = "#666666"; } // floor pour arrondi inf et ceil pour arrondi sup
      
      $mode = mysql_fetch_array( mysql_query("
      SELECT *
      FROM comptes_modes
      WHERE id = ".$row['mode']) );
      
      $compte = mysql_fetch_array( mysql_query("
      SELECT *
      FROM comptes_comptes
      WHERE id = ".$row['compte']) );
      
      $class = mysql_fetch_array( mysql_query("
      SELECT *
      FROM comptes_classifications
      WHERE id = ".$row['class']) );
    
      // Type de planification
      switch ( $row['plani'] )
      {
        case 1:
          $plani = 'Unique';
        break;
        case 7:
          $plani = 'Hebdomadaire';
        break;
        case 31:
          $plani = 'Mensuel';
        break;
        case 365:
          $plani = 'Annuel';
        break;
      }
      
      IF ( $row['plani'] == 7 )
      {
        $date = NumToDay( $row['date'] );
      }
      ELSE IF ( $row['plani'] == 31 )
      {
        IF ( $row['date'] == '32' ) { $date = 'Fin'; } ELSE IF ( $row['date'] == '0' ) { $date = 'D&eacute;but'; } ELSE { $date = $row['date']; }
      }
      ELSE
      {
        $date = $row['date'];
      }
      
      $template->assign_block_vars('flux', array(
        'id' => $row['id'],
        'etat' => $row['etat'],
        'plani' => $plani,
        'date' => $date,
        'compte' => $compte['compte'],
        'desc' => $row['description'],
        'class' => $class['class'],
        'mode' => $mode['mode'],
        'debit' => $row['debit'],
        'solde' => $row['solde'],
        'total' => $row['total'],
        'color' => $color
      ));
      $template->assign_vars( array(
        'PAGE' => $page));
    }
  }
  // Ajout d'une planification
  ELSE IF ( $_GET['action'] == 'new' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    IF ( $_POST['plan'] == 7 )
    {
      $sql = mysql_query( "
        INSERT INTO comptes_planifier
        VALUES ( '', '".$_POST['plan']."', '0', '".$_POST['day']."', '".$_POST['compte']."', '". utf8_encode( $_POST['desc'] ) ."', '".$_POST['class']."', '".$_POST['mode']."', '". str_replace( ',', '.', $_POST['debit'] ) ."', '". str_replace( ',', '.', $_POST['solde'] ) ."' )" );
    }
    ELSE
    {
      $sql = mysql_query( "
        INSERT INTO comptes_planifier
        VALUES ( '', '".$_POST['plan']."', '0', '".FrToUs($_POST['date'])."', '".$_POST['compte']."', '". utf8_encode( $_POST['desc'] ) ."', '".$_POST['class']."', '".$_POST['mode']."', '". str_replace( ',', '.', $_POST['debit'] ) ."', '". str_replace( ',', '.', $_POST['solde'] ) ."' )" );
    }
    
    IF ( $sql == 1 ) { $result = 'Ajout r&eacute;alis&eacute; avec succ&eacute;s'; } ELSE { $result = 'Echec de l\'ajout'; }
    $template->assign_vars( array( 'RESULTAT' => $result ) );
  }
  // Si c'est une mise à jour ou un suppressions
  ELSE IF ( isset( $_GET['id'] ) )
  {
    // Suppresion
    IF ( $_GET['action'] == 'del' )
    {
      $template->set_filenames(array(
      'Corpus' => 'comptes_resultat.htm',
      'Header' => 'iheader.htm',
      'Footer' => 'ifooter.htm'));
      
      $sql = mysql_query( "DELETE FROM comptes_planifier WHERE id = ".$_GET['id'] );
      
      IF ( $sql == 1 ) { $result = '<script language="Javascript">window.setTimeout("location=(\'comptes.php?action=flux&hid='.$hid.'&vid='.$vid.'\');",1000)</script><a href="comptes.php?hid='.$hid.'&vid='.$vid.'" target="_top">Planification supprimer avec succ&eacute;s</a>'; } ELSE { $result = 'Echec de la suppression'; }
      $template->assign_vars( array( 'RESULTAT' => $result ) );
    }
    // Mise à jour
    ELSE IF ( $_GET['action'] == 'upd' )
    {
      $template->set_filenames(array(
      'Corpus' => 'comptes_resultat.htm',
      'Header' => 'iheader.htm',
      'Footer' => 'ifooter.htm'));
      
      IF ( isset( $_POST['etat'] ) ) { $etat = 1; } ELSE { $etat = 0; }
      IF ( $_POST['plani'] == 7 ) { $date = $_POST['day']; } ELSE { $date = $_POST['date']; }
      $sql = mysql_query("UPDATE comptes_planifier SET `plani` = ".$_POST['plani'].", `etat` = ".$etat.", `date` = '".FrToUs($date)."', `compte` = '".$_POST['compte']."', `description` = '". $_POST['Description'] ."', `class` = '".$_POST['class']."', `mode` = '".$_POST['mode']."', `debit` = '". str_replace(',','.',$_POST['debit']) ."', `solde` = '". str_replace(',','.',$_POST['solde']) ."' WHERE `id` = ".$_GET['id']." ");
      
      IF ( $sql == 1 ) { $result = '<script language="Javascript">window.close();</script>'; } ELSE { $result = 'Echec de la Mise &agrave; jour'; }
      $template->assign_vars( array( 'RESULTAT' => $result ) );
    }
  }
}
// Afficahge de la Page d'export/import CSV
ELSE IF ( $vid == 7 )
{
  // Affichage de la page par défaut
  IF ( !isset( $_GET['action'] ) )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_export.htm'));
    
    // Création du formulaire
    $sql_compte = mysql_query("
      SELECT *
      FROM comptes_comptes
      ORDER BY compte ASC");
    WHILE ( $compte = mysql_fetch_array( $sql_compte ) )
    {$COMPTES .= "<option value=\"".$compte['id']."\">".$compte['compte']."</option>";}
    
    $sql_mode = mysql_query("
      SELECT *
      FROM comptes_modes
      ORDER BY mode ASC");
    WHILE ( $mode = mysql_fetch_array( $sql_mode ) )
    {$MODES .= "<option value=\"".$mode['id']."\">".$mode['mode']."</option>";}
    
    $sql_class = mysql_query("
      SELECT *
      FROM comptes_classifications
      ORDER BY class ASC");
    WHILE ( $class = mysql_fetch_array( $sql_class ) )
    {$CLASS .= "<option value=\"".$class['id']."\">".$class['class']."</option>";}
    
    $template->assign_vars( array(
      'DATE' => UsToFr(Date('Y-m-d')),
      'COMPTES' => $COMPTES,
      'MODES' => $MODES,
      'CLASSIFICATIONS' => $CLASS
    ));
  }
  // Export des données
  ELSE IF ( $_GET['action'] == 'export' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    // Selection SQL du compte
    $tri = "WHERE ";
    // Filtrage par compte
    IF ( $_POST['compte'] != 0 )
    { $tri .= "compte = '".$_POST['compte']."'"; }
    // Filtrage par classification
    IF ( $_POST['class'] != 0 )
    {
      IF ( $_POST['compte'] != 0 )
      { $tri .= " AND "; }
      $tri .= "class = '".$_POST['class']."'";
    }
    // Filtrage par mode de payement
    IF ( $_POST['mode'] != 0 )
    {
      IF ( $_POST['compte'] != 0 OR $_POST['class'] != 0 )
      { $tri .= " AND "; }
      $tri .= "mode = '".$_POST['mode']."'";
    }
    // Filtrage par Date
    IF ( $_POST['date'] != date('Y-m-d') )
    {
      IF ( $_POST['compte'] != 0 OR $_POST['class'] != 0 OR $_POST['mode'] != 0 )
      { $tri .= " AND "; }
      
      $TRI_DATE = $_POST['date'];
      $tri .= "date <= '".FrToUs($_POST['date'])."'";
    }
    // Si pas de filtrage
    IF ( $_POST['compte'] == 0 AND $_POST['class'] == 0 AND $_POST['mode'] == 0 AND $_POST['date'] == date('Y-m-d') ) { $tri = ''; }
    
    $sql = mysql_query("
      SELECT *
      FROM comptes_flux
      ".$tri."
      ORDER BY Date ASC, id ASC");
      
    IF ( file_exists( "export/".FrToUs($_POST['date']).".csv" ) ) { unlink( "export/".FrToUs($_POST['date']).".csv" ); }
    $csv = fopen("export/".FrToUs($_POST['date']).".csv", 'a+');
    
    fputs($csv, 'Date;Compte;Description;Classification;Mode de payement;Debit;Solde;Total
'); $i = 2;
    
    $ligne = mysql_fetch_array( $sql );
    $compte = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_comptes WHERE id = ".$ligne['compte'] ) );
    $class = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_classifications WHERE id = ".$ligne['class'] ) );
    $mode = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_modes WHERE id = ".$ligne['mode'] ) );
    fputs ( $csv, $ligne['date'].';'. utf8_decode( $compte['compte'] ) .';'. utf8_decode( $ligne['description'] ) .';'. utf8_decode( $class['class'] ) .';'. utf8_decode( $mode['mode'] ) .';'. str_replace( '.', ',', $ligne['debit'] ) .';'. str_replace( '.', ',', $ligne['solde'] ) .';=G2-F2
' );
    WHILE ( $ligne = mysql_fetch_array( $sql ) )
    {
      $I = $i;$i++;
      $compte = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_comptes WHERE id = ".$ligne['compte'] ) );
      $class = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_classifications WHERE id = ".$ligne['class'] ) );
      $mode = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_modes WHERE id = ".$ligne['mode'] ) );
      fputs ( $csv, $ligne['date'].';'. utf8_decode( $compte['compte'] ) .';'. utf8_decode( $ligne['description'] ) .';'. utf8_decode( $class['class'] ) .';'. utf8_decode( $mode['mode'] ) .';'. str_replace( '.', ',', $ligne['debit'] ) .';'. str_replace( '.', ',', $ligne['solde'] ) .';=H'.$I.'-F'.$i.'+G'.$i.'
' );
    }
    
    fclose($csv);
    
    $template->assign_vars( array(
      'RESULTAT' => '<a href="export/'.FrToUs($_POST['date']).'.csv">Export du '.UsToFr($_POST['date']).'</a>'
    ));
  }
  // Liens pour l'import des données
  ELSE IF ( $_GET['action'] == 'import' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_import.htm'));
    
    //echo '<pre>'.print_r($_FILES).'</pre>';
    //IF ( $_FILES['file']['type'] == 'application/vnd.ms-excel' )
    //{
    //echo '<pre>'.print_r($_FILES).'</pre>';
    //}
    
    // Lecture du fichier
    IF ( $_FILES['file']['error'] )
    {
      switch ( $_FILES['file']['error'] )
      {
        case 1:
          $result = "Le fichier dépasse la limite autorisée";
        break;
        case 2:
          $result = "Le fichier dépasse la limite du formulaire";
        break;
        case 3:
          $result = "L'envoie du fichier à été interrompu pendant le fransfert";
        break;
        case 4:
          $result = "Le fichier que vous avez envoyé a une taille nulle";
        break;
      }
    }
    ELSE
    {
      $file = $_FILES['file']['name'];
      move_uploaded_file( $_FILES['file']['tmp_name'],'import/'.$file );
      $result = 'Copie du fichier OK';
      
      $csv = fopen('import/'.$file,'r');
      $ligne = fgets($csv);
      $ligne_bloc = explode(';', $ligne); $i = 0;
      WHILE ( isset( $ligne_bloc[$i] ) )
      {
        $liste = $liste.'<option value="'.$i.'">'. utf8_encode( $ligne_bloc[$i] ) .'</option>';
        $disp[$i] = $ligne_bloc[$i];
        $i++;
      }
      $ligne = fgets($csv);
      $ligne_bloc = explode(';', $ligne); $i = 0;
      WHILE ( isset( $ligne_bloc[$i] ) )
      {
        $DISP = $DISP.$disp[$i].' : '. utf8_encode( $ligne_bloc[$i] ) .' | ';
        $i++;
      }
    }
    
    $template->assign_vars( array(
      'LISTE' => $liste,
      'FILE' => $file,
      'DISP' => $DISP
    ));
  }
  // Import des données
  ELSE IF ( $_GET['action'] == 'import_sql' )
  {
    $template->set_filenames(array(
    'Corpus' => 'comptes_resultat.htm'));
    
    // Import du fichier CSV
    $csv = fopen('import/'.$_POST['file'],'r');
    $ligne = explode( ';', fgets($csv) );
    WHILE ( $tmp_ligne = fgets($csv) )
    {
      $ligne = explode( ';', $tmp_ligne );
      // Vérification de l'existance des comptes
      $result = mysql_query("SELECT compte FROM comptes_comptes WHERE compte = '". utf8_encode( $ligne[$_POST['Compte']] ) ."' LIMIT 0 , 1 ;");
      IF ( mysql_num_rows($result) == 1 )
      { $compte = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_comptes WHERE compte = '". utf8_encode( $ligne[$_POST['Compte']] ) ."';" ) ); $result_compte = 1; }
      ELSE
      { $sql_compte = mysql_query( "INSERT INTO comptes_comptes VALUES ( '', '". utf8_encode( $ligne[$_POST['Compte']] ) ."' )" );
        $compte = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_comptes WHERE compte = '". utf8_encode( $ligne[$_POST['Compte']] ) ."';" ) );
        IF ( $sql_compte == 1 ) { $result_compte = 1;} }
      
      // Vérification de l'existance des modes de payements
      $result = mysql_query("SELECT mode FROM comptes_modes WHERE mode = '". utf8_encode( $ligne[$_POST['Mode']] ) ."' LIMIT 0 , 1 ;");
      IF ( mysql_num_rows($result) == 1 )
      { $mode = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_modes WHERE mode = '". utf8_encode( $ligne[$_POST['Mode']] ) ."';" ) ); $result_mode = 1; }
      ELSE
      { $sql_mode = mysql_query( "INSERT INTO comptes_modes VALUES ( '', '". utf8_encode( $ligne[$_POST['Mode']] ) ."' )" );
        $mode = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_modes WHERE mode = '". utf8_encode( $ligne[$_POST['Mode']] ) ."';" ) );
        IF ( $sql_mode == 1 ) { $result_mode = 1;}  }
      
      // Vérification de l'existance des classifications
      $result = mysql_query("SELECT class FROM comptes_classifications WHERE class = '". utf8_encode( $ligne[$_POST['Class']] ) ."' LIMIT 0 , 1 ;");
      IF ( mysql_num_rows($result) == 1 )
      { $class = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_classifications WHERE class = '". utf8_encode( $ligne[$_POST['Class']] ) ."';" ) ); $result_class = 1; }
      ELSE
      { $sql_class = mysql_query( "INSERT INTO comptes_classifications VALUES ( '', '". utf8_encode( $ligne[$_POST['Class']] ) ."' )" );
        $class = mysql_fetch_array( mysql_query( "SELECT * FROM comptes_classifications WHERE class = '". utf8_encode( $ligne[$_POST['Class']] ) ."';" ) );
        IF ( $sql_class == 1 ) { $result_class = 1;}  }
      
      // Reformatage de la date
      switch ( $_POST['FDate'] )
      {
        case 1:
          //$time = mktime(h, m, s, m, d, a );
          $time = explode('-', $ligne[$_POST['Date']]);
          $time = mktime(0, 0, 0, $time[1], $time[2], $time[0] );
        break;
        case 2:
          $time = explode('/', $ligne[$_POST['Date']]);
          $time = mktime(0, 0, 0, $time[1], $time[0], $time[2] );
        break;
        case 3:
          $time = explode('.', $ligne[$_POST['Date']]);
          $time = mktime(0, 0, 0, $time[1], $time[0], $time[2] );
        break;
        case 4:
          $time = explode('/', $ligne[$_POST['Date']]);
          $time = mktime(0, 0, 0, $time[0], $time[1], $time[2] );
        break;
        case 5:
          $time = explode('/', $ligne[$_POST['Date']]);
          $time = mktime(0, 0, 0, $time[1], $time[2], $time[0] );
        break;
      }
      $date = date('Y-m-d', $time);
      
      $sql_add = mysql_query( "INSERT INTO comptes_flux VALUES ( '', '".$date."', '".$compte['id']."', '". utf8_encode( str_replace('\'','\\\'', $ligne[$_POST['Desc']] ) ) ."', '".$class['id']."', '".$mode['id']."', '". str_replace(',','.',$ligne[$_POST['Debit']]) ."', '". str_replace(',','.',$ligne[$_POST['Solde']]) ."', '' )" );
      IF ( $sql_add == 1 ) { $result_add = 1;}
    }
    
    fclose($csv);
    
    // Mise à jour des totaux
    $sql_compte = mysql_query( "SELECT id FROM comptes_comptes" );
    WHILE ( $compte = mysql_fetch_array( $sql_compte ) )
    {
      // Pas de changement de compte
      $sql_date = mysql_query("
        SELECT Date, count(id) AS NbrId
        FROM comptes_flux
        WHERE compte = ".$compte['id']."
        GROUP BY Date
        ORDER BY Date ASC");
      
      WHILE ( $Date = mysql_fetch_array( $sql_date ) )
      {
        IF ( $Date['NbrId'] == 1 )
        {
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <=\"".$Date['Date']."\" AND compte = ".$compte['id'].";" ) );
          $sql_id = mysql_fetch_array( mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".$Date['Date']."\" AND compte = ".$compte['id'].";" ) );
          $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$sql_id['id'] );
        }
        ELSE
        {
          $sql_id = mysql_query( "SELECT id FROM comptes_flux WHERE date = \"".$Date['Date']."\" AND compte = ".$compte['id'].";" );
          $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <\"".$Date['Date']."\" AND compte = ".$compte['id'].";" ) );
          $somme = round($sql_somme['soldes'] - $sql_somme['debits'] , 2 );
          WHILE ( $IDs = mysql_fetch_array( $sql_id ) )
          {
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT solde, debit FROM comptes_flux WHERE id = ".$IDs['id'].";" ) );
            $somme = round($somme + $sql_somme['solde'] - $sql_somme['debit'] , 2 );
            $sql_upd = mysql_query("UPDATE comptes_flux SET total = ".$somme." WHERE id = ".$IDs['id'] );
          }
        }
      }
    }
    
    IF ( $result_compte == 1 AND $result_mode == 1 AND $result_class == 1 AND $sql_add == 1 AND $sql_upd == 1 ) { $result = "Import r&eacute;alis&eacute; avec succ&eacute;s"; } ELSE { $result = "Erreur lors de l'import"; }
    $template->assign_vars( array(
      'RESULTAT' => $result
    ));
  }
}

// Affichage du code
$template->pparse('Header');
$template->pparse('Corpus');
$template->pparse('Footer');
?>