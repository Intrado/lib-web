<?
/*CSDELETEMARKER_START*/
if (!$IS_COMMSUITE) {

function open($save_path, $session_name)
{
  return(true);
}

function close()
{
  return(true);
}

function read($id)
{
  return (string) getSessionData($id);
}

function write($id, $sess_data)
{
  return putSessionData($id, $sess_data);
}

function destroy($id)
{
  return(true);
}

function gc($maxlifetime)
{
  return true;
}

session_set_save_handler("open", "close", "read", "write", "destroy", "gc");
}

/*CSDELETEMARKER_END*/
?>
