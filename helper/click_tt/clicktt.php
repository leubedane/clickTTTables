<?php
require_once('verband.php');
require_once('Roman.php');
require_once('simple_html_dom.php');

define("DEBUG", false);

class ClickTT {
	var $clubID = null;
	var $verband = null;
	var $clickTTUrl = null;
	var $clubName = null;

	var $imageUrl = null;

	var $cache = array();

	var $cacheBilanz = array();
	var $cacheDetailBilanz = array();

	var $regExps = array();

	/**
	 * Läd die Regulären Ausdrücke und sucht dann die clubID.
	 *
	 *  @param string $url Die komplette Url zu einer Click-TT Seite
	 * 	z.B. http://wttv.click-tt.de/cgi-bin/WebObjects/ClickWTTV.woa/wa/
	 * @param string $club Der Name des Vereins. Dies muss der Kurzname wie er
	 * 	in Tabellen oder Terminplänen benutzt wird. Über die Vereinssuche (Adressen, Mannschaften, Spieler, Ergebnisse)
	 *  muss dieser Name zur Vereinsseite führen.
	 */
	function ClickTT($verbandName, $vereinsNummer, $vereinsName, $id=null) {
		if(DEBUG)
			echo "<br />new ClickTT($verbandName, $vereinsNummer, $vereinsName, $id)";

		$this->setDefaultRegExp();
		$verbandNeu = new Verband();
		$this->verband = $verbandNeu->getVerband($verbandName);
		$this->clickTTUrl =  $this->verband->url;

		$this->clubName = $vereinsName;
		if(!$id)
			$this->clubID = $this->getClubIDByNummer($vereinsNummer);
		else
			$this->clubID = $id;
		if(DEBUG) echo "<br/>"."----".$this->clubID."----";


		$this->setImageUrl("http://www.aggertalerttc.de/components/com_ttverein/images/");
	}

	function getCache($variable) {
		$variable = strtolower($variable);
		if(array_key_exists($variable, $this->cache))
			return $this->cache[$variable];
			
		switch($variable) {
			case "clubid":
				return $this->clubID;
		}
		return null;
	}

	/**
	 * @param string $url Die Url zum Verzeichnis der Auf und Abstiegspfeile
	 */
	function setImageUrl($url) {
		$this->imageUrl = $url;
	}

	/**
	 * Läd die Regulären Ausdrücke. Im Augenblick nur eine Dummy Methode.
	 * In Zukunft sollen diese Strings aus der Konfiguration geladen werden.
	 */
	function setDefaultRegExp() {
		$this->setRegExp("Saison", "/<h2>Mannschaftsmeisterschaft&nbsp;(.*)\n/Usi");
		$this->setRegExp("PersonenID", "/&amp;person=(.*)&/Usi");
		$this->setRegExp("PersonenName", "/>(.*)</Usi");
		$this->setRegExp("Bilanz", "/Einzelbilanzen<\/b><\/td>\n *<td>(.*)<\/td>/Usi");
		$this->setRegExp("TeamID", "/groupPage\?championship=(.*)\"/Usi");
		$this->setRegExp("SearchClubID", "/club=(.*)\"/Usi");
	}

	function setRegExp($name, $exp) {
		$this->regExps[$name] = $exp;
	}

	function getRegExp($name) {
		if(array_key_exists($name, $this->regExps))
			return $this->regExps[$name];
		return null;
	}

	function getSaisonName($saisonStart) {
		$start = intval($saisonStart);
		return $start . "/" . substr($start+1, 2);
	}

	function getClubIDByNummer($clubName) {
		if(DEBUG)
			echo "<br />getClubIDByNummer($clubName)";

		if(!$clubName)
			return null;

		$content = file_get_contents($this->buildClubSearchUrl($clubName));
		preg_match($this->getRegExp("SearchClubID"), $content, $id);

		return intval($id[1]);
	}

	function getSaisonStarts() {
		if(DEBUG)
			echo "<br />getSaisonStarts()";
		$offset = 0;
		$count = 0;

		$seasonNames = array();
		if(!$this->clubID)
			return $seasonNames;

		$content = file_get_contents($this->buildClubUrl());
		while(preg_match($this->getRegExp("Saison"), $content, $saison, PREG_OFFSET_CAPTURE)) {

			$offset = $saison[1][1] + 1;
			$content = substr($content, $offset);

			$seasonNames[] = intval($saison[1][0]);

			if($count++ >= 100) {
				echo "Parser Error";
				break;
			}
		}

		return $seasonNames;
	}

