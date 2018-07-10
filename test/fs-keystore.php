<?php
/*============================================================================*\
  Simple File Store
  
  Is a simple key value store, Where the key is a string and the value is a 
  scalar or array.
  Its using the OS files system to organise store and retrieve data, either from
  a disk ort a ramdisk.
  
  The operations is POST, GET and DELETE
  
  Features:
  - Extremly fast
  - Very reliant
  - Very little footprint.
  - No dependencies 
  - Works without configuration or setup.
  - Very flexible.
  - Stored in editable text files.

  Introduction:
  
  This is actually really simple. a document (typically a php array containing a
  record of some sort) are packed and stored in a text file in the file system. 
  The collection (directory name) contains the file. The key is the file name.
  
  So:
  - Data are organised in collection of documents. (same as tables of rows in SQL)
  - collections are created with the first post to it.
  - There are no limits on the structure of a document other then that of the 
    variable used.
  - Keys is any string of legal charakters.
  - There are no other restrictions then what the filesystem imposes on the 
    datasets. So you are free to really mess things up, if you choose to :)
  
  Legal charakters for anything other than documents are:
    printable charakters excluding: /|\:<>?*'"~&
    Illigal charakters are simply striped off if used. 

  Usages:
  
  array sfs_post(string $collection, string $key, mixed $document [,int $flags = 0])
       
    Post(insert or if it already exists, update) document with the given key, 
    in the the named collection.
    
  array sfs_get([string $collection [, string $key [, int $min_time [,int $max_time]]]])
    
    Search the collection for a match to the key and returns the first occurence 
    of a document. If wildcards (? and *) are used, and more than one document 
    are found; the count variable are set to the number of documents found.

    If min and max time stamps are specified, only documents create timestamp 
    within this limit is searched.
    If no parameters are given, the list of collections names are the result.
    If only collection name is given, the list of keys in the collection is the 
    result.
    
  array sfs_get_next(int $cursor);
  
    If a call to sfs_get returned a count, the next document, matching the search
    is returned. (or an empty document, if the end has been reached.
    
  array sfs_delete(string $collection [, string $key])
  
    delete a document or an entire collection, if $key is omitted.
  
  array sfs_set_data_dir(string $path)  
  
    Set the root data directory to use. Defaults to sys_get_temp_dir directory 
    path used for temporary files. 
  

  Parameters:
  
    collection: name of the collection of key and documents. Since the collection
                name is really a path, it can contain a sub collection, separated 
                by a '/' (or '\')
    key:        a not NULL string of legal charakters, that uniquely identifies
                the document. With sfs_get, the key may contain wildcards * and ? 
    document:   an array or scalar value to store. 
    flags:      an integer of bitwise combined options:
    
      _SFS_JSON:  Store document in JSON format, rather than the slightly more 
        efficient serialised format

      _SFS_ORDERBY_TIME: Get will sort results by time stamp

      _SFS_ORDERBY_TIME_DESC: Get will sort results by time stamp in descending 
        order.

      _SFS_ORDERBY_KEY: Get will sort results by the key.
    
      _SFS_ORDERBY_KEY_DESC: Get will sort results by the key in descending 
        order.
  
    $min_time:  an epoch timestamp (UTC) defining the oldest document to include
    $max_time:  an epoch timestamp (UTC) defining the yongest document to include
    
  return values:
  
  Returns an associative array containing the result of the operation:
  
    string error:   Empty or if an error occured, an error message
    mixed document: Only used by sfs_get. Contain the retrieved document if any. 
      Empty if no documents was found.
    string key:     Current key to document.  
    int count:      If a search has more results pending, count contains the number
      of unread documents, that can be retrieved with sfs_get_next.
  
  
  Examples:
  
  post combining keys
  semafor
  auto increment
  
  
  
  set_data_dir ram disk
  
  
  
  (c) Paragi 2017, Simon Riget. 
  License MIT. => Free.
  
  
  check post JSON get serialised and vise versa
  
\*============================================================================*/
define(_SFS_JSON              ,0x01);
define(_SFS_ORDERBY_TIME      ,0x02);
define(_SFS_ORDERBY_TIME_DESC ,0x04);
define(_SFS_ORDERBY_KEY       ,0x08);    
define(_SFS_ORDERBY_KEY_DESC  ,0x10);

if(is_dir(sys_get_temp_dir()))
  $_SFS_DATA_DIR = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
  
elseif(is_dir($_SERVER['DOCUMENT_ROOT']))
  $_SFS_DATA_DIR = 
     $_SERVER['DOCUMENT_ROOT']
   . DIRECTORY_SEPARATOR 
   . "sfs_data"
   . DIRECTORY_SEPARATOR
  ;
      
function sfs_set_data_dir($path){
  global $_SFS_DATA_DIR;
  $path = realpath($path);
  if(!is_dir($path))
    return ["error" => "Invalid path to data storage directory"];
    
  $_SFS_DATA_DIR = $path . DIRECTORY_SEPARATOR;
  return ["error" => ""];  
}

echo "<pre>";
print_r(sfs_set_data_dir("./"));
echo "_SFS_DATA_DIR:",$_SFS_DATA_DIR;

function sfs_post($collection, $key, $document ,$flags = 0){
  global $_SFS_DATA_DIR;

  if(empty($key))
    return ["error" => "No key given"];
  
  $pruned_key = preg_replace('/[\x00-\x1F\/\|\\\:<>?\*\'"\~\&]|([\.]{2,})/', '', $key);
  if(empty($pruned_key))
    return ["error" => "The legal key given"];
  
  
  // is_writeable ?
  
  // Create dirs
    
  if($flags & _SFS_JSON)
    $count = @file_put_contents(
       "{$_SFS_DATA_DIR}{$collection}" . DIRECTORY_SEPARATOR . $pruned_key
      ,json_encode(JSON_HEX_QUOT | JSON_PRETTY_PRINT)
    );
  else
    $count = @file_put_contents(
       "{$_SFS_DATA_DIR}{$collection}" . DIRECTORY_SEPARATOR . $pruned_key
      ,serialize($record)
    );
    
  if($count === false) 
    return ["error" => "Unable to write to filesystem: "
    . "{$_SFS_DATA_DIR}{$collection}" . DIRECTORY_SEPARATOR . $pruned_key];  
  
  return ["error" => ""];  
}



function check($client_external_ip){
  $check = "testing";
  if($_REQUEST['fail']) 
    return $_REQUEST['fail'];

  $check = "IP address: " . $_SERVER['REMOTE_ADDR'];
  if(!filter_var($_SERVER['REMOTE_ADDR'],FILTER_VALIDATE_IP)) 
    return $check;

  $check = "No record";
  $record = unserialize(@file_get_contents("$store/connect/{$_SERVER['REMOTE_ADDR']}"));
  if(empty($record)) 
    return $check;
  
  if(time() - $record[0] >1800 ) 
    return $check;
  
  return false;
}


