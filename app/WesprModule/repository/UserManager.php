<?php

namespace App\WesprModule;

use Nette,
	Nette\Utils\Strings;


/*
CREATE TABLE users (
	id int(11) NOT NULL AUTO_INCREMENT,
	username varchar(50) NOT NULL,
	password char(60) NOT NULL,
	role varchar(20) NOT NULL,
	PRIMARY KEY (id)
);
*/

/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) {
                
            list($username, $password) = $credentials;
            $row = $this->database->table('user')->where('email', $username)->fetch();

            if (!$row) {
                    throw new Nette\Security\AuthenticationException('Účet v systému neexistuje. Zkontrolujte prosím zadání.', self::IDENTITY_NOT_FOUND);
            }

            if ($row->password !== $this->calculateHash($password/*, $row->password*/)) {
                    throw new Nette\Security\AuthenticationException('Heslo bylo zádáno chybně. Zkuste to znovu.', self::INVALID_CREDENTIAL);
            }

            $arr = $row->toArray();
            unset($arr['password']);

            //Set login time
            $logintime = new \DateTime();
            $this->database->table('user')->where('id', $row->id)->update(array('logintime' => $logintime));

            return new Nette\Security\Identity($row->id, $row->role, $arr);
        }


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($username, $password)
	{
		$this->database->table('user')->insert(array(
			'email' => $username,
			'password' => $this->calculateHash($password),
		));
	}


	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public static function calculateHash($password, $salt = NULL)
	{
		if ($password === Strings::upper($password)) { // perhaps caps lock is on
			$password = Strings::lower($password);
		}
		//return crypt($password, $salt ?: '$2a$07$' . Strings::random(22));
                /** @todo secure!!! */
                return md5($password);
	}

}
