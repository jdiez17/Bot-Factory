<?php

include('config.php');
include('lib.php');
include('bots.php');

BotLogger::write_log(' --- starting --- ');

$bot = new Ebcari();
$bot = new NavarroCabreado();
$bot = new FailBN();
$bot = new EgoBN();
$bot = new EbcariSevikunTeQuiero();

BotLogger::write_log(" --- goodbye! ({$GLOBALS['cache']['sqlqueries']} queries) --- ");