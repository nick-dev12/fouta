<?php

// Paramètres de connexion
$db_host = "localhost";
$db_name = "ariaqqrw_tresor";
$db_user = "ariaqqrw_aria_db";
$db_pass = "Ludvanne12@";

try {
  // Connexion à la base avec PDO
  $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);

  // Définition du mode d'erreur de PDO pour lever des exceptions  
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


} catch (PDOException $e) {
  // Gestion des erreurs
}

?>