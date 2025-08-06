<?php
/**
 * Queries to MySQL DB
 * 
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\Repository;

use Nette,
    Nette\Database,
    OrbisRex\Translator;

class GeneralRepository extends Nette\Object {
    
        /** @var Database\Context */
        protected $database;
        
        public function __construct(Database\Context $database)
        {
            $this->database = $database;
        }
        
        /**
         * Returns table in object.
         * @return Nette\Database\Table\Selection
         */
        protected function getTable()
        {
            // Table name is deduced from name of the repository file.
            preg_match('#((Public|Modify|Edit)?([A-Z][a-z]+)*([A-Z][a-z]+))Repository$#', get_class($this), $matches);
            
            if(!empty($matches[3])) {
                $tableName = $matches[3].'_'.$matches[4];
            } else {
                $tableName = $matches[4];
            }
            
            return $this->database->table(strtolower($tableName));
        }
        
        /**
         * Return counts.
         * @return Nette\Database\Table\Selection
         */
        public function countAll() {
            return $this->getTable()->count('*');
        }
        
        public function countDictinct($column) {
            return $this->getTable()->count('DISTINCT '.$column);
        }
        
        public function countWhere($where, array $variables) {
            return $this->getTable()->where($where, $variables)->count('*');
        }
        
        /**
         * Return all rows of table.
         * @return Nette\Database\Table\Selection
         */
        public function findAll()
        {
            return $this->getTable();
        }

        /**
         * Direct query into DB.
         * @return Nette\Database\ResultSet
         */
        public function executeQuery($query)
        {
            $database = $this->database->getConnection();
            return $database->query($query);
        }

        /**
         * Queries into DB. Return data depend at array.
         * @return Nette\Database\Table\Selection
         */
        public function findBy(array $by)
        {
            return $this->getTable()->where($by);
        }

        public function findMax($max)
        {
            return $this->getTable()->max($max);
        }
        
        public function findByMax($max, array $by)
        {
            return $this->getTable()->where($by)->max($max);
        }

        /**
         * Queries into DB including SELECT.
         * @return Nette\Database\Table\Selection
         */
        public function findSelectOrder($select, $order)
        {
            return $this->getTable()->select($select)->order($order);
        }
        
        public function findSelectWhere($select, $where, $variables)
        {
            return $this->getTable()->select($select)->where($where, $variables);
        }
        
        public function findSelectWhereOrder($select, $where, $variables, $order)
        {
            return $this->getTable()->select($select)->where($where, $variables)->order($order);
        }
        
        public function findSelectWhereLimit($select, $where, $variables, $limit)
        {
            return $this->getTable()->select($select)->where($where, $variables)->limit($limit);
        }
        
        public function findSelectWhereLimitOffset($select, $where, $variables, $limit, $offset)
        {
            return $this->getTable()->select($select)->where($where, $variables)->limit($limit, $offset);
        }
        
        public function findSelectWhereGroup($select, $where, $variables, $group)
        {
            return $this->getTable()->select($select)->where($where, $variables)->group($group);
        }
        
        public function findSelectWhereGroupLimit($select, $where, $variables, $group, $limit)
        {
            return $this->getTable()->select($select)->where($where, $variables)->group($group)->limit($limit);
        }
        
        public function findSelectWhereOrderLimit($select, $where, $variables, $group, $limit)
        {
            return $this->getTable()->select($select)->where($where, $variables)->order($group)->limit($limit);
        }
        
        public function findSelectWhereOrderLimitOffset($select, $where, $variables, $order, $limit, $offset)
        {
            return $this->getTable()->select($select)->where($where, $variables)->order($order)->limit($limit, $offset);
        }
        
        public function findRandom($table, $select, $where, $variables, $limit) {
            return $this->database->table($table)->select($select)->where($where, $variables)->limit($limit);
        }

        /**
         * Vrací řádky z filtru textového řetězce.
         * @return Nette\Database\Table\Selection
         */
        public function findWhere($string, array $variables)
        {
            return $this->getTable()->where($string, $variables);
        }

        /**
         * Vrací řádky z filtru textového řetězce.
         * @return Nette\Database\Table\Selection
         */
        public function findGroup($string)
        {
            return $this->getTable()->group($string);
        }
        
        /**
         * Vrací sloučené řádky dle zvolené funkce.
         * @return Nette\Database\Table\Selection
         */
        public function findWhereAggregate($string, $function)
        {
            return $this->getTable()->where($string)->aggregation($function);
        }

        /**
         * Vrací řádky z filtru textového řetězce seřazené dle $order.
         * @return Nette\Database\Table\Selection
         */
        public function findWhereOrder($string, $order)
        {
            return $this->getTable()->where($string)->order($order);
        }

        /**
         * Vrací řádky z filtru textového řetězce seřazené dle $order a omezené dle $limit.
         * @return Nette\Database\Table\Selection
         */
        public function findWhereLimit($where, $order, $limit)
        {
            return $this->getTable()->where($where)->order($order)->limit($limit);
        }

        /**
         * Vloží záznam do tabulky dle podmínky v poli.
         * @var integer $Id for identefication of row.
         * @return Nette\Database\Table\Selection
         */
        public function insertData(array $data)
        {
            return $this->getTable()->insert($data);
        }

        /**
         * Vrací řádky z filtru textového řetězce.
         * @var integer $Id for identefication of row.
         * 2var array $data for data.
         * @return Nette\Database\Table\Selection
         */
        public function updateById($id, array $data)
        {
            return $this->getTable()->where($id)->update($data);
        }

        public function updateByColl($coll, $value, array $data)
        {
            return $this->getTable()->where($coll, $value)->update($data);
        }
        
        public function updateByWhere($where, $value, array $data)
        {
            return $this->getTable()->where($where, $value)->update($data);
        }

        public function deleteById($array)
        {
            return $this->getTable()->where($array)->delete();
        }
        
        /**
         * Dotaz včetně SELECT.
         * @return Nette\Database\Table\Selection
         * @deprecated since version 1.1
         */
        public function selectWhereOrder($select, $where, $order)
        {
            return $this->getTable()->select($select)->where($where)->order($order);
        }        
        
        /**
         * Vrací řádky z filtru textového řetězce.
         * @return Nette\Database\Table\Selection
         * @deprecated since version 1.1
         */
        public function findJoin($select, $where)
        {
            return $this->getTable()->select($select)->where($where);
        }        
    }