	function getNextMatches($clubId){
		if(!$clubId){
			return;
		}
		$content = $this->getUserAgentSite($this->buildClubInfoDisplayUrl($clubId));
		$dom = $this->getDomForData($content);
		$table = $dom->find("table[class=result-set]",1);
		$rows = array_slice($table->find('tr'), 1);
		$newTable = '<table class="table table-bordered"><tbody>';

		foreach ( $rows as $element ) {
			
			//echo '<h3>'. $element->plaintext . '</h3>';
			$newTable."<tr>";
			$cols = $element->find('td'); // array_slice($element->find('td'), 2);
			if(trim($cols[1]->plaintext) !== '' && trim($cols[1]->plaintext) !== '&nbsp;'){
				$currentDate = $cols[1]->plaintext;
			}
			if( strpos($cols[7]->plaintext, 'spielfrei') || strpos($cols[6]->plaintext, 'spielfrei')){
				continue;
			}
			$newTable.="<td style='line-height:5px;'>".$currentDate."</td>";//."<br/>".$cols[2]->plaintext."Uhr</td>";
			$newTable.="<td style='line-height:5px;'>".$cols[2]->plaintext."Uhr</td>";
			//$newTable.=."Uhr";
			$newTable.="<td style='line-height:5px;'>".$this->getTeamPrefixName($cols[6]->plaintext, $cols[5]->plaintext)."</td>";
			$newTable.="<td style='line-height:5px;'>".$this->getTeamPrefixName($cols[7]->plaintext, $cols[5]->plaintext)."</td>";
			
			
			$newTable.="</tr>";

			
		}
		$newTable.="</tbody></table>";
		//return $this->fitTableForOutput($table);
		return $newTable;
	}

	function getTeamPrefixName($colName, $championship) {
		if(strpos($colName, $this->clubName)=== false){
			return $colName;
		}
		if(strpos($championship, "Ju") !== false){
			$colName = str_replace( $this->clubName, "Jugend", $colName);
			return $colName;
		}
		if(strpos($championship, "He") !== false){
			$colName = str_replace( $this->clubName, "Herren", $colName);
			return $colName;
		}
		if(strpos($championship, "Da") !== false){
			$colName = str_replace( $this->clubName, "Damen", $colName);
			return $colName;
		}
		if(strpos($championship, "Sm") !== false){
			$colName = str_replace( $this->clubName, "Schüler", $colName);
			return $colName;
		}
		return $colName;
	}

	function getTeamTable($championship, $group) {
		if(DEBUG)
			echo "<br />getTeamTable($championship, $group)";

		if(!$championship || !$group)
			return "";
			
		$content = $this->getUserAgentSite($this->buildTableUrl($championship, $group));

		preg_match("/<table class=\"result-set\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\">(.*)<\/table>/Usi", $content, $table);
		$table = $table[1];
		/*
		 * Auf und Abstiegspfeile ersetzen
		*
		* /WebObjects/Frameworks/TennisFramework.framework/WebServerResources/up_11x11.gif" width="11" height="11"
		* /WebObjects/Frameworks/TennisFramework.framework/WebServerResources/up_grey_11x11.gif" width="11" height="11"
		* /WebObjects/Frameworks/TennisFramework.framework/WebServerResources/down_grey_11x11.gif" width="11" height="11"
		* /WebObjects/Frameworks/TennisFramework.framework/WebServerResources/down_11x11.gif" width="11" height="11"
		*/

		$clickttImageUrl = "/WebObjects/nuLiga.woa/Frameworks/nuLigaFramework.framework/WebServerResources/img/icons/";
		$clickttImageParams = '" width="11" height="11" />';
		$newImageParams = '" width="11" height="5" />';

		$table = str_replace($clickttImageUrl . "up_11x11.gif" . $clickttImageParams, $this->imageUrl . "aufstieg.png" . $newImageParams, $table);
		$table = str_replace($clickttImageUrl . "up_grey_11x11.gif" . $clickttImageParams, $this->imageUrl . "relegation_auf.png" . $newImageParams, $table);
		$table = str_replace($clickttImageUrl . "down_grey_11x11.gif" . $clickttImageParams, $this->imageUrl . "relegation_ab.png" . $newImageParams, $table);
		$table = str_replace($clickttImageUrl . "down_11x11.gif" . $clickttImageParams, $this->imageUrl . "abstieg.png" . $newImageParams, $table);

		/*
		 * Relative Links in absolute click-tt.de Links umwandeln.
		* Zusätzlich Links in neuen Fenster/Tab öffnen lassen.
		*/
		$table = str_replace('href="/cgi-bin/WebObjects/', 'target="_blank" href="' . $this->verband->domain . '/cgi-bin/WebObjects/', $table);

		/*
		 * XHTML Komform
		* ein <a>-Tag hat kein alt-Attribut
		*/
		$table = str_replace('title="Mannschaftsportrait und Spielerbilanzen" alt="Mannschaftsportrait und Spielerbilanzen"', 'title="Mannschaftsportrait und Spielerbilanzen"', $table);

		//Wandelt Text (Umlaute) in UTF-8 um.
		//$table = iconv("ISO-8859-1", "UTF-8", $table);

		return "<table>" . $table . "</table>";
	}

