<?php
// The general information at the top of each file
/**
* @version    1.0 Release for Joomla 2.5
* @package    Joomla
* @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
* @license    GNU General Public License, see LICENSE.php
*/
 
// No direct access allowed to this file
defined( '_JEXEC' ) or die;
 
// Import der JPlugin Klasse
jimport( 'joomla.plugin.plugin' );
// import panes
jimport('joomla.html.pane');

$lib = dirname(__FILE__) . '/helper/click_tt/clicktt.php';
include $lib;

//The Content plugin ttverein for showing tables and gameplan
class plgContenttt_verein  extends JPlugin
{
	public $DEBUG = false;
	protected $verband;
	protected $vereinsNr;
	protected $vereinsName;

	function __construct( &$subject, $config )
	{
		parent::__construct( $subject, $config );
	}     
	
	// onPrepareContent, meaning the plugin is rendered at the first stage in preparing content for output
	public function onContentPrepare($context, &$article, &$params, $limitstart=0 ) {
		
		//start with the standard regex
		$table_regex = "/{ttverein_table.*}/";
		$positions_regex="/{ttverein_formation.*}/";
		$timeTable_regex="/{ttverein_timeTable.*}/";
		$nextMatch_regex="/{ttverein_matchNext.*}/";
		
		preg_match_all( $table_regex, $article->text, $table_matches);
		preg_match_all($positions_regex, $article->text, $position_matches);
		preg_match_all($timeTable_regex, $article->text, $timeTable_matches);
		preg_match_all($nextMatch_regex, $article->text, $position_nextMatches);
		
		
		// no result so nothing to do
		if ( !count( $table_matches[0] ) && !count($position_matches[0]) && !count($timeTable_matches[0]) ) {
			return true;
		}
		
		$this->verband = utf8_decode($this->params->def('Verband'));
		$this->vereinsNr = utf8_decode($this->params->def('VereinsNummer'));
		$this->vereinsName = utf8_decode($this->params->def('VereinsName'));
		
		$clickTT = new ClickTT($this->verband, $this->vereinsNr, $this->vereinsName);
		//case table
		if($this->DEBUG) {
			echo "TabellenMatches: ".count($table_matches[0]);
			echo " SpielplanMatches: ".count($timeTable_matches[0]);
			echo " AufstellungMatches: ".count($position_matches[0]);
			echo "<br/>";
			echo "<br/>";
		}
		if(count($table_matches[0])) {
			//get params
			$championship = $this->getParameter("champ", $table_matches[0][0]);
			if(empty($championship)){
				$championship = $this->params->get('champ');
			}
			$group = $this->getParameter('group', $table_matches[0][0]);
			$table = $clickTT->getTeamTable($championship, $group);
			$table =str_replace('<table>', '<table class="table table-bordered">', $table);
			$article->text = preg_replace($table_regex, $table, $article->text, 1);
		}
		//case positions 
		if(count($position_matches[0])) {
			//get params
			$championship = $this->getParameter("champ", $position_matches[0][0]);
			$group = $this->getParameter('group', $position_matches[0][0]);
			$teamTable = $this->getParameter('id', $position_matches[0][0]);
			//$pageState = $this->getParameter('runde', $position_matches[0][0]);
			$pageState = $this->params->get('Runde');
			if(empty($championship)){
				$championship = $this->params->get('champ');
			}
			//get formation
			$table = $clickTT->getFormation($teamTable, $pageState, $championship, $group);
			$table =str_replace('<table class="result-set" cellspacing="0" border="0" cellpadding="0">', '<table class="table table-bordered">', $table);
			//show formation
			$article->text = preg_replace($positions_regex, $table, $article->text, 1);
		}
		//case gameplan
		if(count($timeTable_matches[0])) {
			//get params
			$championship = $this->getParameter("champ", $timeTable_matches[0][0]);
			$group = $this->getParameter('group', $timeTable_matches[0][0]);
			$teamTable = $this->getParameter('id', $timeTable_matches[0][0]);
			//$pageState = $this->getParameter('runde', $timeTable_matches[0][0]);
			$pageState = $this->params->get('Runde');
			if(empty($championship)){
				$championship = $this->params->get('champ');
			}
			//get gameplan
			$table = $clickTT->getTimeTable($teamTable, $pageState, $championship, $group);
			$table =str_replace('<table class="result-set"', '<table class="table table-bordered"', $table);
			//show gameplan
			$article->text = preg_replace($timeTable_regex, $table, $article->text, 1);
		}

		//case gameplan
		if(count($position_nextMatches[0])) {
			
			//get params
			$club = $this->getParameter("club", $position_nextMatches[0][0]);
			$table = $clickTT->getNextMatches($club);
			//get gameplan
			$table =str_replace('<table class="result-set"', '<table class="table table-bordered"', $table);
			//show gameplan
			$article->text = preg_replace($nextMatch_regex, $table, $article->text, 1);
		}
	}
	
	//this function gets the parameter for the given property in first argument in the given word (second parameter)
	public function getParameter($parameterName, $word) {
		$regex = "/".$parameterName.'=".*"'."/U";
		preg_match($regex, $word, $param);
		preg_match('/".*?"/', $param[0], $result);
		return str_replace('"', "",$result[0]);
	}
	
	
}


