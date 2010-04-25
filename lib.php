<?php 
class BotLogger {
	function write_log($message) {
		echo date('Y/m/d H:i:s') . ': ' . $message . "\n";
	}
}

class BotSQL {
	
	function __do_query($sql) {
		$res = @mysql_query($sql);
		if(mysql_error()) BotLogger::write_log(sprintf('MySQL error executing "%s" --> "%s"', $sql, mysql_error()));
		$GLOBALS['cache']['sqlqueries'] += 1; // merezco la muerte, lo sé
		return $res;
	}
	
	function get_lastid($datasource, $bot) {
		$res = BotSQL::__do_query("SELECT `lastid` FROM `lastids` WHERE `username` = '{$datasource}' AND `bot_uname` = '{$bot}'");
		return (float) @mysql_result($res, 0);
	}
	
	function write_lastid($datasource, $bot, $id) {
		if(mysql_num_rows(BotSQL::__do_query("SELECT 1 FROM `lastids` WHERE `username` = '{$datasource}' AND `bot_uname` = '{$bot}'")) == 0) {
			return (bool) BotSQL::__do_query("INSERT INTO `lastids` (`username`,`lastid`,`bot_uname`) VALUES ('{$datasource}', {$id}, '{$bot}')");
		} else {
			return (bool) BotSQL::__do_query("UPDATE `lastids` SET `lastid` = {$id} WHERE `username` = '{$datasource}' AND `bot_uname` = '{$bot}'");
		}
	}
}

class BotCache {
	function add_user_tl($user, $object) {
		BotCache::add_user($user);
		return (bool) $GLOBALS['cache'][$user]['timeline'] = $object;
	}
	
	function get_user_tl($user) {
		return $GLOBALS['cache'][$user]['timeline'];
	}
	
	function has_user($user) {
		return (bool) $GLOBALS['cache'][$user];
	}
	
	function add_user($user) {
		return (bool) $GLOBALS['cache'][$user]['fetched'] = 1;
	}
}

class BotUtils {
	function post($user, $password, $id, $text) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://twitter.com/statuses/update.json');
		curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'status=' . $text);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		return curl_exec($ch);
	}
	
	function get_timeline($user, $count, $auth = false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://twitter.com/statuses/user_timeline.json?screen_name=' . urlencode($user) . '&count=' . $count);
		if($auth) {
			BotLogger::write_log('using auth for ' . $user);
			curl_setopt($ch, CURLOPT_USERPWD, AUTHUSER . ':' . AUTHPWD);
		} else {
			BotLogger::write_log('not using auth for ' . $user);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
		
		$res = json_decode(curl_exec($ch));
		BotCache::add_user_tl($user, $res);
		if(gettype($res) == 'object') {
			if($auth) return array();
			return BotUtils::get_timeline($user, $count, true);
		} 
		return array_reverse($res);
	}
	
	function last_id($datasource, $bot) {
		return BotSQL::get_lastid($datasource, $bot);
	}
	
	function save_id($datasource, $bot, $id) {
		if(!$id) return;
		return BotSQL::write_lastid($datasource, $bot, $id);
	}
	
	function is_successful($json) {
		return $json;
	}
	
	function italian($text){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q='. urlencode($text) .'&langpair=es%7Cit');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$res = json_decode(curl_exec($ch));
		return $res->responseData->translatedText;
	}
	
}

class Bot {
	function __construct() {
		$tweets = $this->__get_timeline($this->datasource);
		$lastid = BotUtils::last_id($this->datasource, $this->user);
		$this->__tweet_loop($tweets, $lastid);
		
		if(is_callable(array($this, 'postflight'))) {
			$this->_postflight();
		}
		 	
	}
	
	function __get_timeline($datasource) {
		if(BotCache::has_user($this->datasource)) {
			BotLogger::write_log("cached timeline for {$this->datasource} ({$this->user})");
			$tweets = BotCache::get_user_tl($datasource);
		} else {
			BotLogger::write_log("fetching timeline for {$this->datasource} from twitter ({$this->user})");
			$tweets = BotUtils::get_timeline($datasource, TWEET_COUNT);
		}
		return $tweets;
	}

	
	function __tweet_loop($tweets, $lastid) {
		foreach($tweets as $tweet) {
			if($tweet->id > $lastid || $tweet->is_deleted) {
				if($tweet->is_deleted == null) $tweet->is_deleted = false;
				$text = $this->_replace($tweet->text);
				if($text) {
					$this->__post($this->user, $this->password, $tweet->id, $text);
				}
			} else {
				// echo date('H:i:s') . '# ' . $this->user . ': skipping' . "\n";
			}
		}
	}
	
	function __post($user, $password, $id, $text) {
		$res = BotUtils::post($user, $password, $id, $text);
		
		if(BotUtils::is_successful($res)) { 
			BotUtils::save_id($this->datasource, $this->user, $id);
		}
		
		BotLogger::write_log("{$user}: twittd: {$text}");
	}
}

class AppendMachine {
	var $_regex = '/([A-Za-z0-9])/';
	var $_emoticon_regex = '/((:[A-Z])|(xD|XD)|(:3))/';

	function _substr_loop($str = null, $pointer = -1) {
		if(!$str) $str = $this->_str;
		$end = null;
		$letter = false;
		$emoticon = false;
		
		do {
			$this_substr = substr($str, $pointer, 1);
			if(preg_match($this->_regex, $this_substr)) {
				// detectada letra, comprobar si es un emoticono y salir del bucle
				$letter = true;
				
				if(preg_match($this->_emoticon_regex, substr($str, -2))) {
					// detectado emoticono compuesto por caracteres ^[A-Za-z0-9] 
					// to-do: hacer algo con el regex
					
					$pointer = -4; // no questions.
					$end = array(substr($str, -1, 1), substr($str, -2, 1)); 
					$emoticon = true;
				}
			} else {
				// detectado caracter ^[A-Za-z0-9]
				$end[] = $this_substr;
				$pointer -= 1;
			}
		} while(!$letter);
		$pointer += 1;
		
		return array('end' => $end, 'pointer' => $pointer, 'emoticon' => $emoticon);
	}
	
	function append($tweet, $what) {		
		$answer = $this->_substr_loop($tweet);
			
		$end = substr($tweet, $answer['pointer']);
		if(preg_match($this->_regex, $end) && !$answer['emoticon']) $end = null;
		$without_end = substr($tweet, 0, strlen($tweet) + $answer['pointer']); // 140 + (-x) = 140 - x = 140 - abs(x)
		
		if($answer['emoticon'] && !preg_match($this->_regex, substr($without_end, -1))) { // cosas como "Ojete calor. xD"
			$answer = $this->_substr_loop($without_end);
			$this_end = substr($without_end, $answer['pointer']);
			
			$end = $this_end . $end; // recalcular end
			$without_end = substr($without_end, 0, strlen($without_end) + $answer['pointer']); // recalcular without_end
		}
			
		$final_str = $without_end . $what . $end;						
		return $final_str;
	}
}