	function getTimeTable($teamTable, $pageState, $championship, $group) {
		$data = $this->loadHTMLForFormationAndGameplan($teamTable, $pageState, $championship, $group);
		$dom = $this->getDomForData($data);

		//get Gameplan
		$table = $dom->find("table[class=result-set]",1);
		return $this->fitTableForOutput($table);
	}

	function getFormation($teamTable, $pageState, $championship, $group) {
		
		$data = $this->loadHTMLForFormationAndGameplan($teamTable, $pageState, $championship, $group);
		$dom = $this->getDomForData($data);
		$table = $dom->find("table[class=result-set]",2);
		$val = 0;
// 		for($i = 0; $i<20; $i++) {
// 			$val = trim($table->children(i)->children(0));
// 			if(i == 7)
// 			$table->children(i) = null;
// 		}
// 		foreach($table->find('tr') as $row) {
			
// 			$var = trim($row->children(0));
// 			if(empty($var)) $row = null;
// // 			if($row->children(1))
// 			if($val == 12) {
// 				$table->removeAttribute($row);
// 			}
// 			if($val < 15)
// 			$val++;
			
// // 			// initialize array to store the cell data from each row
// // 			$rowData = array();
// // // 			foreach($row->find('td.text') as $cell) {
		
// // // 				// push the cell's text to the array
// // // 				$rowData[] = $cell->innertext;
// // // 			}
		
// // 			// push the row's data array to the 'big' array
// // // 			$theData[] = $rowData;
// 		}
// 		$test = '/<tr>
// 					<td><\/td>(.*?)<\/tr>/s';
// 		$table = preg_replace("/&#?[a-z0-9]+;/i","",$table);
// 		$table = preg_replace($test, "", $table);
// 		echo "<!--".$table."-->";
		return $this->fitTableForOutput($table);
	}
	function fitTableForOutput($table) {
		$table = str_replace('href="/cgi-bin/WebObjects/', 'target="_blank" href="' . $this->verband->domain . '/cgi-bin/WebObjects/', $table);
		$table = str_replace('src="/WebObjects/', 'target="_blank" src="http://ttvbw.click-tt.de/WebObjects/', $table);
		return "<table>".$table."</table>";
	}
	
	function getDomForData($data) {
		
		$html = new simple_html_dom();
		$html->load($data);
		return $html;
	}
	function loadHTMLForFormationAndGameplan($teamTable, $pageState, $championship, $group) {
		if(DEBUG)
			echo "<br />loadHtmlForInfos($teamTable, $pageState, $championship, $group)";

		if(!$championship || !$group || !$teamTable || !$pageState)
			return "";

		//get Page for Teamview
		return $this->getUserAgentSite($this->buildTeamUrl($teamTable, $pageState, $championship, $group));
	}
	function toRomanNumber($number) {
		return Numbers_Roman::toNumeral($number,true,false);
	}

	function toNumber($roman) {
		return Numbers_Roman::toNumber($roman);
	}

	function getUserAgentSite($url, $useragent=null) {
		if($useragent==null){
			$useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2";
		}
		$opts = array(
				'http'=>array(
						'method'=>"GET",
						'header'=>"Accept-language: de\r\n" .
						"User-Agent: ".$useragent."\r\n" .
						"Referer: ".$url."\r\n"
				)
		);
		$context = stream_context_create($opts);
	  
		$fp = @file_get_contents($url, false, $context);
		return $fp;

	}

	function convertString($string) {
		//$tmp = iconv("ISO-8859-1", "UTF-8", trim($string));
		return str_replace("&nbsp;", " ", trim($string));
	}

	function hasRundeString($string) {
		return ($this->hasHinrundeString($string)
				|| $this->hasRueckrundeString($string)
		);
	}

	function hasHinrundeString($string) {
		return (stripos($string, "Hinrunde")
				|| stripos($string, "Vorrunde")
		);
	}
	function hasRueckrundeString($string) {
		return (stripos($string, "Rückrunde")
				|| stripos($string, "R&uuml;ckrunde")
		);
	}

