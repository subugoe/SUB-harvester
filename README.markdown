# OAI Harvester
OAI Harvester with a web interface for configuration.
Currently available in German only.

Geschrieben von Timo Schleier, [SUB Göttingen](http://www.sub.uni-goettingen.de) <timo.schleier@sub.uni.goettingen.de>
Änderungen von Sven-S. Porst, [SUB Göttingen](http://www.sub.uni-goettingen.de) <porst@sub.uni-goettingen.de>


## Einrichtung
Der Harvester benötigt PHP 5.3, Zugang zu einer MySQL Datenbank und für die Konfigurationsoberfläche einen apache Webserver.

Zur Installation, diesen Ordner über den Webserver zugänglich machen.

Zur Konfiguration einer Harvester-Instanz mit einer gegebenen `ID` folgende Schritte durchführen:

1. Anlegen eines Ordners mit Namen `ID` im Ordner configuration. Dieser Ordner enthält die Dateien:

	1. **db_connect.php** mit Zugangsdaten zur MySQL Datenbank, der Inhalt dieser Datei sollte folgendes Format haben:
		
		```
		<?php
			define("DB_SERVER",		"localhost");
			define("DB_USERNAME", 	"harvest");
			define("DB_PASSWORD", 	"");
			define("DB_NAME", 		"harvest");
		?>
		```

	2. **settings.php** mit Einstellungen für Pfade und den Solr Index. Der Inhalt dieser Date sollte folgendes Format haben:

		```
		<?php
			// MySQL Verbindungsdaten
			include(dirname(__FILE__) . '/db_connect.php');

			// Allgemein
			define("SERVICE_NAME", 		'math-arxiv');
			define("SOLR",				'http://localhost:8080/solr/math-arxiv');
			define("DATA_FOLDER",		'/var/www/htdocs/harvester/data/math-arxiv');

			// gemeinsam genutzte Einstellungen
			include(dirname(__FILE__) . '/../settings-common.php');
		?>
		```

2. Anlegen eines Ordners mit Namen `ID` im Hauptordner. Dieser Ordner enhält:

	1. **.htaccess** mit dem Inhalt: `SetEnv HARVESTER_CONFIGURATION_NAME <ID>`
	2. **index.php**: Symlink auf ../software/index.php
	3. **resources**: Symlink auf ../software/resources

3. Anlegen des MySQL-Datenbankschemas: Die Datei *configuration/harvester_schema.sql* enthält das benötigte Schema für die MySQL Datenbank. Es muß vor der ersten Nutzung dort eingespielt werden.
4. Anlegen des Solr-Schemas: Die Datei *configuration/schema.xml* enthält ein Solr Indexschema, das mit den ausgegebenen Feldern klarkommt. Es muß an der entsprechenden Stelle der Solr Konfiguration abgelegt werden.

### Anmerkung
Da zur Auswahl der Sets ein Formular mit sehr vielen Eingabeelementen abgeschickt wird, muß sichergestellt sein, daß die Konfiguration des Webservers diese Parameter nicht abschneidet, wenn sehr viele Sets vorhanden sind. Mit den Standardeinstellungen werden 1000 POST Parameter zugelassen, so daß die Bearbeitung von knapp 500 Sets funktioniert. Einige OAI Server enhalten wesentlich mehr Sets (z.B. über 2000 auf http://hal.archives-ouvertes.fr/oai/oai.php), so daß hier die Konfiguration angepaßt werden muß:

* Ab PHP 5.3.9 begrenzt die Einstellung `max_input_vars` die Anzahl der Parameter (Voreinstellung vermutlich in /etc/php5/apache2/php.ini)
* Ist Suhosin installiert, müssen die Werte `suhosin.request.max_vars` und `suhosin.post.max_vars` hochgesetzt werden (Voreinstellung vermutlich in /etc/php5/conf.d/suhosin.ini)
* Die Anzahl der benötigten Parameter sollte mindestens 3 * Anzahl der Sets + 20 sein


## Verwaltung der OAI Quellen
Der Harvester bietet über den Webserver eine Oberfläche zur Verwaltung der geharvesteten OAI Quellen an. Sie zeigt die auf den OAI-Servern verfügbaren Sets and und ermöglicht eine Auswahl.


## Harvesting und Indizieren
Zum Ausführen des eigentlichen Harvestings. In der Shell:

1. die Umgebungsvariable `HARVESTER_CONFIGURATION_NAME` auf die `ID` der gewünschten Instanz setzen
2. das Skript *software/scripts/harvester.php* ausführen
3. das Skript *software/scripts/indexer.php* ausführen

Erfolgs- und Fehlermeldungen dieser Skripte sind in der Weboberfläche einsehbar.


## Interner Ablauf
Die Software erfährt über die Umgebungsvariable `HARVESTER_CONFIGURATION_NAME` den Namen der gewünschten Konfiguration und lädt dementsprechend die Einstellungen für Pfade und Datenbankzugriff. In der MySQL-Datenbank sind die bekannten OAI-Repositories, deren Sets und die ausgewählten Sets gespeichert.

Die geladenen Daten werden im Datenordner data/`ID`/ abgelegt. Dieser kann folgenden Unterordner haben:

* **harvest**: hier werden von harvester.php die geladenen OAI Daten abgelegt
* **temp**: enthält kurzzeitig während der Konversion von OAI zu Solr anfallende Daten
* **archive**: enthält zip-Dateien mit den geladenen OAI Daten und den daraus erstellten Solr-Dokumenten
* **error**: enthält Dateien mit den Fehlermeldungen, die bei den Durchläufen von software/scripts/harvester.php und software/scripts/indexer.php aufgetreten sind

Diese Ordner enthalten jeweils Unterordnerstrukturen mit dem akutellen Datum, der ID des OAI Servers und der ID des Sets. Zur leichteren Orientierung sind Dateien *source_data.txt* mit den dazugehörigen Namen abgelegt.

Die Daten im Ordner archive können nützlich sein, wenn die Indizierung verändert werden soll. In diesem Fall muß nicht das Harvesting erneut stattfinden, sondern es können die zip Dateien entpackt werden und der Inhalt ihrer *harvest* Unterordner kann in den *harvest* Ordner gelegt werden, um dann das Skript software/scripts/indexer.php erneut zu starten. Das Skript software/scripts/reindex.php führt diese Schritte durch.

