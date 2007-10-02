<?

function sess_open($save_path, $session_name)
{
  return(true);
}

function sess_close()
{
  return(true);
}

function sess_read($id)
{
  return (string) portalGetSessionData($id);
}

function sess_write($id, $sess_data)
{
  return portalPutSessionData($id, $sess_data);
}

function sess_destroy($id)
{
  return(true);
}

function sess_gc($maxlifetime)
{
  return true;
}

session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
?>
