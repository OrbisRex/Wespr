<?php

/**
 * Operation over DB table User
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository,
    Nette\Utils\Strings;

class ModifyUserRepository extends Repository\UserRepository {

    public function findValidateUser($lang, $role) {
        return $this->findWhere('role ? AND state ?', array($role, array('public')));
    }

    public function insertUser($data) {
        return $this->insertData($data);
    }

    public function updateUser($col, $value, $data) {
        return $this->updateByColl($col, $value, $data);
    }
    
    /*Static methods - for callback within conditions*/
    public static function verifyPassword($item, $arg) {
        $curretPassword = ModifyUserRepository::calculateHash($item->value);
        return ($arg === $curretPassword) ? true : false;
    }
   
    public static function calculateHash($password, $salt = NULL) {
            if ($password === Strings::upper($password)) { // perhaps caps lock is on
                $password = Strings::lower($password);
            }
            
            //return crypt($password, $salt ?: '$2a$07$' . Strings::random(22));
            /** @todo secure!!! */
            return md5($password);
    }
    
    public static function calculateTag($email) {
            return hash('sha256', $email);
    }
    
    public function verifyTag($tag) {
        $user = $this->findOneUserByTag($tag);
        return (count($user) == 1) ? $user : false;
    }
}
