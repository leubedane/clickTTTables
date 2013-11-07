<?php
require ('clicktt.php');
$verband = $_GET['verband'];
$vereinsnummer = $_GET['vereinsnummer'];
$clubname = $_GET['clubname'];
$clubid = $_GET['clubid'];
$championship = $_GET['championship'];
$group = $_GET['group'];
$imageUrl = $_GET['imageurl'];

if($verband && $vereinsnummer && $clubname && $clubid) {
	$clicktt = new ClickTT($verband, $vereinsnummer, $clubname, $clubid);
	$clicktt->setImageUrl("http://" . $imageUrl);
	if($clicktt->clubID > 0) {
		echo "<h3>Tabelle</h3>";
		echo $clicktt->getTeamTable( $championship, $group);
	}
}

?>
