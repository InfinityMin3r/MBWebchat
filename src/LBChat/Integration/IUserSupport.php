<?php
namespace LBChat\Integration;

use LBChat\Database\Database;

/**
 * Defines a set of methods for getting information about users from a database.
 * Interface IUserSupport
 * @package LBChat\Integration
 */
interface IUserSupport {
	/**
	 * @param Database $database A Database to use for queries
	 */
	public function __construct(Database $database);

	/**
	 * Get a user's ID based on their username
	 * @param string $username The user's username
	 * @return int
	 */
	public function getId($username);

	/**
	 * Resolve a user's username, generally just capitalization
	 * @param string $username The user's username
	 * @return string
	 */
	public function getUsername($username);

	/**
	 * Get a user's access level (0 = user, 1 = mod, 2 = admin)
	 * @param string $username The user's username
	 * @return int
	 */
	public function getAccess($username);

	/**
	 * Get a user's display name to be shown in the chat and list.
	 * @param string $username The user's username
	 * @return string
	 */
	public function getDisplayName($username);

	/**
	 * Get the user's custom username color in a "RRGGBB" (hex) form
	 * @param string $username The user's username
	 * @return string
	 */
	public function getColor($username);

	/**
	 * Get a user's various titles that decorate their name
	 * @param string $username The user's username
	 * @return array An array containing the flair, prefix, and suffix in that order.
	 */
	public function getTitles($username);

	/**
	 * Attempt to login a user.
	 * @param string $username The user's username
	 * @param string $type Either "key" or "password" for which method to use
	 * @param string $data The key/password to use
	 * @return boolean If the login succeeded
	 */
	public function tryLogin($username, $type, $data);
}