	//TODO Auch ältere Teams. Dies ist aber bis jetzt nicht möglich
	function getTeams($altersKlasse=null, $mannschaftNum=null, $hinrunde=true) {

		if(DEBUG) {
			if($hinrunde)
				$hin = "true";
			else
				$hin = "false";
			echo "<br />getTeams(\"$altersKlasse\", $mannschaftNum, $hin)";
		}
			
		$mannschaften = array();

		//Mannschaften und Ligeneinteilung seite Laden
		$html = new simple_html_dom();
		$data = $this->getUserAgentSite($this->buildTeamProfilUrl());
		$html->load($data);

		//Tabelle mit den Mannschaften und Ligeneinteilung
		$table = $html->find("table[class=result-set]",0);

		$zeilen = $table->find("tr");
		//Ersten beiden Zeilen Überspringen, da Überschriften
		for($i=2; $i < count($zeilen); $i++) {
			$zeile = $zeilen[$i];

			//Manchmal in mehrere Abschnitte augeteilt
			if(array_key_exists('class', $zeile->attr)
					&& $zeile->attr['class'] == "table-split") {
				$td = $zeile->find("td",0);
				$h2 = $td->find("h2",0);
				//Teil der Tabelle mit Pokal Klassen überspringen
				if( stripos($h2->innertext(), "Pokal") !== false )
					break;

				//Überschrift überspringen
				$i++;
				continue;
			}
				
			/*
			 * Erste Splate enthält die Altersklasse mit Römischer nummer.
			* Außer die Erste Mannschaft - keine Ziffer
			* z.B.
			* 1. Herren => Herren
			* 2. Herren => Herren II
			*
			*/
			$td = $zeile->find("td", 0);
			$clickAltersKlasse = $this->convertString($td->innertext());
			//Bugix wenn " (Z)" im Namen ist dies entfernen.
			$clickAltersKlasse = str_replace(" (Z)", "", $clickAltersKlasse);
				
			//int Zahl in Römische Zahl umwandel um Klassen vergleichen zu können
			$klassenString = $altersKlasse;
			if($mannschaftNum != null && $mannschaftNum > 1) {
				$klassenString .=  " " . $this->toRomanNumber($mannschaftNum);
			}

			/*
			 * Ist keine Altersklasse angegeben werden alle geladen
			* Ist die Mannschaftsnummer angegeben muss die Altersklasse genau übereinstimmen.
			* Sonst muss der String nur vorkommen.
			*/
			if( $altersKlasse == null
					|| ($mannschaftNum != null && $clickAltersKlasse == $klassenString)
			 	|| ($mannschaftNum == null && stripos($clickAltersKlasse,$klassenString) !== false)
			) {
				//Zweite Spalte enthält Link zur Tabelle und genaue Klassenbezeichnung
				$td = $zeile->find("td", 1);
				$a = $td->find("a",0);
				$klassenBezeichnung = $this->convertString($a->innertext());

				/*
				 * Wenn in der Klassenbezeichnung ein Vorrunde oder Rückunde enthalten ist,
				* muss die richtige Klasse ausgewählt werden. Sonst ist die Klasse für Vor-
				* und Rückrunde.
				*/
				if($this->hasRundeString($klassenBezeichnung)) {
					/*
					 * Zeile überspringen wenn falsche Runde
					*/
					if($hinrunde && !$this->hasHinrundeString($klassenBezeichnung)) {
						continue;
					} else if(!$hinrunde && !$this->hasRueckrundeString($klassenBezeichnung)) {
						continue;
					}
				}

				/*
				 * championship und group aus dem Link filtern
				*/
				$href = $a->attr['href'];
				$tmp = explode("?", $href);
				$params = explode("&amp;", $tmp[1]);
				$championship = substr($params[0], 13);
				$group = substr($params[1], 6);

				/*
				 * Mannschaftsnummer entweger übergeben worden
				* oder
				* sie ist am Ende des Namens der AltersKlasse
				* oder
				* sie ist Mannschaft nummer 1
				*/
				if($altersKlasse != null && $mannschaftNum != null) {
					$nummer = $mannschaftNum;
					$klasse = $altersKlasse;
						
					//Rückgabewerte vorbereiten
					$mannschaften[$klasse][$nummer]['championship'] = $championship;
					$mannschaften[$klasse][$nummer]['group'] = $group;
						
					/*
					 * Wenn der Klassenbezeichnung "Vorrunde" oder "Rückrunde" enthält
					* ist die Richtige Klasse gefunden worden. Ansonsten könnte es sein,
					* dass die nächste Klasse die richtige ist.
					*/
					if($this->hasRundeString($klassenBezeichnung))
						return $mannschaften;
						

				} else if(strripos($clickAltersKlasse, "I") == strlen($clickAltersKlasse)-1
						|| strripos($clickAltersKlasse, "V") == strlen($clickAltersKlasse)-1
						|| strripos($clickAltersKlasse, "X") == strlen($clickAltersKlasse)-1
				) {
					$tmp = explode(" ", $clickAltersKlasse);
					$nummer = $this->toNumber(array_pop($tmp));
					//Altersklasse ohne Röische Nummer
					$klasse = implode(" ", $tmp);
				} else {
					$klasse = $clickAltersKlasse;
					$nummer = 1;
				}

				//Rückgabewerte vorbereiten
				$mannschaften[$klasse][$nummer]['championship'] = $championship;
				$mannschaften[$klasse][$nummer]['group'] = $group;

			}

		}

		return $mannschaften;
	}



