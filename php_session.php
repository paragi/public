<?php
namespace php_session;
/*============================================================================*\
  PHP Session store

  This object replaces the build-in object and must have the same methods available.

  NB: SessionHandlerInterface must be in root namespace
\*============================================================================*/
class SessionHandler implements \SessionHandlerInterface{
  private $prefix;

  public function open($savePath, $sessionName){
    // Ignore savePath and session name option
    $this->prefix = __DIR__ . DIRECTORY_SEPARATOR . "var";
    if (!is_dir($this->prefix))
      mkdir($this->prefix, 0777);
    $this->prefix += DIRECTORY_SEPARATOR . "session_";

    return true;
  }

  public function close(){
    return true;
  }

  public function read($id){
    return (string)@file_get_contents("{$this->prefix}$id");
  }

  public function write($id, $data){
    if(empty($this->prefix)) return false;
    return (bool) file_put_contents("{$this->prefix}$id", $data) !== false;
  }

  public function destroy($id){
    if(empty($this->prefix)) return;
    if (file_exists("$this->prefix_$id"))
      unlink("$this->prefix_$id");
    return true;
  }

  public function gc($maxlifetime){
    if(empty($this->prefix)) return false;
    foreach (glob($this->prefix . "*") as $file)
      if (filemtime($file) + $maxlifetime < time() && file_exists($file))
        unlink($file);
    return true;
  }

  public function create_sid(){
    return uniqid("bad_session_id_");
  }
}
