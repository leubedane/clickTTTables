<?php
class Verband {
	
	function getVerband($name) {
		$verbaende = Verband::getVerbaende();
		foreach($verbaende as $verband) {
			if($verband->name == $name)
				return $verband;
		}
		return null;
	}
	
	function getVerbaende() {
		$verbaende = array();
		$i = 0;
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "WTTV Westdeutschland";
		$verbaende[$i]->federation = "WTTV";
		$verbaende[$i]->domain = "http://wttv.click-tt.de";
		$verbaende[$i++]->url = "http://wttv.click-tt.de/cgi-bin/WebObjects/ClickWTTV.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVBW Württemberg-Hohenzollern";
		$verbaende[$i]->federation = "TTVWH";
		$verbaende[$i]->domain = "http://ttvwh.click-tt.de";
		$verbaende[$i++]->url = "http://ttvwh.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVBW Südbaden";
		$verbaende[$i]->federation = "SbTTV";
		$verbaende[$i]->domain = "http://ttvbw.click-tt.de";
		$verbaende[$i++]->url = "http://ttvbw.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVBW Baden";
		$verbaende[$i]->federation = "BaTTV";
		$verbaende[$i]->domain = "http://ttvbw.click-tt.de";
		$verbaende[$i++]->url = "http://ttvbw.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "FTTB Bremen";
		$verbaende[$i]->federation = "FTTB";
		$verbaende[$i]->domain = "http://fttb.click-tt.de";
		$verbaende[$i++]->url = "http://fttb.click-tt.de/cgi-bin/WebObjects/ClickNTTV.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "HTTV Hessen";
		$verbaende[$i]->federation = "HeTTV";
		$verbaende[$i]->domain = "http://httv.click-tt.de";
		$verbaende[$i++]->url = "http://httv.click-tt.de/cgi-bin/WebObjects/ClickSWTTV.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVN Niedersachsen";
		$verbaende[$i]->federation = "TTVN";
		$verbaende[$i]->domain = "http://ttvn.click-tt.de";
		$verbaende[$i++]->url = "http://ttvn.click-tt.de/cgi-bin/WebObjects/ClickNTTV.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVR Rheinland";
		$verbaende[$i]->federation = "TTVR";
		$verbaende[$i]->domain = "http://ttvr.click-tt.de";
		$verbaende[$i++]->url = "http://ttvr.click-tt.de/cgi-bin/WebObjects/ClickSWTTV.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "TTVSA Sachsen-Anhalt";
		$verbaende[$i]->federation = "TTVSA";
		$verbaende[$i]->domain = "http://ttvsa.click-tt.de";
		$verbaende[$i++]->url = "http://ttvsa.click-tt.de/cgi-bin/WebObjects/ClickNTTV.woa/wa/";
		
		$verbaende[$i] = new stdClass();
		$verbaende[$i]->name = "BTTV Bayern";
		$verbaende[$i]->federation = "ByTTV";
		$verbaende[$i]->domain = "http://bttv.click-tt.de";
		$verbaende[$i++]->url = "http://bttv.click-tt.de/cgi-bin/WebObjects/ClickBTTV.woa/wa/";
	
		return $verbaende;
	}
	
}
?>
