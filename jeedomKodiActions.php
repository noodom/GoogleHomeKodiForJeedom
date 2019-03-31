// Contrôler Kodi par Google Home à l'aide de GoogleHomeKodi (https://github.com/OmerTu/GoogleHomeKodi)

// Liste des kodis déclarés (mettre seulement 'kodi' si configuration par défaut, correspond à la liste des identifiants déclarés dans le fichier /GoogleHomeKodi/kodi-hosts.config.js)
// Attention : mettre les ids en minuscule !
$kodiIds = array('chambre','salon');

// Récupération des tags passés en paramètres.
$tags = $scenario->getTags();

// Vérification de l'existence des tags.
// Si ces derniers n'existent pas, on les crée avec la valeur souhaitée.
(empty($tags['#action#'])) ? $tags['#action#'] = "erreur" : null;
(empty($tags['#param1#'])) ? $tags['#param1#'] = "erreur" : null;
(empty($tags['#param2#'])) ? $tags['#param2#'] = "erreur" : null;

// Initialisation des variables
// URL du serveur GoogleHomeKodi
$tags['#urlServer#'] = "http://192.168.0.102:8099";
// Id du kodi en cours
(empty($scenario->getData('kodiId'))) ? $scenario->setData('kodiId', $kodiIds[0]) : null;
$scenario->setLog('Initialisation kodi de destination : '.$scenario->getData('kodiId'));

// MAJ des tags avant exécution de la suite du scénario.
$scenario->setTags($tags);

// Token
$data = array(
    'token' => 'MyAuthTokenSharedWith_IFTTT_Applet'
);

$data['kodiid'] = $scenario->getData('kodiId');
$payload = json_encode($data);
 
// Construction de la requête
$ret = true;
$actionKodi = $tags['#action#'];

switch ($actionKodi) {
  case 'erreur': // erreur de paramétrage
    // Erreur : pas d'action renseignée
    $ret = false;
    break;
  case 'kodiid':
    $newKodiId = strtolower(str_replace('"', '', $tags['#param1#']));
    if (in_array($newKodiId, $kodiIds)) {
      $scenario->setLog('Modification du kodi de destination : '.$scenario->getData('kodiId').' => '.$newKodiId);
      $scenario->setData('kodiId', $newKodiId);
    }
    else {
      $scenario->setLog('Erreur de modification du kodi de destination : '.$scenario->getData('kodiId').' non remplacé par '.$newKodiId);
      $ret = false;
    }
    break;
  case 'playpause': // lecture/pause
  case 'stop': // stop
  case 'mute': // mute
  case 'volumeup': // augmentation du volume
  case 'volumedown': // diminution du volume
  case 'activate': // activate
  case 'standby': // standby
  case 'shutdown': // shutdown
  case 'hibernate': // hibernate
  case 'reboot': // reboot
  case 'suspend': // suspend
  case 'displayinfo': // affichage info film en cours
  case 'encours': // en cours de lecture
  case 'navback': // menu retour
  case 'navhome': // menu OK
  case 'navselect': // menu OK
    // Action sans paramètre
    $urlKodi = $tags['#urlServer#'].'/'.$tags['#action#'];
    $scenario->setLog('url sans paramètre='.$urlKodi.' ('.$scenario->getData('kodiId').')');
    break;
  case 'playmovie': // lecture d un film
  case 'resumemovie' : // lecture du film au moment arrêté
  case 'setvolume': // modification du volume
  //case 'volumeup': // augmentation du volume
  //case 'volumedown': // diminution du volume
  case 'youtube': // youtube
  case 'genre': // regarder un genre de film
    // Action avec 1 paramètre nécessaire
    if ($tags['#param1#'] == "erreur") {
    	$ret = false;
    }
    else {
      $urlKodi = $tags['#urlServer#'].'/'.$tags['#action#'].'?q='.urlencode(str_replace('"', '', $tags['#param1#']));
	  $scenario->setLog('url avec 1 paramètre='.$urlKodi.' ('.$scenario->getData('kodiId').')');
    }
    break;
  case 'playepisode': // lecture d un episode de serie
    // Action avec 2 paramètres nécessaires    
    if ($tags['#param1#'] == "erreur" || $tags['#param2#'] == "erreur") {
    	$ret = false;
    }
    else {
	    $urlKodi = $tags['#urlServer#'].'/'.$tags['#action#'].'?q='.urlencode(str_replace('"', '', $tags['#param1#'])).'&e='.$tags['#param2#'];
    	$scenario->setLog('url avec 2 paramètres='.$urlKodi.' ('.$scenario->getData('kodiId').')');
    }
    break;
  case 'startmovie': // démarre le film à un instant donné (durée en minutes)
    if ($tags['#param1#'] == "erreur" || $tags['#param2#'] == "erreur") {
    	$ret = false;
    }
    else {
      	// décalage de démarrage du film en minutes
      	$delai = $tags['#param2#']*60;
	    $urlKodi = $tags['#urlServer#'].'/playmovie?q='.urlencode(str_replace('"', '', $tags['#param1#'])).'&delay='.$delai;
    	$scenario->setLog('url avec 2 paramètres='.$urlKodi);
    }
    break;
  case 'navup': //menu haut
  case 'navdown': //menu bas
  case 'navleft': //menu bas
  case 'navright': //menu bas
    $urlKodi = $tags['#urlServer#'].'/'.$tags['#action#'];
    if ($tags['#param1#'] != "erreur") {
      $urlKodi .= '?q='.str_replace('"', '', $tags['#param1#']);
    }
    $scenario->setLog('url de navigation : '.$urlKodi);
    break;   
  case 'netflix': // lancement de l extension Netflix de Kodi
    $urlKodi = $tags['#urlServer#'].'/executeAddon?q=netflix';
    break;
}


if ($ret) {
  if ($actionKodi != 'kodiid') {
    // Paramètres OK : Préparation de la requête
    $ch = curl_init($urlKodi);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // HTTP Header pour la requête POST 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload))
    );

    // Exécution de la requête POST
    $result = curl_exec($ch);

    // Ferme la session
    curl_close($ch);
  }
}
else {
  // Erreur de paramétrage
  $scenario->setLog('Erreur dans le traitement d une action Kodi : '.$actionKodi);
}
