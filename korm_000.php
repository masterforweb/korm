<?php

  /*
 * This file is part of the Korm package.
 *
 * (c) Andrey Kuvshinov <sak.delf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

  class DB {

  	
  	static $config = array(); //конфиги подключения к базе
  	static $conn = array(); // все подключения
    static $memcache = '';

  	private $ORM = '';
  	private $conf = 'default';
    private $sql = '';
  	private $filters = array();
  	private $sort = array();
  	private $limit = null;
  	private $columns = array();
    private $time = 0; // cache time
    private $wh_str = '';
    private $ord_str = '';

  /**
   * The where constraints for the query.
   *
   * @var array
   */
    public $wheres;


  	function __construct($table, $conf = ''){
  		$this->table = $ORM;
  		$this->config = $conf; //текущая конфигурация
  	}


    function __toString() {
      return $this->build();
    }


    static function table($table, $conf = '') {
      return new DB ($table, $conf);
    }


    //активируем мемкеш
    static function memcache($host = '127.0.0.1', $port = 11211) {
        
        if (class_exists('Memcache')) {
          kORM::$memcache = new Memcache;
          kORM::$memcache->connect($host, $port);
        }  
      
        return;
    }

  	/*
    * добавляем конфигурацию подключения к базе
    */
    static function config($name, $user = 'root', $pswd = '', $host = 'localhost', $db = ''){
      
      if ($db == '')
        $db = $name;

  		self::$config[$name] = array('host'=>$host, 'user'=>$user, 'pswd'=>$pswd, 'db'=>$db);
      return True;

  	}

  	
  	/**
    ** сonnected DB
    */

    private function addConnection($config = array(), $name = null) {
  		 		
      if ($conf == '')
        $config = current(self::$config); //first config
      else  
        $config = self::$config[$conf]; 

      if (!is_array($config))
          error_log('no config DB `'.$conf.'` found'); 

      $mysqli = new mysqli($config['host'], $config['user'], $config['pswd'], $config['db']);
      if ($mysqli->connect_error) {
          error_log('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
      }

      $mysqli->query('SET NAMES UTF8');      
      self::$conn[$conf] = $mysqli;
     
      return True;
  	
  	}
  
  	
    /**
    * функции добавления
    */

    function separ($value){
  		return '`'.$value.'`';
  	} 

    function quote($value){
      return chr(39).$value.chr(39);
    }	

  	function columns($columns = array()){
  		$this->columns = $columns;
  		return $this;
  	}

    /**
   * Add a new select column to the query.
   *
   * @param  mixed  $column
   * @return \Illuminate\Database\Query\Builder|static
   */
  public function addSelect($column)
  {
    $column = is_array($column) ? $column : func_get_args();

    $this->columns = array_merge((array) $this->columns, $column);

    return $this;
  }
  	


    function where($column, $value = 1, $op ='=', $type = 'AND') {
  		$this->wheres[] = array('column'=>$column, 'value'=>$value, 'op'=>$op, 'type'=>$type);
  		return $this;
  	} 

    function orWhere($column, $operator = null, $value = null){

    }

  
    $column, $operator = null, $value = null, $boolean = 'and'


  	function whor($column, $value = 1, $op ='=') {
      $this->filters[] = array('column'=>$column, 'value'=>$value, 'op'=>$op, 'type'=>'OR');
      return $this; 
    }

    function not($column, $value = 1){
      $this->filters[] = array('column'=>$column, 'value'=>$value, 'op'=>'<>', 'type'=>'AND');
      return $this;
    }

    public function wh_str($sql) {
      $this->wh_str = $sql;
      return $this; 
    }

    public function ord_str($sql) {
      $this->ord_str = $sql;
      return $this;
    }

    /**
    * функция where _  in
    */

    function in($column, $values = array(), $type = 'AND') {
        
        if (is_array($values)){
            $values = implode(',', $values);
        }

        $this->filters[] = array('column'=>$column, 'value'=>$values, 'op'=>'IN', 'type'=>'AND');
        
        return $this;

    }


    /**
    * обработка массива с удалением пустых значений
    */
    function arr2value($arr, $prefix = ',') {

      $res = '';

      foreach ($arr as $item) {
        $item = trim($item);
        if ($item !== '') {
          if ($res !== '')
              $res .= ','; 
          $res .= $item; 
        }
      }

      return $res;
    
    }

    
    /**
   * Add an "order by" clause to the query.
   *
   * @param  string  $column
   * @param  string  $direction
   * @return \kORM\korm.php|static
   */
    function orderBy($column, $direction = 'asc') {
		    
      $direction = strtolower($direction) == 'asc' ? 'asc' : 'desc';  
      $this->orders[$column] = $type;
		  
      return $this;  		
  	}

  
  	function limit($value) {
      if ($value > 0) $this->limit = $value;
          return $this;
    }


    function join($jtable, $column ,$jkey = '', $type = 'LEFT JOIN'){
      
      $key = md5($jtable.$column.$type);

      if ($jkey == '')
        $jkey = $column; 
      
      $this->join[$key] = array('jtable'=>$jtable, 'column' => $column, 'jkey' => $jkey, 'type' => $type);
      
      return $this;

    }


 
  /**
  * Выбока полей 
  */


   /**
   * Set the columns to be selected.
   *
   * @param  array  $columns
   * @return \korm\korm.php|static
   */
  public function select($columns = array('*'))
  {
   
    $this->columns = is_array($columns) ? $columns : func_get_args();
    return $this;

  }


  /**
   * Add a new select column to the query.
   *
   * @param  mixed  $column
   * @return \Illuminate\Database\Query\Builder|static
   */
  public function addSelect($column)
  {
    
    $column = is_array($column) ? $column : func_get_args();
    $this->columns = array_merge((array) $this->columns, $column);

    return $this;
  }

    
/**
* операции
*/


function update($attributes = array()){

}


    function build(){
  		
      if ($this->sql !== '')
        return $this->sql;

      $sql = 'SELECT';

      if(is_array($this->columns)){

       $columns = '';
        
        foreach($this->columns as $column) {
          if ($columns !== '')
            $columns .= ',';
          $columns .= $column;
        }

      }
      else
        $columns = '*';

  		$sql .= ' '.$columns.' FROM '.$this->separ($this->ORM);
  		
      
       //joins
      if (sizeof($this->join) > 0) {
        
        foreach($this->join as $join)
          $sql .= ' '.$join['type'].' '.$join['jtable'].' ON('.$this->ORM.'.'.$join['column'].'='.$join['jtable'].'.'.$join['jkey'].')';
        
      }

      if ($this->wh_str !== '')
        $sql .= ' WHERE '.$this->wh_str;
      elseif (count($this->filters) > 0)
        $sql .= $this->build_filters();
  		
      if ($this->ord_str !== '')
        $sql .= ' ORDER BY '.$this->ord_str;
      elseif (count($this->sort) > 0)
        $sql .= $this->build_sort();

      
       		
      //limit
      if ($this->limit !== null)
  			$sql .= ' LIMIT '.$this->limit;

  		$sql .= ';';

		 // echo $sql;

      return $sql;

  	}

    
    function count(){
      
      $sql = 'SELECT COUNT('.$this->columns.') FROM'.$this->separ($this->ORM);
      $sql .= $this->build_filters();

      $result = $this->query($sql);

      if ($result) {
        $count = $result->fetch_row();
        return $count[0];
       }  

      return null;
      

    }




  	function build_filters(){

  		$res = '';

  		foreach ($this->filters as $filter){
  			
  			if ($res !== '')
  				$res .= ' '.$filter['type'].' ';

  			$res .=	$this->separ($filter['column']);

        $op = trim($filter['op']);

        if ($op == '')
          $res .= ' '.$filter['value'];
        else
          $res .=$op.$this->quote($filter['value']);


  		} 

  		return ' WHERE '.$res;

  	} 


  	function build_sort(){

  		$res = '';

  		foreach ($this->sort as $key => $sort){
  			
  			if ($res !== '')
  				$res = ',';
  			
  			$res .= $this->separ($key).' '.$sort;
  		
      }

  		return ' ORDER BY '.$res;
  	
  	}


  	function all() {

  		$sql = $this->build();
      $result = $this->query($sql);
        

      while ($row = $result->fetch_assoc()) {
          $result_array[] = $row;
      }

      return $result_array;   
        		
  	}

    function num() {

    }


    function one() {

      $sql = $this->build();
      $result = $this->query($sql);

      if ($result)
        return $result->fetch_assoc(); 

      return null;

    }


  function query($sql, $conf=''){
      
    if ($this->time > 0)
        $result = $this->cache($sql);

    $this->conn($conf);
    $curr = kORM::$conn[$conf];

    $result = $curr->query($sql);

    if ($this->time > 0)
      $this->cache($sql, $result);
      
    if ($curr->errno) 
      error_log('Select Error (' . $mysqli->errno . ') ' . $mysqli->error);
    

    return $result;
    
  }



  function sql($sql) {
    $this->sql = $sql;
    return $this;
  }

   
    function cache($sql, $value = null) {

      $key = md5($sql);

      if (is_null($value)) 
         return korm::$memcache->set($key, $value, False, $this->time);   

      if ($result = korm::$memcache->get($key))
          return $result;
      else
          return False;
    
    }

    function remember($minutes = 2, $key = null){

        $this->time = $minutes;
        $this->cacheKey = $key;

        return $this;
    }


    function time($time = 3600){
      $this->time = $time;
      return $this;
    }

  
    function set($column, $value = 0) {
      
      $this->set[$column] = $value;
      return $this;

    }


    function array2insert($arr = array()){
      
      $this->set = $arr;
      $this->save(); 

      return $this;

    }

    
    function save() {

      foreach($this->set as $key => $set){
        
        $set = trim($set);

        if ($set !== '') {
          
          if (isset($columns))
            $columns .= ',';

          if (isset($values))
            $values .= ',';

          $columns .= '`'.$key.'`';
          $values .= '"'.$set.'"';

        }  

      }

      
     $this->query('INSERT INTO `'.$this->ORM.'` ('.$columns.') VALUES('.$values.');');
 
    }



   


  }


// автозагрузка класса

  
  //функция быстрой загрузки
  if (!function_exists('table')) {
    function table($table, $conf = ''){
      return new kORM($table, $conf);
    }
  }  


  spl_autoload_register(function ($class) {
      
 
  $fclass = SITEPATH.'app/models/'.$class.'.php';
    
  if (file_exists($fclass))
     require $fclass;
    else
      return table($class);


      //error(500, 'not found class '.$class);

  });

