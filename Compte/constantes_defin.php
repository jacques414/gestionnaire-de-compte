<?php
/**************************
#
**************************/

// Définition du Menu Horizontal
$result = mysql_query("
	SELECT *
	FROM config_menu_h");

WHILE( $row = mysql_fetch_array( $result ) )
{
  IF ( $row['name'] != '' )
  {
    $template->assign_block_vars('Menu_H', array(
      'LINK' => $row['link'],
      'NAME' => $row['name'],
      'HID' => '?hid='.$row['id']
    ));
  }
}

IF ( $_SESSION['password'] == 'connect' )
{
  $template->assign_block_vars('Menu_H', array(
    'LINK' => 'connect.php?action=deconnect',
    'NAME' => 'D&eacute;connexion'
  ));
}

// Définition des configurations
$result = mysql_query("
  SELECT *
  FROM config");

WHILE ( $row = mysql_fetch_array( $result ) )
{
  $config[$row['name']] = $row['value'];
}
?>