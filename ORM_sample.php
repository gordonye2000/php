<?php
/**
 * This file is used in Drupal 7 for registering a domain and/or manipulating domain's data
 * Author: Gordon Ye 
 * Create Date: 2012-11-08
 * Update Date:
**/

class domain {

	private $_site_url = '';
	private $_domain_url = '';
	private $settings = array();
	private static $_Object; 
	
    /**
     * constructor : set up the variables
     *
     * @param dbobject $db db_class object

     * @return object
     */
	function __construct()
	{
		$this->_site_url = isset($_SERVER["SERVER_HOST"])?$_SERVER["SERVER_HOST"]:$_SERVER["SERVER_NAME"];
		$this->_site_url = str_replace('www.','',strtolower($this->_site_url));
		$this->_getDomainInfo();
		self::$_Object = $this;
		return self::$_Object;
	}

    /**
     * Get the module static object
     *
     * @return self
     */
    public static function getInstance() 
    {
    	$class = __CLASS__;
    	if (!isset(self::$_Object)) {
    		return new $class();
    	}	
    	return self::$_Object;
    }
	    
	/**
     * Get the domain information
     *
     * @return settings
     */
	private function _getDomainInfo($domain_url=null)
	{
		$this->_domain_url = !empty($domain_url)?$domain_url:$this->_site_url;
		$sql = 'SELECT * FROM {domain} WHERE sub_domain=:sub_domain or domain=:domain';	  
		$row = db_query($sql, array('sub_domain' => $this->_domain_url, 'domain' => $this->_domain_url))->fetchAssoc(); //fetchObject();
		$this->settings = array();
		foreach($row as $var => $value) {
			$this->settings[$var] = $value;
		}
	}
	
	/**
     * Get the domain information
     *
     * @return domainInfo
     */
	  public function getDomain($domain_url=null)
	  {
		 $this->_getDomainInfo($domain_url);
	  }
	
    /**
     * Magic Get
     *
     * @param string $property Property name
     *
     * @return mixed
     */
    final public function __get($property)
    {
        return $this->__getProperty($property);
    }

    /**
     * Magic Set
     *
     * @param string $property Property name
     * @param mixed $value New value
     *
     * @return self
     */
    final public function __set($property, $value)
    {
        return $this->__setProperty($property, $value);
    }

    /**
     * Magic Isset
     *
     * @param string $property Property name
     *
     * @return boolean
     */
    final public function __isset($property)
    {
       if ($property == 'domain')
       		return isset($this->_domain_url);
       else	
       		return isset($this->settings[$property]);
    }

    /**
     * Get Property
     *
     * @param string $property Property name
     *
     * @return mixed
     */
    protected function __getProperty($property)
    {
        $value = null;

        $methodName = '__getVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            $value = call_user_func(array($this, $methodName));
        } else {
        	if (isset($this->settings[$property])) {
        		$value = $this->settings[$property];
        	}
        }

