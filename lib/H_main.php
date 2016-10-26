<?php

/*** INTERNATIONALIZATION (I18N) ***/

function i18n($key) {
	global $__txt;
	$t = $__txt[$key];
	if(!empty($t) && func_num_args()>1) {
		$code = '$t = sprintf($t';
		for($i=1; $i<func_num_args(); $i++)
			$code .= ',"'.func_get_arg($i).'"';
		$code .= ');';
		eval($code);
	}
	return $t;
}



/*** FORM'S FUNCTIONS ***/

function form_select($name, $options, $can_be_empty=true, $selected_value='', $props='') {
	$out = '<select id="'.$name.'" name="'.$name.'" '.$props.'>';
	if($can_be_empty)
		$out .= '<option value="">'.i18n('form.select').'</option>';
	foreach($options as $k=>$v)
		$out .= '<option value="'.$k.'" '.(($k==$selected_value)?'selected':'').'>'.$v.'</option>';
	$out .= '</select>';
	return $out;
}



/*** USER'S FUNCTIONS ***/

function user_type($user_types, $type) {
	if($type<0)
		$type = -$type;
	foreach($user_types as $k=>$v) {
		if($v==$type)
			return $k;
	}
}



/*** NEWS' FUNCTIONS ***/

function news_parse($n) {
	$n['title'] = nl2br(htmlentities(utf8_decode($n['title'])));
	$n['text'] = nl2br(htmlentities(utf8_decode($n['text'])));
	$n['text'] = preg_replace("/\[b\](.*)\[\/b\]/Usi", "<b>\\1</b>", $n['text']);
	$n['text'] = preg_replace("/\[u\](.*)\[\/u\]/Usi", "<u>\\1</u>", $n['text']);
	$n['text'] = preg_replace("/\[i\](.*)\[\/i\]/Usi", "<i>\\1</i>", $n['text']);
	$n['text'] = preg_replace("/\[url\](.*)\[\/url\]/i","<a href=\"http://\\1\" target=\"_blank\">\\1</a>",$n['text']);
	return $n;
}



/*** PLAYER'S PROPERTIES FUNCTIONS ***/

function player_skill($skill, $type='') {
	$sufix = '';
	if($type=='short')
		$sufix = '.short';
	return ucwords(i18n("player.level.$skill$sufix"));
}

function player_injury($injury, $type='') {
	$return = '&nbsp;';
	if($injury==-1)
		$return = '-';
	elseif($injury==0)
		$return = '<img src="./img/bruised.gif" alt="'.i18n('player.injury.bruised').'"/>';
	elseif($injury>0)
		$return = '<img src="./img/injured.gif" alt="'.i18n('player.injury.injured',$injury).'"/>'.$injury;
	return $return;
}
function player_specialty($specialty, $type='') {
	$key = '';
	$sufix = '';
	switch($specialty) {
		case 1: $key = 'technical'; break;
		case 2: $key = 'quick'; break;
		case 3: $key = 'powerful'; break;
		case 4: $key = 'unpredictable'; break;
		case 5: $key = 'head'; break;
	}
	if($type=='short')
		$sufix = '.short';
	return (empty($key)) ? '&nbsp;' : i18n("player.specialty.$key$sufix");
}
function player_trainer_type($t_type,$type = '') {
	$key = '';
	$sufix = '';
	if(isset($t_type)) {
		switch($t_type) {
			case 0: $key = 'defensive'; break;
			case 1: $key = 'offensive'; break;
			case 2: $key = 'both'; break;
		}
	}
	if($type=='short')
		$sufix = '.short';
	return (empty($key)) ? '&nbsp;' : i18n("player.trainerType.$key$sufix");
}



/*** TEAM FUNCTIONS ***/

function training_type($type) {
	$key = '';
	switch($type) {
		case  0: $key = 'general'; break;
		case  1: $key = 'stamina'; break;
		case  2: $key = 'setPieces'; break;
		case  3: $key = 'defending'; break;
		case  4: $key = 'scoring'; break;
		case  5: $key = 'winger'; break;
		case  6: $key = 'shooting'; break;
		case  7: $key = 'shortPasses'; break;
		case  8: $key = 'playmaking'; break;
		case  9: $key = 'goaltending'; break;
		case 10: $key = 'throughPasses'; break;
		case 11: $key = 'defensivePositions'; break;
		case 12: $key = 'wingAttacks'; break;
	}
	return (empty($key)) ? '&nbsp;' : i18n("team.trainingType.$key");
}

?>
