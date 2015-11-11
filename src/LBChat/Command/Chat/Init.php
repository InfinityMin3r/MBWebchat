<?php
namespace LBChat\Command\Chat;

use LBChat\ChatClient;
use LBChat\ChatServer;
use LBChat\Command\ChatCommandFactory;
use LBChat\Command\CommandFactory;

function _addCommands($array) {
	foreach ($array as $class) {
		if (is_array($class)) {
			_addCommand($class[0], $class[1], $class[2], $class[3]);
		} else {
			_addCommand($class);
		}
	}
}

function _addCommand($name, $class = null, $requiredAccess = 0, $caseSensitive = false) {
	if ($class === null)
		$class = "LBChat\\Command\\Chat\\{$name}Command";

	ChatCommandFactory::addCommandType($name, function(ChatServer $server, ChatClient $client, $rest, $msg) use ($class) {
		return call_user_func(array($class, "init"), $server, $client, $rest, $msg);
	}, $requiredAccess, $caseSensitive);
}

//Add all the commands
_addCommands(
	array(
		//    command     class name (with path)
		array("!PQ",      "LBChat\\Command\\Chat\\PQCommand",      0, true ),
		array("PQ WHERe", "LBChat\\Command\\Chat\\PQCommand",      0, true ),
		array("/whisper", "LBChat\\Command\\Chat\\WhisperCommand", 0, false),
		array("/send",    "LBChat\\Command\\Chat\\SendCommand",    0, false),
		array("/mute",    "LBChat\\Command\\Chat\\MuteCommand",    1, false),
		array("/muteall", "LBChat\\Command\\Chat\\MuteAllCommand", 1, false),
		array("/unmute",  "LBChat\\Command\\Chat\\UnmuteCommand",  1, false),
		array("/kick",     "LBChat\\Command\\Chat\\KickCommand", 1, false)
		array("/stop",    "LBChat\\Command\\Chat\\StopCommand",    2, false),
		array("/ping",    "LBChat\\Command\\Chat\\PingCommand",    0, false),


		//Keep this line at the bottom to catch all invalid commands. No /command will work if it comes after this line.
		array("/",        "LBChat\\Command\\Chat\\InvalidCommand", 0, false)
	)
);