	function getPersons($saisonStart, $alterklasse="Herren", $hinrunde=true) {
		if(DEBUG) {
			$rundetext = ($hinrunde) ? "true" : "false";
			echo "<br />getPersons($saisonStart, \"$alterklasse\", $rundetext)";
		}
		$seasonName = $this->getSaisonName($saisonStart);
		$personen = array();

		$offset = 0;
		$count = 0;

		$content = file_get_contents($this->buildAufstellungUrl($seasonName, $alterklasse, $hinrunde));

		if(preg_match($this->getRegExp("PersonenID"), $content, $id, PREG_OFFSET_CAPTURE) == 0)
			$content = file_get_contents($this->buildAufstellungUrl($seasonName, $alterklasse, null));

		while(preg_match($this->getRegExp("PersonenID"), $content, $id, PREG_OFFSET_CAPTURE)) {
			$offset = $id[1][1] + 1;
			$content = substr($content, $offset);

			preg_match($this->getRegExp("PersonenName"), $content, $name);
			$name = explode(", ", $name[1])	;

			/*
			 * click-tt's Codierung ist ISO-8859-1. Strings müssen dementsprechend umgewandelt werden.
			*/
			//			$personen[$count]->nachname = iconv("ISO-8859-1", "UTF-8", $name[0]);
			//			$personen[$count]->vorname = iconv("ISO-8859-1", "UTF-8", $name[1]);
			$personen[$count]->nachname = $name[0];
			$personen[$count]->vorname = $name[1];
			$personen[$count++]->id = intval($id[1][0]);
		}

		return $personen;
	}

	function getPersonByName($vorname, $nachname, $saisonStart, $alterklasse="Herren", $hinrunde=true) {
		if(DEBUG)
			echo "<br />getPersonByName($vorname, $nachname, $saisonStart, $alterklasse, $hinrunde)";

		$personen = $this->getPersons($saisonStart, $alterklasse, $hinrunde);
		foreach($personen as $person) {
			if($person->nachname == $nachname && $person->vorname == $vorname) {
				return $person;
			}
		}

		//In anderer Runde suchen
		$personen = $this->getPersons($saisonStart, $alterklasse, !$hinrunde);
		foreach($personen as $person) {
			if($person->nachname == $nachname && $person->vorname == $vorname) {
				return $person;
			}
		}

		return null;
	}

