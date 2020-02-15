<?php
declare(strict_types=1);

namespace Webfan\Support;

use Webfan\Support\LoggingHandlerInterface;
use Psr\Log\LoggerInterface;


class PhpLogs implements LoggerInterface, LoggingHandlerInterface
{
   use \Psr\Log\LoggerTrait;	
	
	
  const E_RECOVERABLE_ERROR = 1;	
	
  protected $config = [];
  protected $errorCallback = null;	
  protected $error_handler_stack = [];	
  protected $exception_handler_stack = [];
  protected $registered	= false;
	
	
	
  public function __construct(array $config = null, $register = true, callable $errorCallback = null){
	  $this->errorCallback = $errorCallback;
	  $this->registered = false;
	  
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


   foreach($this->error_handler_stack as $stack){
	if(true===call_user_func_array($stack, [$num, $str, $file, $line, $context])){
	  return true;	
	}
   }
	 
   return $this->log_exception( new \ErrorException( $str, $num, $severity, $file, $line ),  $severity, $context);
 }


 public function log_exception(  $e,  $severity = 0, $context = null)
 {
	
	 
     foreach($this->exception_handler_stack as $stack){
	if(true===call_user_func_array($stack, [$e,  $severity, $context])){
	  return true;	
	}
     } 
	 

	if(!file_exists($this->config['logs.dir'].$this->config['logs.file.errors'])){
	  file_put_contents($this->config['logs.dir'].$this->config['logs.file.errors'], '');	
	}
	 

   if(is_object($e) && is_callable(array($e, 'getServerity') )) $severity = $e->getSeverity();

    $txt = $this->exceptionMessage($e); 
	 
     if(is_callable($this->errorCallback)){
	call_user_func_array($this->errorCallback, [$e, $txt, $severity]);	
     }	
	 
    if(!($severity >= $this->config['logs.error_level']) )return true;	 
	 
    error_log($txt."\n", 3, $this->config['logs.dir'].$this->config['logs.file.errors']); 
  
   if($this->config['logs.display_errors'] || strtolower($this->config['logs.display_errors'])==='on'){
	print $xt;   
   }
   $severity >= 2 && 'cli' === strtolower(substr(\PHP_SAPI, 0, strlen('cli'))) && exit;
   $severity >= 2 && 'cli' !== strtolower(substr(\PHP_SAPI, 0, strlen('cli'))) && die();	 
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
     if(true===$this->registered){
	  return $this;    
     }
	 
      $this->registered = true;
	 
	 
      ini_set('display_errors', (string)$this->config['logs.display_errors']);
      error_reporting($this->config['logs.error_reporting']);
	 
	 
       $this
	       ->set_php_error_handler( [$this, "log_error"] )
	       ->set_php_exception_handler([$this, "log_exception"]);
	 
	if(class_exists(\frdlweb\Thread\ShutdownTasks::class)){
		$ShutdownTasks = \frdlweb\Thread\ShutdownTasks::getInstance();
                $ShutdownTasks([$this, 'shutdown_functions']);		
	}else{
		register_shutdown_function([$this, 'shutdown_functions']);		
	}	  	 
	 
	 
   return $this;
 }
	
	
	
public function prepend_error_handler(callable $fn){
   array_unshift($this->error_handler_stack, $fn);
}	
public function set_error_handler(callable $fn){
   return call_user_func_array([$this, 'set_php_error_handler'], func_get_args());	
}
public function set_php_error_handler(callable $fn){
   $previous =  $this->get_error_handler();	
   if(is_callable($previous) && !in_array($previous, $this->error_handler_stack)){
	   $this->error_handler_stack[] = $previous;
   }
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

public function prepend_exception_handler(callable $fn){
   array_unshift($this->exception_handler_stack, $fn);
}		
public function set_exception_handler(callable $fn){
   return call_user_func_array([$this, 'set_php_exception_handler'], func_get_args());	
}	
public function set_php_exception_handler(callable $fn){
   $previous =  $this->get_exception_handler();	
   if(is_callable($previous) && !in_array($previous, $this->exception_handler_stack)){
	   $this->exception_handler_stack[] = $previous;
   }
   set_exception_handler($fn);	
   return $this;	
}	
	
public function get_exception_handler() {
  $exception_handler = set_exception_handler([$this, 'void_error_handler']);
  restore_exception_handler();
  return $exception_handler;
}

public function restore_php_exception_handler() {
  while (set_exception_handler([$this, 'void_error_handler']) !== NULL) {
    restore_exception_handler();
    restore_exception_handler();
  }  
 }	
	
/*
* https://www.php.net/manual/en/function.set-exception-handler.php#98201
*/
public function exceptionMessage(\Exception $exception) {
    $datum = date('c', time());
    // these are our templates
    $traceline = "#%s %s(%s): %s(%s)";
    $msg = "(%s) %s %s: '%s' in %s:%s\nStack trace:\n%s\n thrown in %s on line %s";

    // alter your trace as you please, here
    $trace = $exception->getTrace();
    foreach ($trace as $key => $stackPoint) {
        // I'm converting arguments to their type
        // (prevents passwords from ever getting logged as anything other than 'string')
        $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
    }

    // build your tracelines
    $result = array();
    foreach ($trace as $key => $stackPoint) {
        $result[] = sprintf(
            $traceline,
            $key,
            $stackPoint['file'],
            $stackPoint['line'],
            $stackPoint['function'],
            implode(', ', $stackPoint['args'])
        );
    }
    // trace always ends with {main}
    $result[] = '#' . ++$key . ' {main}';

    // write tracelines into main template
    $msg = sprintf(
        $msg,
	getmypid(),    
	$datum,    
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        implode("\n", $result),
        $exception->getFile(),
        $exception->getLine()
    );

    // log or echo as you please
  //  error_log($msg);
  return $msg;	
}	
	
	
  public function log($level, $message, array $context = array()){
	$this->log_exception( new \Exception( $message, 0, $level ), $level, $context);    
  }
}
