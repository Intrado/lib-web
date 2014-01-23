<?php
namespace messagelink;

/**
 * Autogenerated by Thrift Compiler (0.9.1)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


class Info {
  static $_TSPEC;

  public $customerdisplayname = null;
  public $urlcomponent = null;
  public $timezone = null;
  public $jobname = null;
  public $jobdescription = null;
  public $jobstarttime = null;
  public $nummessageparts = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'customerdisplayname',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'urlcomponent',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'timezone',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'jobname',
          'type' => TType::STRING,
          ),
        6 => array(
          'var' => 'jobdescription',
          'type' => TType::STRING,
          ),
        7 => array(
          'var' => 'jobstarttime',
          'type' => TType::I32,
          ),
        8 => array(
          'var' => 'nummessageparts',
          'type' => TType::BYTE,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['customerdisplayname'])) {
        $this->customerdisplayname = $vals['customerdisplayname'];
      }
      if (isset($vals['urlcomponent'])) {
        $this->urlcomponent = $vals['urlcomponent'];
      }
      if (isset($vals['timezone'])) {
        $this->timezone = $vals['timezone'];
      }
      if (isset($vals['jobname'])) {
        $this->jobname = $vals['jobname'];
      }
      if (isset($vals['jobdescription'])) {
        $this->jobdescription = $vals['jobdescription'];
      }
      if (isset($vals['jobstarttime'])) {
        $this->jobstarttime = $vals['jobstarttime'];
      }
      if (isset($vals['nummessageparts'])) {
        $this->nummessageparts = $vals['nummessageparts'];
      }
    }
  }

  public function getName() {
    return 'Info';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->customerdisplayname);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->urlcomponent);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->timezone);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->jobname);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->jobdescription);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 7:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->jobstarttime);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 8:
          if ($ftype == TType::BYTE) {
            $xfer += $input->readByte($this->nummessageparts);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Info');
    if ($this->customerdisplayname !== null) {
      $xfer += $output->writeFieldBegin('customerdisplayname', TType::STRING, 1);
      $xfer += $output->writeString($this->customerdisplayname);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->urlcomponent !== null) {
      $xfer += $output->writeFieldBegin('urlcomponent', TType::STRING, 2);
      $xfer += $output->writeString($this->urlcomponent);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->timezone !== null) {
      $xfer += $output->writeFieldBegin('timezone', TType::STRING, 3);
      $xfer += $output->writeString($this->timezone);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->jobname !== null) {
      $xfer += $output->writeFieldBegin('jobname', TType::STRING, 5);
      $xfer += $output->writeString($this->jobname);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->jobdescription !== null) {
      $xfer += $output->writeFieldBegin('jobdescription', TType::STRING, 6);
      $xfer += $output->writeString($this->jobdescription);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->jobstarttime !== null) {
      $xfer += $output->writeFieldBegin('jobstarttime', TType::I32, 7);
      $xfer += $output->writeI32($this->jobstarttime);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->nummessageparts !== null) {
      $xfer += $output->writeFieldBegin('nummessageparts', TType::BYTE, 8);
      $xfer += $output->writeByte($this->nummessageparts);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class Logo {
  static $_TSPEC;

  public $contenttype = null;
  public $data = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'contenttype',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'data',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['contenttype'])) {
        $this->contenttype = $vals['contenttype'];
      }
      if (isset($vals['data'])) {
        $this->data = $vals['data'];
      }
    }
  }

  public function getName() {
    return 'Logo';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->contenttype);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->data);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Logo');
    if ($this->contenttype !== null) {
      $xfer += $output->writeFieldBegin('contenttype', TType::STRING, 1);
      $xfer += $output->writeString($this->contenttype);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->data !== null) {
      $xfer += $output->writeFieldBegin('data', TType::STRING, 2);
      $xfer += $output->writeString($this->data);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class Audio {
  static $_TSPEC;

  public $contenttype = null;
  public $data = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'contenttype',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'data',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['contenttype'])) {
        $this->contenttype = $vals['contenttype'];
      }
      if (isset($vals['data'])) {
        $this->data = $vals['data'];
      }
    }
  }

  public function getName() {
    return 'Audio';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->contenttype);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->data);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Audio');
    if ($this->contenttype !== null) {
      $xfer += $output->writeFieldBegin('contenttype', TType::STRING, 1);
      $xfer += $output->writeString($this->contenttype);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->data !== null) {
      $xfer += $output->writeFieldBegin('data', TType::STRING, 2);
      $xfer += $output->writeString($this->data);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class MessageLinkCodeNotFoundException extends TException {
  static $_TSPEC;


  public function __construct() {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        );
    }
  }

  public function getName() {
    return 'MessageLinkCodeNotFoundException';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('MessageLinkCodeNotFoundException');
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}


