<?php
declare(strict_types=1);

namespace Webfan\Support;

interface LoggingHandlerInterface
{

  public function prune();
  public function register();
  public function prepend_error_handler(callable $fn);
  public function set_error_handler(callable $fn);
  public function get_error_handler();
  public function prepend_exception_handler(callable $fn);
  public function set_exception_handler(callable $fn);
  public function get_exception_handler();
  public function restore_php_exception_handler();
  public function restore_php_error_handler();

}

