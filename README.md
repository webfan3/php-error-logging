# php-error-logging
ErrorHandling


## Installation
````
$ composer install webfan3/php-error-logging
````

## Usage
````php
new \Webfan\Support\PhpLogs([
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
		         'logs.error_level' => 1,
		         'logs.log_level' => 0,
		         'logs.error_reporting' => \E_ALL,
		         'logs.display_errors' => 0,
], true, function($e, $message, $level){
           $status = 500;
           $headers = ['X-Frdl-Response' => 'ServerErrorException'];
           $body = '<error>'.$e->getMessage().'<br />'.$message.'</error>';
           $protocol = '2.0';
        //   $response = new \GuzzleHttp\Psr7\Response($status, $headers, $body, $protocol);
	    //   if(function_exists('\Http\Response\send'))\Http\Response\send($response);
	       die($body);
});
````
