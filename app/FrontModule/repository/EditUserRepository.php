<?php

/**
 * Edit operation over DB table user - lower access
 *
 * @author David Ehrlich, 2013
 * @version 1.0
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App;

class EditUserRepository extends PublicUserRepository {
    
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