	/**
	 * Läd die Bilanzen eines Spieler aus einer bestimmten Saison aus click-TT.
	 * @access public
	 * @param string $seasonName Saison aus der die Bilanz genommen werden soll.
	 * @param int $personID Die Click-TT ID des Spielers.
	 * @return array Gibt einen Array von der Struktur array[$klasse][$i][]['position'|'saetze'] zurück.
	 */
	function getDetailBilanz($saisonStart, $personID) {
		if(DEBUG)
			echo "<br />getDetailBilanz($saisonStart, $personID)";
		//Zwischengespeicherte Werte Zurückgeben
		if(array_key_exists($saisonStart . $personID, $this->cacheDetailBilanz) && is_array($this->cacheDetailBilanz[$saisonStart . $personID]))
			return $this->cacheDetailBilanz[$saisonStart . $personID];
		if(!$saisonStart || !$personID)
			return array();

		if(DEBUG)
			echo "<br />" . $this->buildPersonenUrl($saisonStart, $personID);
		$content = file_get_contents($this->buildPersonenUrl($saisonStart, $personID));

		/*
		 * Überprüfen ob dies die gewünschte Saison ist. Gibt es den Spieler
		* nicht in der gewünschten Saison, so gibt click-tt die Aktuelle Saison aus.
		*/
		preg_match("/<h1>Spielsaison&nbsp;(.*)\n/Usi", $content, $result);
		$saisonName = $this->getSaisonName($saisonStart);

		if(trim($result[1]) != $saisonName)
			return array();

		//Bereich der Bilanzen Tabelle
		preg_match("/<h2>Einzel-Spiele(.*)<\/table>/Usi", $content, $result, PREG_OFFSET_CAPTURE);
		if(count($result) <= 0)
			return array();

		$offset = $result[1][1] + 1;
		$content = substr($content, $offset);

		$spiele = array();
		$index = 0;
		//Die Spielklasse
		preg_match("/<td colspan=\"[0-9]+\">[\n\t ]+<h2>(.*)<\/h2>/Usi", $content, $klasse, PREG_OFFSET_CAPTURE);


		$offset = $klasse[1][1] + 1;
		$content = substr($content, $offset);
		//click-TT von ISO-8859-1 nach UTF-8 umwandeln
		//$klasse = iconv("ISO-8859-1", "UTF-8", trim($klasse[1][0]));
		$klasse = trim($klasse[1][0]);
		$klasse = str_replace("(6er)", "", $klasse);
		$klasse = str_replace("(4er)", "", $klasse);
		$klasse = str_replace("&nbsp;", " ", $klasse);
		$klasse = trim($klasse);

		//Das Paarkreuz bzw. welche Nummer gegen welche gespielt hat. z.B. 6-5
		while(preg_match("/<TD nowrap=\"nowrap\" alt=\"[0-9\ a-z=\"\.\-]*>(.*)\-[0-9]<\/TD>/Usi", $content, $paarKreuz, PREG_OFFSET_CAPTURE)) {
				
			/*
			 * Wenn das Paarkreuz schon von der nächsten Spielklasse ist,
			* dann die Spielklasse setzen.
			*/
			preg_match("/<td colspan=\"[0-9]+\">[\n\t ]+<h2>(.*)<\/h2>/Usi", $content, $nextKlasse, PREG_OFFSET_CAPTURE);
				

			$nextKlasseClear = str_replace("(6er)", "", $nextKlasse[1][0]);
			$nextKlasseClear = str_replace("(4er)", "", $nextKlasseClear);
			$nextKlasseClear = str_replace("&nbsp;", " ", $nextKlasseClear);
			$nextKlasseClear = trim($nextKlasseClear);
				
			if(count($nextKlasse) > 0 && $nextKlasse[1][1] < $paarKreuz[1][1] && $klasse != $nextKlasseClear) {
				//click-TT von ISO-8859-1 nach UTF-8 umwandeln
				//$klasse = iconv("ISO-8859-1", "UTF-8", $nextKlasse[1][0]);
				$klasse = $nextKlasse[1][0];
				$klasse = str_replace("(6er)", "", $klasse);
				$klasse = str_replace("(4er)", "", $klasse);
				$klasse = str_replace("&nbsp;", " ", $klasse);
				$klasse = trim($klasse);
			}

			$offset = $paarKreuz[1][1] + 1;
			$content = substr($content, $offset);
				
			//Spalte mit dem Satzverhältnis z.B. 3:1
			preg_match("/<td><b>(.*)<\/b>/Usi", $content, $spiel, PREG_OFFSET_CAPTURE);
				
			/*
			 * Gegner finden.
			*/
			preg_match("/<td nowrap=\"nowrap\">(.*)<\/td>/Usi", $content, $gegner);
			//click-TT von ISO-8859-1 nach UTF-8 umwandeln
			//			$gegner = iconv("ISO-8859-1", "UTF-8", $gegner[1]);
			$gegner = $gegner[1];
			//Leerzeichen entfernen.
			$gegner = str_replace("&nbsp;", " ", $gegner);
			$gegner = trim($gegner);
				
			//Wenn Gegner nicht angetreten, nicht in die Bilanz aufnehmen.
			if(stristr($gegner, "nicht anwesend") !== false)
				continue;
				
			$tmp = explode("(", $klasse);
			$spielKlasse = trim($tmp[0]);
			$runde = strtolower( trim( substr($tmp[1],0,strlen($tmp[1])-1) ) );

			if($runde == "r&uuml;ckrunde")
				$runde = "rueckrunde";
				
			$index = count($spiele[$runde][$spielKlasse]);
			$spiele[$runde][$spielKlasse][$index]['position'] = $paarKreuz[1][0];
			$spiele[$runde][$spielKlasse][$index]['saetze'] = $spiel[1][0];
		}
		$this->cacheDetailBilanz[$saisonStart . $personID] = $spiele;
		return $spiele;
	}

