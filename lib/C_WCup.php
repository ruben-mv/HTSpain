<?php
class C_WCup
{
	private $u20_calendar;
	private $nt_calendar;
	private $format;
	
	public function __construct($dateFormat = null)
	{
		if($dateFormat != null)
			$this->format = $dateFormat;
		else
			$this->format = 'd-m-Y';
		$this->u20_calendar = $this->generateCalendar(mktime(20,0,0,10,24,2008),8);
		//$this->nt_calendar = generateCalendar(mktime(20,0,0,7,4,2008),12);
	}
	
	private function generateCalendar($ini_date,$ini_seasson){
		$calendar = array();
		
		$today = mktime(12, 0, 0, date("m"), date("d"), date("Y"));
		
		$diff_s = floor((($today+ 19*60*60*24)-$ini_date)/(60 * 60 * 24)/112);
		//echo $diff_s;

		if ($diff_s % 2 == 0){
			$round = 'Q'; //Clasificación
			$ini_date += $diff_s * (60 * 60 * 24) * 112;
			$diff_s += $ini_seasson + 2;
		}
		else{
			$round = 'R'; //Fase final
			$ini_date += 13 * (60 * 60 * 24) * 7;
			$ini_date += ($diff_s - 1) * (60 * 60 * 24) * 112;
			$diff_s += $ini_seasson + 1;
		}

		$phases = 0;
		while(1){
			if ($round == 'Q'){
				$calendar[$diff_s]['C-1']["date"] = date($this->format,$ini_date);
				$calendar[$diff_s]['C-1']["age"] = $this->calcAge($ini_date);
				for($i = 2; $i <= 14; $i += 1){
					$ini_date += (60 * 60 * 24 * 7);
					$calendar[$diff_s]["C-$i"]["date"] = date($this->format,$ini_date);
					$calendar[$diff_s]["C-$i"]["age"] = $this->calcAge($ini_date);
				}
				$phases += 1;
				if($phases == 6) break;
				$round = 'R';
			}

			if($round == 'R'){
				$ini_date += (60 * 60 * 24 * 7 * 8);
				$calendar[$diff_s]['R-2-1']["date"] = date($this->format,$ini_date);
				$calendar[$diff_s]['R-2-1']["age"] = $this->calcAge($ini_date);
				for($i = 2; $i <= 3; $i += 1){
					if ($i % 2 == 0)
						$ini_date += (60 * 60 * 24 * 3);
					else
						$ini_date += (60 * 60 * 24 * 4);
					$calendar[$diff_s]["R-2-$i"]["date"] = date($this->format,$ini_date);
					$calendar[$diff_s]["R-2-$i"]["age"] = $this->calcAge($ini_date);
				}

				$ini_date += (60 * 60 * 24 * 7 * 4);
				$calendar[$diff_s]["R-3-1"]["date"] = date($this->format,$ini_date);
				$calendar[$diff_s]["R-3-1"]["age"] = $this->calcAge($ini_date);
				for($i = 2; $i <= 3; $i += 1){
					if ($i % 2 == 0)
						$ini_date += (60 * 60 * 24 * 3);
					else
						$ini_date += (60 * 60 * 24 * 4);
					$calendar[$diff_s]["R-3-$i"]["date"] = date($this->format,$ini_date);
					$calendar[$diff_s]["R-3-$i"]["age"] = $this->calcAge($ini_date);
				}
			
				for($i = 1; $i <= 3; $i += 1){
					if ($i % 2 == 1)
						$ini_date += (60 * 60 * 24 * 3);
					else
						$ini_date += (60 * 60 * 24 * 4);
					$calendar[$diff_s]["R-4-$i"]["date"] = date($this->format,$ini_date);
					$calendar[$diff_s]["R-4-$i"]["age"] = $this->calcAge($ini_date);
				}
				
				//Semifinal
				$ini_date += (60 * 60 * 24 * 4);
				$calendar[$diff_s]["S"]["date"] = date($this->format,$ini_date);
				$calendar[$diff_s]["S"]["age"]= $this->calcAge($ini_date);
				
				//Final
				$ini_date += (60 * 60 * 24 * 2);
				$calendar[$diff_s]["F"]["date"] = date($this->format,$ini_date);
				$calendar[$diff_s]["F"]["age"] = $this->calcAge($ini_date);
			
				$phases += 1;
				if($phases == 6) break;
				$round = 'Q';
				$ini_date += (60 * 60 * 24) * 19;
			}
			$diff_s += 1;
		}
		return $calendar;
	}
	
	public function getU20_Calendar(){
		//echo '<pre>';
		//print_r($this->u20_calendar);
		//echo '</pre>';
		return $this->u20_calendar;
	}
	
	private function calcAge($date){
		$date = $date - mktime(12, 0, 0, date("m"), date("d"), date("Y"));
		$date = floor($date / (60 * 60 * 24));
		$days = (20 * 112 + 111) - $date;
		$age = array();
		$age['years'] = ($days - ($days % 112))/112;
		$age['days'] = ($days % 112);
		return $age;
	}
}
