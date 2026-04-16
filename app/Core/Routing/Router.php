<?php
namespace App\Core\Routing;
class Router{
protected $r=[];
function get($u,$a){$this->r[]=['GET',$u,$a];}
function post($u,$a){$this->r[]=['POST',$u,$a];}
function delete($u,$a){$this->r[]=['DELETE',$u,$a];}
function match($p,$u,&$pa){$p=preg_replace('#\{([^/]+)\}#','([^/]+)',$p);$p='#^'.$p.'$#';if(preg_match($p,$u,$m)){array_shift($m);$pa=$m;return true;}return false;}
function dispatch($m,$u){
foreach($this->r as [$rm,$ru,$ra]){
if($rm!=$m)continue;
$pa=[];
if($this->match($ru,$u,$pa)){
$c=new $ra[0];
return call_user_func_array([$c,$ra[1]],$pa);
}}
return '404';
}}
