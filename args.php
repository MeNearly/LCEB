<?php
namespace xylian\args;


function parseArgs(array $args, bool $minPlus=false):array {
  $result=array();
  $i=0;
  $result['args']=array_values($args);
  foreach ($args as &$arg) {
    if (preg_match("@(\w+)=(.*)$@",$arg,$matches)) {
      $name=$matches[1];
      $result[$name]=$matches[2];
      unset($args[$i]);
    } elseif (preg_match("@(-(\w+))$@",$arg,$matches)) {
      $name=$matches[2];
      if (!$minPlus)
        $value=true;
      else
        $value=false;
      $result[$name]=$value;
      unset($args[$i]);
    } elseif (preg_match("@(\+(\w+))$@",$arg,$matches)) {
      $name=$matches[2];
      $result[$name]=true;
      unset($args[$i]);
    }
    $i++;
  }
  $rest=array_values($args);
  $result['remaining']=join($rest," ");

  return $result;
}

