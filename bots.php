<?php
class Ebcari extends Bot {
	public $user = 'ebcari';
	public $password = 'JAJAJAsoyEBCUBE';
	public $datasource = 'ebcube';
	
	var $_append = ', cari';

	function _replace($tweet) {
		$machine = new AppendMachine();
		
		if(stristr($tweet, '@')) return;
		return $machine->append($tweet, $this->_append);
	}

}

class EbcariSevikunTeQuiero extends Bot {
	public $user = 'ebcari';
	public $password = 'JAJAJAsoyEBCUBE';
	public $datasource = 'sevikun';
	
	var $_find = array('te quiero', 'os quiero');
	var $_answer = 'Y yo a ti, cari :*';

	function _replace($tweet) {
		foreach($this->_find as $pastelosidad) {
			if(stristr($tweet, $pastelosidad)) {
				return '@' . $this->datasource . ' ' . $this->_answer;
			}
		}
	}
}

class NavarroCabreado extends Bot {
	public $user = 'navarrocabreado';
	public $password = 'JAJAJAsoyADGI';
	public $datasource = 'adrinavarro';
	
	var $_append = ', coño';
	var $_chorradas = array('COÑO', 'VERGA', 'HOSTIA', 'BAGGIO', 'MARCRE');
	var $_period = 5; // cada 5 palabras, cambiar por una chorrada

	function _replace($tweet) {	
		$machine = new AppendMachine();
		if(stristr($tweet, '@')) return;
		
		$words = explode(' ', $tweet);
		for($i = 0; $i < count($words); $i++) {
			if($i % $this->_period == 0 && $i != 0) {
				$words[$i] = $this->_chorradas[rand(0, count($this->_chorradas)-1)];
			}
		}
		$tweet = implode(' ', $words);
		
		return $machine->append($tweet, $this->_append);
	}

}

class FailBN extends Bot {
	public $user = 'failbn';
	public $password = 'JAJAJAsoyFLAN';
	public $datasource = 'franbn';

	function _replace($tweet, $deleted = false) {
		if(!stristr($tweet, '@') && !$deleted) return;
		
		preg_match_all('/@([A-Za-z0-9]+)/is', $tweet, $matches);
		foreach($matches[1] as $nick) {
			$tweet = str_replace($nick, strrev($nick), $tweet);
		}
		
		if(substr($tweet, 0, 1) == '@') {
			$tweet = '.' . $tweet;
		}

		return $tweet;
	}

}

class EgoBN extends Bot {
	public $user = 'egobn';
	public $password = 'JAJAJAsoyFLAN';
	public $datasource = 'franbn';
	
	var $_append = ', porque molo';

	function _replace($tweet) {
		$machine = new AppendMachine();
		
		if(stristr($tweet, '@')) return;
		return $machine->append($tweet, $this->_append);
	}

}
