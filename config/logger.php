<?php

use Psr\Container\ContainerInterface;
use function DI\factory;
use function DI\create;
use function DI\get;
use function DI\add;
use function DI\decorate;
use function DI\env;

use WShafer\PSR11MonoLog\MonologFactory as LoggerFactory;


return [
     'logger' => factory(function(ContainerInterface $c) {	
         return new LoggerFactory($c);    
      }),
    
];
