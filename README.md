# eedomus script : Nuki smartlock

<img src="asset\nikya_nuki-plugin.svg" alt="Nuki Logo" style="max-width: 150px;"/>

* Plugin version : 1.0
* Origine : [GitHub/Nikya/nuki_smartlock](https://github.com/Nikya/eedomusScript_nuki_smartlock "Origine sur GitHub")
* Nuki Bridge HTTP-API : 1.6 ([API documentation](https://nuki.io/fr/api/))

## Description
***Nikya eedomus Script Nuki Smartlock*** est un plugin pour la box domotique eedomus, qui permet de piloter et connaitre l'état de celle-ci.

Ce plugin est composé d'un script et d'une déclaration pour 3 périphériques :
- Commande d'ouverture/fermeture
- État de la serrure
- Indicateur de batterie faible

Son avantage principal est de mettre à jour l'état de la serrure, seulement si nécessaire, en utilisant la fonctionalité _callback_ de l'API Nuki. (au lieu de créer des _polling_ côté eedomus)

**Nota** : Le script ne sait par gérer plusieurs serrures à la fois. (Si besoin, le script doit être dupliqué)

## Prérequis

Une serrure Nuki Smartlock et son bridge (Matériel ou logiciel)

## Installation via store

Depuis le portail _eedomus_, cliquez sur `Configuration / Ajouter ou supprimer un périphérique` > `Store eedomus` puis sélectionner _Nikya Nuki Smartlock_.

Des informations seront demandées pour la création des 3 périphériques. (Voir §Discovery)

## Installation manuelle

1. Télécharger le projet sur GitHub : [GitHub/Nikya/nuki_smartlock](https://github.com/Nikya/eedomusScript_nuki_smartlock "Origine sur GitHub")
1. Uploader le fichier `dist/nukismartlock.php` sur la box ([Doc eedomus scripts](http://doc.eedomus.com/view/Scripts#Script_HTTP_sur_la_box_eedomus))
2. Créer manuellement les 3 périphériques.

### Paramétrage

Informations à prendre en note, car à réutiliser ultérieurement.

1. **Discovery** : Appeler l'URL suivante pour connaitre l'IP et le port local de votre Bridge
	* URL : https://factory.nuki.io/discover/bridges.
	* Résultat : Une IP et un port
2. **Get Token (auth)** : S'authentifier sur le brige, avec l'IP et le port obtenu précédemment, en appelant l'URL suivante et en confirmant par un appui sur le bouton physique du bridge.
 	* URL : http://192.168.1.50:8080/auth
 	* Résutat : Un token
3. **Setup script** : Configurer le script eedomus, avec les informations obtenues, en appelant la _fonction setup_
5. **Register script** : Configurer le script eedomus, avec les informations obtenues, en appelant la _fonction register_

### Les fonctions du script

Executer le script eedomus en précisant une `function`.

* Format : https://[ip_box_eedomus]/script/?exec=nukismartlock.php&function=
* Exemple : https://192.168.1.60/script/?exec=nukismartlock.php&function=toto

#### Fonction _setup_

Configurer ce script.

* params
	- function : `setup`
	- nukihost : IP du bridge Nuki
	- nukiport : Port du bridge Nuki
	- token : Token d'identification
* Résultat
	- (Json) Un listing des équipements trouvés sur le bridge ciblé (Noter le Nuki ID)
* Exemple : https://192.168.1.60/script/?exec=nukismartlock.php&function=setup&nukihost=192.168.1.50&nukiport=8080&token=909090

#### Fonction _register_

Abonner la box eedomus en tant que _Callback_ souhaitant être informé des changements d'état de la serrure.

* params
	- function : `register`
	- eedomushost : IP de votre eedomus qu'appelera le bridge Nuki
	- nukiid : Id du Nuki (Voir _fonction list_)
	- periph_id_state : Code API eedomus du périphérique qui contiendra l'information _ETAT_ de la serrure
	- periph_id_batterycritical : Code API eedomus du périphérique qui contiendra l'information _Batterie faible_ de la serrure
* Résultat
	- (Json) Une confirmation ou non du succès de la fonction
* Exemple : https://192.168.1.60/script/?exec=nukismartlock.php&function=register&eedomushost=192.168.1.60&nukiid=111&periph_id_state=222&periph_id_batterycritical=333

#### Fonction _list_

Lister les équipements connus par bridge Nuki ciblé

* params :
	- function : `list`
* Résultat
	- (Json) Listing
* Exemple : https://192.168.1.60/script/?exec=nukismartlock.php&function=list

#### Fonction _callback list_

Lister les callback enregistrés par le brige Nuki.

* params :
	- function : `callback_list`
* Résultat
	- (Json) Listing des équipements
* Exemple : https://192.168.1.60/script/?exec=nukismartlock.php&function=callback_list

#### Fonction _callback remove_

Supprimer un callback enregistré sur le Bridge Nuki

* params :
	- function : `callback_remove`
	- id : Id du callback à supprimer (obtenue avec la _fonction callback list_)
* Résultat
	- (Json) Listing des équipements
* Exemple : https://192.168.1.60/script/?exec=nukismartlock.php&function=callback_remove&id=222

#### Fonction _incomingcall_

Fonction coeur de ce script, c'est cette fonction qu'appellera le bridge Nuki à chaque changement d'état de la serrure.  
Elle lis les informations reçues et met à jour les périphériques concernés avec les nouvelles valeurs
Inutile de l'appeler, mais un appel manuel permet de savoir si l'ensemble est correctement configuré.

* params :
	- function : `incomingcall`
* Résultat
	- (Json) Résultat des valeurs lues
* Exemple : https://192.168.1.60/script/?exec=nukismartlock.php&function=incomingcall

##### Valeurs possibles

###### Pour _periph value batterycritical_

* 0 : Batterie non faible
* 100 : Batterie faible

###### Pour _periph value state_

ID  | Name
----|-----------------------
0   | uncalibrated
1   | locked
2   | unlocking
3   | unlocked
4   | locking
5   | unlatched
6   | unlocked (lock ‘n’ go)
7   | unlatching
254 | motor blocked
255 | undefined
