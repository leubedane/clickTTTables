<?php
class Verband {
	
	function getVerband($id) {
		$verbaende = Verband::getVerbaende();
		foreach($verbaende as $verband) {
			if($verband->id == $id){
				return $verband;
			}
				
		}
		return null;
	}
	
	function getVerbaende() {
		$verbaende = array();
		$i = 0;
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVBW Südbaden";
		$verbaende[$i]->id = 1;
		$verbaende[$i]->federation = "SbTTV";
		$verbaende[$i]->domain = "http://ttbw.click-tt.de";
		$verbaende[$i++]->url = "https://ttbw.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVBW Baden";
		$verbaende[$i]->federation = "BaTTV";
		$verbaende[$i]->id = 2;
		$verbaende[$i]->domain = "http://ttvbw.click-tt.de";
		$verbaende[$i++]->url = "http://ttvbw.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/";
		
		return $verbaende;
	}
	
}
?>
