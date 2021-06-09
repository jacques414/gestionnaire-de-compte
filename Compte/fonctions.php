<?php
/*************************************************************
#
#                   Gestion de collection
#
#
#
#
*************************************************************/

// Remplace les caractéres spéciaux
function Replace($value)
{
  // Caractére Spéciaux
  $value = str_replace('\\','\\\\',$value);
  $value = str_replace('\'','\\\'',$value);
  $value = str_replace('
','<br />',$value);
  
  return $value;
}

// Converti le Num du jour de la semaine par son nom
function NumToDay($num)
{
  switch ( $num )
  {case 1:
      $day = 'Lundi';
    break;
    case 2:
      $day = 'Mardi';
    break;
    case 3:
      $day = 'Mercredi';
    break;
    case 4:
      $day = 'Jeudi';
    break;
    case 5:
      $day = 'Vendredi';
    break;
    case 6:
      $day = 'Samedi';
    break;
    case 7:
      $day = 'Dimanche';
    break;}
  return $day;
}

// Converti le Nom du jour de la semaine par son num
function DayToNum($day)
{
  switch ( $day )
  {case 'Monday':
      $num = 1;
    break;
    case 'Lundi':
      $num = 1;
    break;
    case 'Tuesday':
      $num = 2;
    break;
    case 'Mardi':
      $num = 2;
    break;
    case 'Wednesday':
      $num = 3;
    break;
    case 'Mercredi':
      $num = 3;
    break;
    case 'Thursday':
      $num = 4;
    break;
    case 'Jeudi':
      $num = 4;
    break;
    case 'Friday':
      $num = 5;
    break;
    case 'Vendredi':
      $num = 5;
    break;
    case 'Saturday':
      $num = 6;
    break;
    case 'Samedi':
      $num = 6;
    break;
    case 'Sunday':
      $num = 7;
    break;
    case 'Dimanche':
      $num = 7;
    break;}
  return $num;
}

// Calcul le nombre de jour entre deux dates
function NbDay($begin, $ending )
{
  // explode de la date de debut
  $tBegin = explode("-", $begin);
  // explode de la date de fin
  $tEnd = explode("-", $ending);
  // Calcul de la différence en seconde sans prendre en comptes le changement heure d'été/hiver
  $diff = mktime(0, 0, 0, $tEnd[1], $tEnd[2], $tEnd[0], 0 ) - mktime(0, 0, 0, $tBegin[1], $tBegin[2], $tBegin[0], 0 );
  // Retourne le rédultat en nombre de jour
  return ($diff / 86400);
}

// Convertit la date US->FR
function UsToFr($date)
{
  // Explode la date US
  $time = explode("-", $date);
  // Reformatage en date FR
  IF ( isset( $time[1] ) ) { $date = $time[2]."/".$time[1]."/".$time[0]; }
  // Retourne la date
  return $date;
}

// Convertit la date FR->US
function FrToUs($date)
{
  // Explode de la date FR
  $time = explode("/", $date);
  // Reformatage en date US
  IF ( isset( $time[1] ) ) { $date = $time[2]."-".$time[1]."-".$time[0]; }
  // Retourne la date
  return $date;
}
?>