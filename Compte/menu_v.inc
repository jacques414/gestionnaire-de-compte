<?php
// Création du Menu Vertical
$result = mysql_query("
  SELECT *
  FROM `config_menu_v`
  WHERE parent = ".$hid."
  ORDER BY id");

while( $row = mysql_fetch_array($result) )
{
  $template->assign_block_vars('Menu_V', array(
    'LINK' => $row['link'],
    'NAME' => $row['name'],
    'HID' => '?hid='.$row['parent'],
    'VID' => '&vid='.$row['id']
  ));
}
?>