<?php

namespace Webfan\Support;

class PhpLogs
{
	
  const E_RECOVERABLE_ERROR = 1;	
	
  protected $config = [];
  protected $errorCallback = null;	
  protected $error_handler_stack = [];	
  protected $exception_handler_stack = [];	
	
	
  public function __construct(array $config = null, $register = true, callable $errorCallback = null){
	  $this->errorCallback = $errorCallback;
	  
	  if(null===$config){
		$config = [];  
	  }
	  $this->config = array_merge(array_merge($this->config, [
		         'logs.debug' => false, 
		         'logs.dir' => ((class_exists(\webfan\hps\patch\Fs::class)) 
								? \webfan\hps\patch\Fs::getRootDir()
								: getcwd())
		                       .\DIRECTORY_SEPARATOR.'logs'.\DIRECTORY_SEPARATOR.'frdl'.\DIRECTORY_SEPARATOR,
		         'logs.prune_size.trigger' => 999999,
		         'logs.prune_size.size' =>    32000,
		         'logs.autoprune' =>    true,
		         'logs.file.errors' => 'php_error_log',
		         'logs.file.memory' => 'php_memory_log',
		         'logs.error_level' => \E_USER_ERROR,
		         'logs.log_level' => self::E_RECOVERABLE_ERROR,
		         'logs.error_reporting' => \E_ALL,
		         'logs.display_errors' => 0,
		  ]), $config);
	  
	  if(!is_dir($this->config['logs.dir'])){
		  mkdir($this->config['logs.dir'], 0755, true);
	  }else{
		chmod($this->config['logs.dir'], 0755);  
	  }
	  
	  if(true === $register){
		$this->register();  
	  }
	  
	
  }
	
  public function formatFilesize($size){
   $units = explode(' ', 'B KB MB GB TB PB');

    $mod = 1024;

    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }

    $endIndex = strpos($size, ".")+3;