	function getBilanz($saisonStart, $personID) {
		if(DEBUG) {
			echo "<br />getBilanz($saisonStart, $personID)";
			var_dump(array_key_exists($saisonStart . $personID, $this->cacheBilanz));
			var_dump(is_array($this->cacheBilanz[$saisonStart . $personID]));
		}


		//Zwischengespeicherte Werte Zurückgeben
		if(array_key_exists($saisonStart . $personID, $this->cacheBilanz) && is_array($this->cacheBilanz[$saisonStart . $personID]))
			return $this->cacheBilanz[$saisonStart . $personID];

		$bilanz = array();
		$details = $this->getDetailBilanz($saisonStart, $personID);

		/*
		 * Teilt die Bilanzen in Oberes, Mittleres, Unteres Paarkreuz ein
		*/
		foreach($details as $runde=>$data) {
			foreach($data as $klasse=>$detail) {
				foreach($detail as $spiel) {
					$gewonnen = 0;
					$verloren = 0;
					if(substr($spiel['saetze'],0,1) == 3)
						$gewonnen = 1;
					else
						$verloren = 1;
						
					//Verhindert Notice Meldung in PHP
					if(!array_key_exists($runde, $bilanz)) {
						$bilanz[$runde] = array();
					}
					if(!array_key_exists($klasse, $bilanz[$runde])) {
						$bilanz[$runde][$klasse] = array();
					}
						
					if($spiel['position'] <= 2) {
						$paarKreuz = 'oben';

					}
					else if($spiel['position'] <= 4) {
						$paarKreuz = 'mitte';
					}
					else {
						$paarKreuz = 'unten';
					}
						
					//Verhindert Notice Meldung in PHP
					if(!array_key_exists($paarKreuz, $bilanz[$runde][$klasse])) {
						$bilanz[$runde][$klasse][$paarKreuz] = array();
						$bilanz[$runde][$klasse][$paarKreuz]['gewonnen'] = 0;
						$bilanz[$runde][$klasse][$paarKreuz]['verloren'] = 0;
					}
					$bilanz[$runde][$klasse][$paarKreuz]['gewonnen'] += $gewonnen;
					$bilanz[$runde][$klasse][$paarKreuz]['verloren'] += $verloren;
				}
			}
		}
		$this->cacheBilanz[$saisonStart . $personID] = $bilanz;
		return $bilanz;
	}

	function getLeistungsIndex($saisonStart, $vorrunde, $personID) {
		if(DEBUG)
			echo "<br />getLeistungsIndex($saisonStart, $vorrunde, $personID)";
		$details = $this->getBilanz($saisonStart, $personID);

		$grundPunkte = 0;
		$maxCount = 0;
		$countSpiele = array();

		if($vorrunde && array_key_exists("vorrunde", $details))
			$data = $details['vorrunde'];
		else if(!$vorrunde && array_key_exists("rueckrunde", $details))
			$data = $details['rueckrunde'];
		else
			return null;


		foreach($data as $klasse=>$spiele) {

			$klassenpunkte = $this->_getKlassenPunkte($klasse);

			if($klassenpunkte === null)
				continue;
				
			foreach($spiele as $paarKreuz=>$spiel) {
				$paarKreuzPunkte = 10;
				if($paarKreuz == "mitte")
					$paarKreuzPunkte = 20;
				else if($paarKreuz == "oben")
					$paarKreuzPunkte = 30;

				if(!array_key_exists($paarKreuzPunkte+$klassenpunkte, $countSpiele)) {
					$countSpiele[$paarKreuzPunkte+$klassenpunkte]["gewonnen"] = 0;
					$countSpiele[$paarKreuzPunkte+$klassenpunkte]["verloren"] = 0;
				}

				$countSpiele[$paarKreuzPunkte+$klassenpunkte]["gewonnen"] += $spiel["gewonnen"];
				$countSpiele[$paarKreuzPunkte+$klassenpunkte]["verloren"] += $spiel["verloren"];

				if( ($spiel["gewonnen"] + $spiel["verloren"]) > $maxCount ) {
					$maxCount = $spiel["gewonnen"] + $spiel["verloren"];
					$grundPunkte = $paarKreuzPunkte+$klassenpunkte;
				}
			}
		}

		$spielePunkte = 0;
		foreach($countSpiele as $gPunkte=>$spiel) {
			if($gPunkte == $grundPunkte) {
				$spielePunkte += $spiel["gewonnen"] - $spiel["verloren"];
			} else if($gPunkte > $grundPunkte) {
				$spielePunkte += $spiel["gewonnen"] - $spiel["verloren"] * 0.5;
			} else {
				$spielePunkte += $spiel["gewonnen"] * 0.5 - $spiel["verloren"];
			}
		}
		return $grundPunkte + $spielePunkte;
	}


