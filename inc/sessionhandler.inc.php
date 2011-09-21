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
  global $SESSION_READONLY;
  if ($SESSION_READONLY === true)
    return (string) getSessionDataReadOnly($id);
  else
    return (string) getSessionData($id);
}

function sess_write($id, $sess_data)
{
  global $SESSION_READONLY;
  if ($SESSION_READONLY == true)
    return true;
  else
    return putSessionData($id, $sess_data);
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