    return substr( $size, 0, $endIndex).' '.$units[$i];
 }
	
 public function pune(){
	$this
		->puneFile($this->config['logs.dir'].$this->config['logs.file.errors'],$this->config['logs.prune_size.size'],$this->config['logs.prune_size.trigger'])
		->puneFile($this->config['logs.dir'].$this->config['logs.file.memory'],$this->config['logs.prune_size.size'],$this->config['logs.prune_size.trigger'])
		;
	 
	 return $this;	
 }
 protected function puneFile(string $file,int $size,int $trigger){
	 if(!file_exists($file))return $this;
	 if(filesize($file) > $trigger){
		 if(class_exists(\webfan\hps\patch\Fs::class)){
		     \webfan\hps\patch\Fs::filePrune($file, $size, true);
		 }else{
			 $datum = date('c', time());
			 file_put_contents($file, $datum.' logfile cleared');	
		 }
	} 
   return $this;
 }	
	
 public function log_error( $num, $str, $file, $line, $context = null )
 {
    switch ( $num ) {
    	            case \E_CORE_ERROR :
    	            case \E_PARSE :
    	            case \E_ERROR : 
                    case \E_USER_ERROR:
                        $str = 'Fatal Error '.$str;
                        $severity = 2;
                      break;
                    case \E_USER_WARNING:
                    case \E_WARNING:
                        $str = 'Warning '.$str;
                        $severity = 0;
                      break;
                    case \E_USER_NOTICE:
                    case \E_NOTICE:
                    case @\E_STRICT:
                        $str = 'Notice '.$str;
                        $severity = 0;
                      break;
		            case self::E_RECOVERABLE_ERROR:
                    case @\E_RECOVERABLE_ERROR:
                        $str = 'Catchable '.$str;
                        $severity = 1;
                      break;
                    default:
                        $str = 'Unknown Error '.$str;
                        $severity = 1;
                     break;
                }



   return $this->log_exception( new \ErrorException( $str, $num, $severity, $file, $line ),  $severity, $context);
 }


 public function log_exception(  $e,  $severity = 0, $context = null)
 {
	
	
    if (!($severity >= $this->config['logs.log_level']) && strtolower(substr(\PHP_SAPI, 0, 3)) == 'cli') {
          return false;
    }
	
	
	
	   $ln = (is_object($e) && is_callable(array($e, 'getLine') ) && is_callable(array($e, 'getFile') ) && is_callable(array($e, 'getMessage') ) ) 
	   ? $e->getMessage().' '.$e->getFile().' '.$e->getLine() 
	   : $e;
	
		$datum = date('c', time());
	
	

	if(!file_exists($this->config['logs.dir'].$this->config['logs.file.errors'])){
	  file_put_contents($this->config['logs.dir'].$this->config['logs.file.errors'], '');	
	}
	
 
	
	  if(!($severity >= $this->config['logs.log_level']))return false;   
  
   $message =(is_object($e) && is_callable(array($e, 'getMessage') ))  ? $e->getMessage() : $e;
 

   if(is_object($e) && is_callable(array($e, 'getServerity') )) $severity = $e->getSeverity();




   if(!($severity >= $this->config['logs.log_level']) )return false;   
 

	 error_log($datum." ".$ln."\n", 3, $this->config['logs.dir'].$this->config['logs.file.errors']);  
	 
	
  if(!($severity >= $this->config['logs.error_level']) )return false;	 
	 
	 
   $file    =(is_object($e) && is_callable(array($e, 'getFile') ))  ? $e->getFile() : $_SERVER['PHP_SELF'];
   $file_scrambled = basename($file);
   $file_scrambled = str_replace('.php', '', $file_scrambled);
   $file_scrambled = str_pad($file_scrambled, mt_rand(3,8), '*', \STR_PAD_RIGHT);
   $file_scrambled = str_pad($file_scrambled, mt_rand(3,8), '/*/', \STR_PAD_LEFT);
   $file_scrambled = 'source:// '.$file_scrambled;
   $line = (is_object($e) && is_callable(array($e, 'getLine') ))  ?  $e->getLine() : null;
 
   $txt = '';
   $txt.= 'Es hat sich leider ein <b>Fehler</b> zugetragen!<br />';
   $txt.= 'Fehlermeldung:';
   $txt.= '<ul>';
   $txt.='<li>'.$message.'</li>';
   $txt.='<li>Quelle: '.$file_scrambled.'</li>';
	
	

	if(true===$this->config['logs.debug'] && $severity >= $this->config['logs.log_level'])
     {
		 $txt.='<li style="font-size:9px;">Admininfo: Datei: '.$file.'</li>';
     }	 
    if(is_callable($this->errorCallback)){
	    call_user_func_array($this->errorCallback, [$e, $txt, $severity]);	
		die();
	}else{
    	die($txt);
	}
 }






 public function checkLastError()
 {
	
    $error = \error_get_last();
    if ( $error["type"] === \E_ERROR || $error["type"] === \E_PARSE )
	//if (isset($error["type"]) )
        $this->log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
 }






 public function memory_usage()
 {
	 
	if(!file_exists($this->config['logs.dir'].$this->config['logs.file.memory'])){
	  file_put_contents($this->config['logs.dir'].$this->config['logs.file.memory'], '');	
	}	
	
		
    $site = '' == getenv('SERVER_NAME') ? getenv('SCRIPT_FILENAME') : getenv('SERVER_NAME');
	$site =getenv('FRDLWEB_PROXY_FOR_HOST').' -> '.
		$site.' -Host->'.$_SERVER['HTTP_HOST'];
    $url = $_SERVER['PHP_SELF'];
	$uri = $_SERVER['REQUEST_URI'];
	
    $current = memory_get_usage();
	$current_formatted = ' '.$this->formatFilesize($current);
	$current.= ' '.$current_formatted;
	
    $peak = \memory_get_peak_usage();
	$peak_formatted = ' '.$this->formatFilesize($peak);
	$peak.= ' '.$peak_formatted;
	
	$datum = date('c', time());

	error_log("$current_formatted $peak_formatted $datum $site $url $uri\n", 3, $this->config['logs.dir'].$this->config['logs.file.memory']);
}
	


	
 public function shutdown_functions()
 { 
    $this->memory_usage(); 
	$this->checkLastError();
	 if(true===$this->config['logs.autoprune']){	
		 $this->pune(); 
	 }
 }	
	
 public function register(){	

      ini_set('display_errors', $this->config['logs.display_errors']);
      error_reporting($this->config['logs.error_reporting']);
	 
	 
       $this->set_php_error_handler( [$this, "log_error"] );
       set_exception_handler( [$this, "log_exception"] );	 
	 
	if(class_exists(\frdlweb\Thread\ShutdownTasks::class)){
		$ShutdownTasks = \frdlweb\Thread\ShutdownTasks::getInstance();
                $ShutdownTasks([$this, 'shutdown_functions']);		
	}else{
		register_shutdown_function([$this, 'shutdown_functions']);		
	}	  	 
	 
	 
   return $this;
 }
	

public function set_php_error_handler(callable $fn){
   $this->error_handler_stack[] = $this->get_error_handler();
   set_error_handler($fn);	
   return $this;	
}
	
public function void_error_handler() {}

public function get_error_handler() {
  $error_handler = set_error_handler([$this, 'void_error_handler']);
  restore_error_handler();
  return $error_handler;
}

public function restore_php_error_handler() {
  while (set_error_handler([$this, 'void_error_handler']) !== NULL) {
    restore_error_handler();
    restore_error_handler();
  }  
 }
	
}
