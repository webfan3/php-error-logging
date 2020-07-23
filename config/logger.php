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
     'channelRoot' => [LoggerFactory::class, 'channelRoot'],
     'channelAdmin' => [LoggerFactory::class, 'channelAdmin'],
    // 'channelDeveloper' => [LoggerFactory::class, 'channelDeveloper'],
     'channelUser' => [LoggerFactory::class, 'channelUser'],
      
    'monolog' => [
        'formatters' => [
            // Array Keys are the names used for the formatters
            'lineFormatter' => [
                // A formatter type or pre-configured service from the container
                'type' => 'line',
                
                // Formatter specific options.  See formatters below
                'options' => [
                    'format'                     => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                    'dateFormat'                 => "c",
                    'allowInlineLineBreaks'      => true,
                    'ignoreEmptyContextAndExtra' => false,
                ],
            ],
            
            'htmlFormatter' => [
                // A formatter type or pre-configured service from the container
                'type' => 'html',
                
                // Formatter specific options.  See formatters below
                'options' => [
                    'dateFormat'                 => "c"
                ],
            ],
        ],
        
        'handlers' => [
            // Array Keys are the names used for the handlers
            'default' => [
                // A Handler type or pre-configured service from the container
                'type' => 'stream',
                
                // Optional: Formatter for the handler.  Default for the handler will be used if not supplied
                'formatter' => 'lineFormatter', 
                
                // Handler specific options.  See handlers below
                'options' => [
                    'stream' => './logs/log.default.txt',
                ], 
                'processors' => [
                    'main-processor',
                ],
            ],
            
            'admin' => [
                // A Handler type or pre-configured service from the container
                'type' => 'stream', 
                
                // Optional: Formatter for the handler.  Default for the handler will be used if not supplied
                'formatter' => 'htmlFormatter', 
                
                // Adaptor specific options.  See adaptors below
                'options' => [
                    'stream' => './logs/log.admin.txt',
                ], 
            ],
            
            'user' => [
                // A Handler type or pre-configured service from the container
                'type' => 'stream', 
                
                // Optional: Formatter for the handler.  Default for the handler will be used if not supplied
                'formatter' => 'htmlFormatter', 
                
                // Adaptor specific options.  See adaptors below
                'options' => [
                    'stream' => './logs/log.user.txt',
                ], 
            ],
        ],
        
        'processors' => [
            // Array Keys are the names used for the processors
            'processorOne' => [
                // A processor type or pre-configured service from the container
                'type' => 'psrLogMessage',
                
                // processor specific options.  See processors below
                'options' => [], 
            ],
            
            'processorTwo' => [
                // A processor type or pre-configured service from the container
                'type' => 'uid',
                
                // processor specific options.  See processors below
                'options' => [
                    'length'  => 7,
                ], 
            ],
            'main-processor' => [
                // A processor type or pre-configured service from the container
                'type' => 'psrLogMessage',
                
                // processor specific options.  See processors below
                'options' => [], 
            ],            
        ],
        
        'channels' => [
            // Array Keys are the names used for the channels
            //
            // Note: You can specify "default" here to overwrite the default settings for the
            // default channel.  If no handler is defined for default then the default 
            // handler will be used.
            'default' => [
                // Optional: Name of channel to show in logs.  Defaults to the array key
                'name' => 'Application Events',
                
                // array of handlers to attach to the channel.  Can use multiple handlers if needed.
                'handlers' => ['default'],
                
                // optional array of processors to attach to the channel.  Can use multiple processors if needed.
                'processors' => ['processorOne', 'processorTwo', 'main-processor'],
            ],
 
            'channelRoot' => [
                // Optional: Name of channel to show in logs.  Defaults to the array key
                'name' => 'System Events',
                
                // array of handlers to attach to the channel.  Can use multiple handlers if needed.
                'handlers' => ['default', 'admin'],
                
                // optional array of processors to attach to the channel.  Can use multiple processors if needed.
                'processors' => ['processorOne', 'processorTwo', 'main-processor'],
            ],
            
            'channelAdmin' => [
                // Optional: Name of channel to show in logs.  Defaults to the array key
                'name' => 'Admin Events',
                
                // array of handlers to attach to the channel.  Can use multiple handlers if needed.
                'handlers' => ['admin'],
                
                // optional array of processors to attach to the channel.  Can use multiple processors if needed.
                'processors' => ['processorTwo'],
            ],
            'channelUser' => [
                // Optional: Name of channel to show in logs.  Defaults to the array key
                'name' => 'User Events',
                
                // array of handlers to attach to the channel.  Can use multiple handlers if needed.
                'handlers' => ['user'],
                
                // optional array of processors to attach to the channel.  Can use multiple processors if needed.
                'processors' => ['processorOne'],
            ],
        ],
    ],  
];
