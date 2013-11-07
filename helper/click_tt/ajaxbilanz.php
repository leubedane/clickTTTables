<?php
require ('clicktt.php');
$verband = $_GET['verband'];
$vereinsnummer = $_GET['vereinsnummer'];
$clubname = $_GET['clubname'];
$clubid = $_GET['clubid'];
$spielerid = $_GET['spielerid'];
$calc_leistungsindex = $_GET['calc_leistungsindex'] == "1" ? true : false  ;

$clicktt= new ClickTT($verband,$vereinsnummer, $clubname,$clubid);
$saisons = $clicktt->getSaisonStarts();

$bilanzen = array();

foreach($saisons as $saison) {
	$bilanz = $clicktt->getBilanz($saison, $spielerid);
		
	if(count($bilanz) > 0) {
		$bilanzen[$saison] = $bilanz;
		if($calc_leistungsindex) {
			$leistungsIndex[$saison] = array();
			$leistungsIndex[$saison]['vorrunde'] = $clicktt->getLeistungsIndex($saison, true, $spielerid);
			$leistungsIndex[$saison]['rueckrunde'] = $clicktt->getLeistungsIndex($saison, false, $spielerid);
		}
	}
			
}
$tab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
if(count($bilanzen) > 0) {
	echo "<h2>Bilanzen aus click-TT</h2>\n";
	echo "\n<table>";
	
	$oldSaison = "";

	foreach($bilanzen as $saison=>$bilanz) {
		
		echo "\n\t<tr><td>";
		echo "<b>Saison ". buildSaisonString($saison). "</b>";
		echo ' <a href="' . htmlspecialchars($clicktt->buildPersonenUrl($saison,$spielerid)) . '" target="_blank">mehr</a>';
		echo "</td><td>Oben</td><td>Mitte</td><td>Unten</td>";
		echo "</tr>";
		
		ksort($bilanz);
		foreach($bilanz as $runde=>$data) {
			
			if($runde == "vorrunde")
				$rundeAnzeige = "Vorrunde";
			else if($runde == "rueckrunde")
				$rundeAnzeige = "Rückrunde";
			
			$ausgabeIndex = "&nbsp;";
			if($leistungsIndex) {
				if($leistungsIndex[$saison][$runde] != null && $leistungsIndex[$saison][$runde] > 0) {
					$ausgabeIndex = " (Leistungsindex: " . $leistungsIndex[$saison][$runde] . ")";
				}
			}		
			echo "<tr><td colspan=\"4\"><b>$rundeAnzeige</b>$ausgabeIndex</td></tr>";
			
			
			foreach($data as $klasse=>$row) {
				echo "\n\t<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;$klasse</td>";
				
				//TODO align über CSS setzen
				if(array_key_exists('oben', $row))
					echo "<td align=\"center\">" . $row['oben']['gewonnen'] . ":" . $row['oben']['verloren'] . "</td>";
				else
					echo "<td align=\"center\">-</td>";
				if(array_key_exists('mitte', $row))
					echo "<td align=\"center\">" . $row['mitte']['gewonnen'] . ":" . $row['mitte']['verloren'] . "</td>";
				else
					echo "<td align=\"center\">-</td>";
				if(array_key_exists('unten', $row))
					echo "<td align=\"center\">" . $row['unten']['gewonnen'] . ":" . $row['unten']['verloren'] . "</td>";
				else
					echo "<td align=\"center\">-</td>";
				echo "</tr>";
			}
		}
		$oldSaison = $saison;
		
		echo "\n<tr><td colspan=\"4\">&nbsp;</td></tr>";		
	}
	echo "\n</table>\n";
}
function buildSaisonString ($saison){
	return $saison .'/'. substr(($saison+1),2,2);
}
?>
