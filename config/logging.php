<?php

use Psr\Container\ContainerInterface;
use function DI\add;
use function DI\decorate;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use Franzl\Middleware\Whoops\WhoopsMiddleware;

return [
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
