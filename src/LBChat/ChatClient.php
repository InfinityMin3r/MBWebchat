<?php
namespace LBChat;
use LBChat\Command\Chat\WhisperCommand;
use LBChat\Command\Server\IdentifyCommand;
use LBChat\Command\Server\InvalidCommand;
use LBChat\Command\Server\NotifyCommand;
use LBChat\Misc\ServerChatClient;
use Ratchet\ConnectionInterface;

/**
 * A basic chat client class that outlines how clients behave.
 * Class ChatClient
 * @package LBChat
 */
class ChatClient {
	protected $server;
	protected $connection;
	private $username;
	private $display;
	private $location;
	private $access;
	private $color;
	private $titles;
	private $muted;
	private $muteTime;
	private $visible;
	private $shadowbanned;
	protected $loggedIn;

	public function __construct(ChatServer $server, ConnectionInterface $connection) {
		$this->server = $server;
		$this->connection = $connection;
		$this->username = "";
		$this->display = "";
		$this->location = 0;
		$this->access = 0;
		$this->color = "000000";
		$this->titles = array("", "", "");
		$this->muted = false;
		$this->muteTime = 0;
		$this->visible = true;
		$this->loggedIn = false;
	}

	/**
	 * Interpret a raw message from the client connection.
	 * @param string $msg The message
	 */
	public function interpretMessage($msg) {
		$command = Command\CommandFactory::construct($this->server, $this, $msg);

		if ($command === null) {
			$command = new InvalidCommand($this->server);
			$command->execute($this);
		} else {
			$command->execute();
		}
	}

	/**
	 * Send a raw message out to the client connection
	 * @param string $msg The message
	 */
	public function send($msg) {
		$this->connection->send($msg);
	}

	/**
	 * Disconnect the client from the server, closing all connections
	 */
	public function disconnect() {
		$this->connection->close();
	}

	/**
	 * Callback for when the client has successfully logged in
	 */
	public function onLogin() {
		$this->loggedIn = true;

		$this->server->sendAllUserlists();
		$this->server->broadcastCommand(new NotifyCommand($this->server, $this, "login", -1, $this->location), $this);
	}

	/**
	 * Callback for when the client logs out, before disconnecting
	 */
	public function onLogout() {
		if ($this->loggedIn) {
			$this->server->sendAllUserlists();
			$this->server->broadcastCommand(new NotifyCommand($this->server, $this, "logout", - 1, $this->location), $this);
		}
	}

	/**
	 * Compare one client to another
	 * @param ChatClient $other The other client
	 * @return bool
	 */
	public function compare(ChatClient $other) {
		return $other->connection === $this->connection;
	}

	/**
	 * Get the client's user Id
	 * @return int
	 */
	public function getId() {
		//No database access in this class, just use the resourceId on the connection
		return $this->connection->resourceId;
	}

	/**
	 * Get the client's username
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Set the client's username
	 * @param string $username The new username
	 */
	public function setUsername($username) {
		$this->username = $username;
		$this->display = $username;
	}

	/**
	 * Get the client's display name.
	 * @return string
	 */
	public function getDisplayName() {
		return $this->display;
	}

	/**
	 * Set the client's display name
	 * @param string $display The new display name
	 */
	public function setDisplayName($display) {
		$this->display = $display;
	}

	/**
	 * Get the client's Location.
	 * @return int
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Set the client's location
	 * @param int $location The new location
	 */
	public function setLocation($location) {
		$this->location = $location;
		$this->server->sendAllUserlists();
	}

	/**
	 * Get the client's access.
	 * @return int
	 */
	public function getAccess() {
		return $this->access;
	}

	/**
	 * Set the client's Access
	 * @param int $access The new access
	 */
	public function setAccess($access) {
		$this->access = $access;
	}

	/**
	 * Get the client's privilege level (different from access as Guests are 0 instead of 3)
	 * @return int
	 */
	public function getPrivilege() {
		switch ($this->getAccess()) {
		case -3: return 0;
		case 3:  return 0;
		default: return $this->getAccess();
		}
	}

	/**
	 * Get the client's color
	 * @return string
	 */
	public function getColor() {
		return $this->color;
	}

	/**
	 * Set the client's color
	 * @param string $color The client's color
	 */
	public function setColor($color) {
		$this->color = $color;
	}

	/**
	 * Get the client's titles
	 * @return array
	 */
	public function getTitles() {
		return $this->titles;
	}

	/**
	 * Set the client's titles
	 * @param array $titles The client's titles
	 */
	public function setTitles($titles) {
		$this->titles = $titles;
	}

	public function setTitle($index, $title) {
		$this->titles[$index] = $title;
	}

	/**
	 * Get if the client should be displayed on user lists and accessible via commands.
	 * @return bool
	 */
	public function getVisible() {
		return $this->visible;
	}

	/**
	 * Set whether the client should be displayed on user lists and accessible via commands.
	 * @param bool $visible
	 */
	public function setVisible($visible) {
		$this->visible = $visible;
	}

	/**
	 * Get if the client is logged in
	 * @return bool
	 */
	public function getLoggedIn() {
		return $this->loggedIn;
	}

	/**
	 * Set if the client is logged in
	 * @param bool $loggedIn If the client should be logged in
	 */
	public function setLoggedIn($loggedIn) {
		$this->loggedIn = $loggedIn;
	}

	/**
	 * Perform a login on the client.
	 * @param string $type The type of login, either "key" or "password"
	 * @param string $data The key/password to use for verification, depending on what is used in $type.
	 */
	public function login($type, $data) {
		if ($this->tryLogin($type, $data)) {
			//Login succeeded
			$this->onLogin();

			$command = new IdentifyCommand($this->server, IdentifyCommand::TYPE_SUCCESS);
			$command->execute($this);
		} else {
			//Login failed
			$command = new IdentifyCommand($this->server, IdentifyCommand::TYPE_INVAILD);
			$command->execute($this);
			$this->disconnect();
		}
	}

	/**
	 * Attempt to verify login details
	 * @param string $type The type of login, either "key" or "password"
	 * @param string $data The key/password to use for verification, depending on what is used in $type.
	 * @return bool If the login was successful
	 */
	public function tryLogin($type, $data) {
		//Base users have no login conditions
		return true;
	}

	/**
	 * Called once every second.
	 */
	public function onSecondAdvance() {
		if ($this->muted) {
			$this->muteTime--;
			if ($this->muteTime <= 0) {
				$this->cancelMute();
			}
		}
	}

	/**
	 * Get if the client is muted
	 * @return bool
	 */
	public function isMuted() {
		return $this->muted;
	}

	/**
	 * Get for how long the client is muted
	 * @return int
	 */
	public function getMuteTime() {
		return $this->muteTime;
	}

	/**
	 * Mute the client for a specified amount of time, adding to their current mute time.
	 * @param int $time The time (in seconds) to add
	 */
	public function addMuteTime($time) {
		$this->muteTime += $time;
		$this->muted = true;
	}

	/**
	 * Completely cancel/terminate a client's mute
	 */
	public function cancelMute() {
		$this->muteTime = 0;
		$this->muted = false;
		$chat = new WhisperCommand($this->server, ServerChatClient::getClient(), $this, "You have been unmuted.");
		$chat->execute();
	}
}