	function _getKlassenPunkte($klasse) {
		if(DEBUG)
			echo "<br />_getKlassenPunkte(\"$klasse\")";

		if(!$klasse)
			return 0;

		//Bugfix 4er Mannschaften
		$klasse = str_replace("4er", "", $klasse);
		//Bugfix für den RV-TTC Fürstengrund (HTTV)
		$klasse = str_replace("Unterzent", "", $klasse);
		$klasse = str_replace("&nbsp;", " ", $klasse);
		$klasse = trim($klasse);


		/*
		 * Wenn Altersklasse und Spielklasse nicht mit einem "-" getrennt werden,
		* wird ab dem ersten leerzeichen getrennt.
		*/
		$klasseExplode = explode("-", $klasse);
		if(count($klasseExplode) == 1) {
			$klasseExplode[0] = substr($klasse, 0, strpos($klasse, " "));
			$klasseExplode[1] = substr($klasse, strpos($klasse, " ")+1);
				
			//Keine Altersklasse vor der Spielklasse angegeben
			if($klasseExplode[0] == "" || strlen($klasseExplode[0]) < 5) {
				$klasseExplode[0] = "Herren";
				$klasseExplode[1] = $klasse;
			}
		}

			
		$altersKlasse = $klasseExplode[0];

		$klasseExplode[1] = trim($klasseExplode[1]);

		//Erstes Leerzeichen finden, welches nicht am Anfang ist.
		$explodePos = strpos(substr($klasseExplode[1], 4), " ");
		if($explodePos > 0) {
			//String bis zum Leerzeichen ist die Klasse
			$spielKlasse = substr($klasseExplode[1],0,$explodePos+4);
		} else {
			$spielKlasse = $klasseExplode[1];
		}
			
		switch($altersKlasse) {
			default:
				return null;
			case "Herren":
				switch($spielKlasse) {
					default:
						return null;
							
					case "1. Bundesliga":
						return 220;
					case "2. Bundesliga":
						return 200;
					case "Regionalliga":
						return 180;
					case "Oberliga":
						return 160;
					case "Verbandsliga":
						return 140;
					case "Landesliga":
						return 120;
					case "Bezirksliga":
						return 100;
					case "Bezirksklasse":
						return 80;
					case "Kreisliga":
						return 60;
					case "1. Kreisklasse":
						return 40;
					case "2. Kreisklasse":
						return 20;
					case "3. Kreisklasse":
						return 0;
							
				}
				break;

		}
		return null;

	}

	/**
	 * @return string Generiert die URL zur Seite "Mannschaften und Ligeneinteilung"
	 */
	function buildTeamProfilUrl() {
		return $this->clickTTUrl . "clubTeams?club=" . $this->clubID;
	}
	//clubInfoDisplay?club=1288

	function buildClubInfoDisplayUrl($clubId) {
		return $this->clickTTUrl . "clubInfoDisplay?club=" . $clubId;
	}
	/**
	 * @param string $seasonName Saison des Spielerportraits z.B. "2008/09"
	 * @param int $personID Der personid ist die eindeutige Nummer die jeder Spieler in click-TT hat
	 * @return string Url zum Portraiseite eines Spielers
	 */
	function buildPersonenUrl($saisonStart, $personID) {
		$seasonName = $this->getSaisonName($saisonStart);
		// "/" umwandeln
		$seasonName = rawurlencode($seasonName);

		return $this->clickTTUrl . "playerPortrait?federation=" . $this->verband->federation . "&season=$seasonName&person=$personID&club=" . $this->clubID;
	}

	function buildAufstellungUrl($saisonStart, $alterklasse="Herren", $hinrunde=true) {
		$seasonName = $this->getSaisonName($saisonStart);
		// "/" umwandeln
		$seasonName = urlencode($seasonName);
		if($hinrunde)
			$runde = "vorrunde";
		else
			$runde = "rueckrunde";

		/*
		 * Umlaute codieren. Vorher muss der String aber in ISO-8859-1 umgewandelt werden,
		* da sonst ein anderer URL code generiert wird.
		*/
		//$alterklasse = iconv("UTF-8", "ISO-8859-1", $alterklasse);
		$alterklasse = urlencode($alterklasse);
		//Bugfix TODO bessere urlencode Methode
		$alterklasse = str_replace("%E4", "ä", $alterklasse);
		$alterklasse = str_replace("%FC", "ü", $alterklasse);
		//$alterklasse = iconv("UTF-8", "ISO-8859-1", $alterklasse);

		if($hinrunde == null)
			return $this->clickTTUrl . "clubPools?seasonName=$seasonName&contestType=$alterklasse&club=" . $this->clubID;

		return $this->clickTTUrl . "clubPools?displayTyp=$runde&seasonName=$seasonName&contestType=$alterklasse&club=" . $this->clubID;
	}

	function buildClubUrl() {
		return $this->clickTTUrl . "clubPools?club=" . $this->clubID;
	}

	function buildClubSearchUrl($search) {
		return $this->clickTTUrl . "clubSearch?federation=" . $this->verband->federation . "&searchFor=" . rawurlencode($search);

	}

	function buildTableUrl($championship, $group) {
		return $this->clickTTUrl . "groupPage?championship=$championship&group=$group";
	}
	function buildTeamUrl($teamTable, $pageState, $championship, $group) {
		return $this->clickTTUrl."teamPortrait?teamtable=$teamTable&pageState=$pageState&championship=$championship&group=$group";
	}

}

?>
