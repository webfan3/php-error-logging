<?php

use Psr\Container\ContainerInterface;
use function DI\add;
use function DI\decorate;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use Franzl\Middleware\Whoops\WhoopsMiddleware;

return [
	
	
	    'logger.php.exception_handler' => function (ContainerInterface $c) {
		  return [$c->get(\Psr\Http\Server\RequestHandlerInterface::class),'withErrorResponse'];
		}, 
	    'logger.php' => get(\Webfan\Support\PhpLogs::class), 
	    \Webfan\Support\PhpLogs::class  => function (ContainerInterface $c) {
			
            $f = __DIR__ .\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR
			   .'config'. \DIRECTORY_SEPARATOR.'module-configs'.\DIRECTORY_SEPARATOR.'frdl.web'.\DIRECTORY_SEPARATOR
			   .'preferences.json';
           $preferences = (file_exists($f))
	           ? json_decode(file_get_contents($f))
	           : json_decode(file_get_contents(__DIR__ .\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.$c->get('project')->modules_dirname.\DIRECTORY_SEPARATOR.'frdl.web'.\DIRECTORY_SEPARATOR.'preferences.json'));
			
			
			return new \Webfan\Support\PhpLogs([
		         'logs.debug' => $preferences->logs->debug, 
		         'logs.dir' => rtrim($c->get('project')->dir, \DIRECTORY_SEPARATOR.'/ ')
		                       .\DIRECTORY_SEPARATOR.'logs'.\DIRECTORY_SEPARATOR.'frdl'.\DIRECTORY_SEPARATOR,
		         'logs.prune_size.trigger' => 999999,
		         'logs.prune_size.size' =>    32000,
	             'logs.autoprune' =>    true,
		         'logs.file.errors' => 'error.log',
		         'logs.file.memory' => 'memory.log',
		         'logs.error_level' => intval($preferences->logs->error_level),
		         'logs.log_level' => intval($preferences->logs->log_level),
		         'logs.error_reporting' => \E_ALL,
		         'logs.display_errors' => intval($preferences->logs->display_errors),
            ], true, $c->get('logger.php.exception_handler'));
          },	
	
   Run::class  => function (ContainerInterface $c) {
             $run = new Run();
             
             return $run;
          },	
  'logger.project-shield.php' => function (ContainerInterface $c) {
             return $c->get(Run::class);          
     },	
                   
    PrettyPageHandler::class  => function (ContainerInterface $c) {
             $Pretty = new PrettyPageHandler();
             
         return $Pretty;
     },	  
          

'state.emitter' => decorate(function($emitter, ContainerInterface $c){
	   
     $emitter->once('bootstrap', static function($eventName, $emitter, $Event) use($c){
			 
	     $logger = $Event->getArgument('container')->get('logger.project-shield.php');
        
     
	     $logger >register();  
     });
     
	
		return $emitter;	
}),
	
  
  
	\frdl\web\RequestHandler::class => decorate(function($handler, ContainerInterface $c){
		$handler = $handler->addMiddleware(new WhoopsMiddleware());
		
		return $handler;
	}),

];