        return $value;
    }

    /**
     * Get domain Property
     *
     * @param void
     *
     * @return string
     */
    protected function __getValDomain()
    {
        return $this->_domain_url;
    }
    
    /**
     * Set Property
     *
     * @param string $property Property name
     * @param mixed $value Property value
     *
     * @return self
     */
	final protected function __setProperty($property, $value)
    {
        $methodName = '__setVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            call_user_func(array($this, $methodName), $value);
        } else {
       		$this->settings[$property] = $value;
        }
            
        return $this;
    }
	
    /**
     * Set __setValDomain Property
     *
     * @param mixed $value Property value
     *
     * @return false
     */
    protected function __setValDomain($value)
    {
        return false;
    }

    /**
     * Set __getValSettings Property
     *
     * @param mixed $value Property value
     *
     * @return self
     */
    private function __getValSettings(){
    	return 	$this->settings;
    }
     
    public function getDomains()
  	{
		$sql = 'SELECT * FROM {domain} ORDER BY domain ASC ';	  	  
		$result = db_query($sql);
		while($row = $result->fetchAssoc()) {
			$data[$row['did']] = $row;
		} 
		return $data;
 	}
  
	public static function getDomainID($domain)
  	{
  		$domain = str_ireplace('www', '', $domain);
		$sql = 'SELECT did FROM {domain} WHERE sub_domain=:sub_domain or domain=:domain';
		$result = db_query($sql,array('sub_domain' => $domain, 'domain' => $domain))->fetchField();
		return $result;
  	}

 	public static function getDomainByID($did)
  	{
		$sql = 'SELECT * FROM {domain} WHERE did=:did';  
		$result = db_query($sql,array('did' => $did))->fetchAssoc();
		return $result;
  	}

  	private static function saveDomain($fieldArray)
  	{
  		$did 		= isset($fieldArray["did"])?$fieldArray["did"]:0;
  		$domain 	= isset($fieldArray["domain"])?$fieldArray["domain"]:'';

  		if (empty($domain) && empty($did))
  			return false;

  		if (!empty($domain))
  		{
  			$id = self::getDomainID($domain);
	  		if (empty($did))
	  			$did = $id;
  			elseif ($did != $id)
 	 			return false;
  		}
  			
  		if (!empty($did))
  			$info = self::getDomainByID($did);
  		else
  			$info = array();
  			
  		if (empty($domain))
  			$domain = $info['domain'];
  		
  		$sub_domain = isset($fieldArray["sub_domain"])?$fieldArray["sub_domain"]:(isset($info["sub_domain"])?$info["sub_domain"]:'');

  		if (empty($sub_domain) || stripos($sub_domain, $domain) === false)
  			return false;
  			
  		// fields
  		$fields = array();
  		$fields['domain'] = $domain;
  		$fields['sub_domain'] = $sub_domain;
  		if (isset($fieldArray["view_prefix"]) && !empty($fieldArray["view_prefix"]))
  			$fields['view_prefix'] = $fieldArray["view_prefix"];
  		if (isset($fieldArray["theme"]) && !empty($fieldArray["theme"]))
  			$fields['theme'] = $fieldArray["theme"];
  		
  		if ($did) {
  			$fields['changed'] = REQUEST_TIME;
  			$num_updated = db_update('domain') 
			  ->fields($fields)
			  ->condition('did', $did, '=')
			  ->execute();
  		}
  		else 
  		{
	  		if (empty($view_prefix))
	  			$fields['view_prefix'] = 'v_'.str_ireplace(array('.','_','-'),'',strtolower($domain)).'_';
  			$fields['created'] = REQUEST_TIME;
			$did = db_insert('domain') 
			->fields($fields)
			->execute();
  		}
		return $did;
  	}

    public static function update_domain($fieldArray)
    {
    	return self::saveDomain($fieldArray);
    }

	public static function setupDomain($name, $theme='garland')
	{
		$fieldArray = array();
		$fieldArray['domain'] = $name;
		$fieldArray['sub_domain'] = 'blog.'.$name;
		$fieldArray['view_prefix'] = 'v_'.str_replace(array('.','_','-'),'',$name).'_';
		$fieldArray['theme'] = $theme;
		return self::saveDomain($fieldArray);
	}

	public static function exist($name)
	{
		$id = self::getDomainID($name);
		return !empty($id);
	}

	private static function create_view($domain)
	{
		if ($did = self::getDomainID($domain))
		{		
			$info = self::getDomainByID($did);
			$sql = "
				DROP VIEW IF EXISTS ".$info['view_prefix']."node;
				CREATE VIEW ".$info['view_prefix']."node AS (
				SELECT *
				FROM node
				WHERE node.nid IN(SELECT domain_object.entity_id
									  FROM (domain_object
										 JOIN domain
										   ON (((domain_object.did = domain.did)
												AND (domain_object.entity_type = 'node')
												AND (domain_object.deleted = '0'))))
									  WHERE ((domain.domain = '".$domain."')
											 AND (domain.status = '1')))) WITH CASCADED CHECK OPTION;";
			
			db_query($sql);
			
			$sql = "
				DROP VIEW IF EXISTS ".$info['view_prefix']."tracker_node;
				CREATE VIEW ".$info['view_prefix']."tracker_node AS (
				SELECT *
				FROM tracker_node
				WHERE tracker_node.nid IN(SELECT domain_object.entity_id
									  FROM (domain_object
										 JOIN domain
										   ON (((domain_object.did = domain.did)
												AND (domain_object.entity_type = 'node')
												AND (domain_object.deleted = '0'))))
									  WHERE ((domain.domain = '".$domain."')
											 AND (domain.status = '1')))) WITH CASCADED CHECK OPTION;";
				                                         			
			db_query($sql);
			return true;
		}
		return false;
	}
	  
	public static function recurse_copy($source,$dest) 
	{
	    if(is_dir($source)) {
	        $dir_handle=opendir($source);
	        $sourcefolder = basename($source);
	        mkdir($dest."/".$sourcefolder);
	        while($file=readdir($dir_handle)){
	            if($file!="." && $file!=".."){
	                if(is_dir($source."/".$file)){
	                    self::recurse_copy($source."/".$file, $dest."/".$sourcefolder);
	                } else {
	                    copy($source."/".$file, $dest."/".$file);
	                }
	            }
	        }
	        closedir($dir_handle);
	    } else {
	        // can also handle simple copy commands
	        copy($source, $dest);
	    }
	} 
	  
	private static function create_symlink($source,$dest) 
	{
		$fullpath = $dest.'/files';
		if (!is_dir($fullpath))
		{
			exec('mklink /J '.str_replace('/','\\',$fullpath).' '.str_replace('/','\\',$source)); //----- create symlink in windows ----
	  	 	exec('ln -s '.str_replace('\\','/',$source).' '.str_replace('\\','/',$fullpath));   //----- create symlink in linux ----
		}
	}
	
	public static function create_domain($domain_name,$theme,$path) 
	{
		if ($did = self::setupDomain($domain_name,$theme))
		{	
			$info = self::getDomainByID($did);
			$src = $path."/sites/default/files";
			$fullpath = $path."/sites/".$info['domain'];
			$file = $fullpath."/settings.php";
			if (!is_dir($fullpath))
				mkdir($fullpath, 0755);
				
			// the settings file
			$setting = '<?php
							$domain_settings[\'prefix\'] 		= \''.$info['view_prefix'].'\';
							$domain_settings[\'site_name\']		= \''.$info['domain'].'\';
							$domain_settings[\'theme_default\']	= \''.$info['theme'].'\';
							
							include_once DRUPAL_ROOT .\'/sites/default/domain_settings.php\';
					   ';
			file_put_contents($file, $setting);
			
			// create symlink to files folder
			self::create_symlink($src,$fullpath);
			
			// create symlink to files folder
			return self::create_view($info['domain']);
		}
		return false;
	}
	
	/**
     * Display the object 
     *
     * @return void
     */
    public function printMe() {
		echo '<br />';
		echo '<pre>';
		print_r ($this);
		echo '</pre>';
	}
}


