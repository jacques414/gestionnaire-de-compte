<?php
/*************************************************************
#
#                   Gestion de collection
#
#
#
#
*************************************************************/

session_start();

require 'includes/constantes.php';
require 'includes/fonctions.php';
require 'includes/template.php';

// connection a la basse de donnee
mysql_connect($BD_serv , $BD_login, $BD_pass);
mysql_select_db($BD_name);

// Définition du dossier de theme
$template = new Template('template/');

// Definiions des fichiers principaux pour l'affichage du site
require 'includes/constantes_defin.php';
$template->set_filenames(array(
  'Header' => 'header.htm',
  'Footer' => 'footer.htm',
  'Corpus' => 'password.htm'));

// Definition des valeurs globals
$template->assign_vars(array(
  'TITRE' => $config['titre']
));

// 
IF ( isset( $_GET['action'] ) )
{
  // Connexion
  IF ( $_GET['action'] == 'connect' )
  {
    IF ( md5( $_POST['password'] ) == $config['password'] )
    {
      // Connection
      $_SESSION['password'] = 'connect';
      
      // Application des action planifié
      IF ( $config['date'] != Date('Y-m-d') )
      {
        // Explode de la date de la derniére utilisation
        $tps = explode("-", $config['date']); $day = $tps[2]; $month = $tps[1]; $year = $tps[0];
        
        // Sélection des planifications
        $sql = mysql_query("
          SELECT *
          FROM comptes_planifier
          WHERE etat = 1");
        
        // Traitement
        WHILE ( $plani = mysql_fetch_array( $sql ) )
        {
          // Cas journaliers
          IF ( $plani['plani'] == 1 AND Date('Y-m-d') >= $plani['date'] AND $plani['date'] > $config['date'] )
          {
            // UPDATE
            $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <=\"".$plani['date']."\" AND compte = ".$plani['compte'].";" ) );
            $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] + $plani['solde'] - $plani['debit'] , 2) ;
            $sql_add = mysql_query("INSERT INTO comptes_flux VALUES ( '' , '".$plani['date']."', ".$plani['compte'].", '". $plani['description'] ."', ".$plani['class'].", ".$plani['mode'].", ". str_replace(',','.',$plani['debit']) .", ". str_replace(',','.',$plani['solde']) .", ".$somme." )");
            $sql_upd = mysql_query( "UPDATE comptes_planifier SET etat = 0 WHERE id = ".$plani['id'] );
          }
          // Cas Annuel
          ELSE IF ( $plani['plani'] == 365 AND Date('Y-m-d') >= Date("Y-").$plani['date'] AND Date("Y-").$plani['date'] > $config['date'] )
          {
            $i = $year;
            WHILE ( $i <= Date("Y") )
            {
              $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <= \"".$i."-".$plani['date']."\" AND compte = ".$plani['compte'].";" ) );
              $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] + $plani['solde'] - $plani['debit'] , 2) ;
              $sql_add = mysql_query("INSERT INTO comptes_flux VALUES ( '' , '".$i."-".$plani['date']."', ".$plani['compte'].", '". $plani['description'] ."', ".$plani['class'].", ".$plani['mode'].", ". str_replace(',','.',$plani['debit']) .", ". str_replace(',','.',$plani['solde']) .", ".$somme." )");
              $i++;
            }
          }
          // Cas mensuel
          ELSE IF ( $plani['plani'] == 31 )
          {
            $y = $year;
            // Boucle pour les années
            WHILE ( $y <= Date("Y") )
            {
              $m = $month;
              // Boucle pour les mois
              WHILE ( $m <= Date("m") )
              {
                $tps_date = date("Y-m-d", mktime(0, 0, 0, $m, $plani['date'], $y) );
                // Filtrage des dates
                IF ( Date('Y-m-d') >= $tps_date AND $tps_date > $config['date'] )
                {
                  $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <= \"".$tps_date."\" AND compte = ".$plani['compte'].";" ) );
                  $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] + $plani['solde'] - $plani['debit'] , 2) ;
                  $sql_add = mysql_query("INSERT INTO comptes_flux VALUES ( '' , '".$tps_date."', ".$plani['compte'].", '". $plani['description'] ."', ".$plani['class'].", ".$plani['mode'].", ". str_replace(',','.',$plani['debit']) .", ". str_replace(',','.',$plani['solde']) .", ".$somme." )");
                } $m++; } $y++; } }
          ELSE IF ( $plani['plani'] == 7 )
          {
            $Day = DayToNum( strftime( "%A", mktime( 0, 0, 0, $month, $day, $year ) ) );
            $NbDay = NbDay($config['date'], Date('Y-m-d'));
            
            IF ( $Day < $plani['date'] )
            {
              $diff = $plani['date'] - $Day;
              $tps_date = date('Y-m-d', mktime( 0, 0, 0, $month, $day + $diff, $year ));
              $sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <= \"".$tps_date."\" AND compte = ".$plani['compte'].";" ) );
              $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] + $plani['solde'] - $plani['debit'] , 2) ;
              $sql_add = mysql_query("INSERT INTO comptes_flux VALUES ( '' , '".$tps_date."', ".$plani['compte'].", '". $plani['description'] ."', ".$plani['class'].", ".$plani['mode'].", ". str_replace(',','.',$plani['debit']) .", ". str_replace(',','.',$plani['solde']) .", ".$somme." )");
            }
            
            $NbDay = $NbDay - 7 + $Day;
            $Days = 7 - $Day;
            
            WHILE ( $NbDay > 0 )
            {
              $diff = $Days + $plani['date'];
              $tps_date = date('Y-m-d', mktime( 0, 0, 0, $month, $day + $diff, $year ));
              IF ( $tps_date >= Date('Y-m-d') )
              {$sql_somme = mysql_fetch_array( mysql_query( "SELECT SUM(solde) AS soldes, SUM(debit) AS debits FROM comptes_flux WHERE date <= \"".$tps_date."\" AND compte = ".$plani['compte'].";" ) );
              $somme = round( $sql_somme['soldes'] - $sql_somme['debits'] + $plani['solde'] - $plani['debit'] , 2) ;
              $sql_add = mysql_query("INSERT INTO comptes_flux VALUES ( '' , '".$tps_date."', ".$plani['compte'].", '". $plani['description'] ."', ".$plani['class'].", ".$plani['mode'].", ". str_replace(',','.',$plani['debit']) .", ". str_replace(',','.',$plani['solde']) .", ".$somme." )");}
              $Days = $Days + 7; $NbDay = $NbDay - 7;
            }
          }
        }
        mysql_query("UPDATE config SET value='".Date('Y-m-d')."' WHERE name = 'date'");
      }
    
      // Mise à jour des comptes
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
      
      // Redirection vers l'index
      header('Location: index.php');
    }
  }
  // Déconnexion
  IF ( $_GET['action'] == 'deconnect' )
  {
    $_SESSION['password'] = '';
    unset( $_SESSION['password'] );
    header('Location: connect.php');
  }
}

// Affichage du code
$template->pparse('Header');
$template->pparse('Corpus');
$template->pparse('Footer');
?>