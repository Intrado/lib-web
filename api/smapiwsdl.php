<?
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));


header("Pragma: private");
header("Cache-Control: private");
header("Content-disposition: attachment; filename=smapi.wsdl");
header("Content-type: text");

echo '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<wsdl:definitions xmlns:sm="http://localhost/' . $CUSTOMERURL . '/api" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" name="SMAPI" targetNamespace="http://localhost/' . $CUSTOMERURL . '/api" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
    <wsdl:types><xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="http://localhost/' . $CUSTOMERURL . '/api">


	<xsd:complexType name="list">
		<xsd:sequence>
			<xsd:element name="id" type="xsd:int"></xsd:element>
			<xsd:element name="name" type="xsd:string"></xsd:element>
			<xsd:element name="description" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>

	<xsd:complexType name="ArrayofLists">
		<xsd:sequence>
			<xsd:element name="lists" type="sm:list" minOccurs="0" maxOccurs="unbounded"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>

	<xsd:complexType name="message">
		<xsd:sequence>
			<xsd:element name="id" type="xsd:int"></xsd:element>
			<xsd:element name="name" type="xsd:string"></xsd:element>
			<xsd:element name="description" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>

	<xsd:complexType name="ArrayofMessages">
		<xsd:sequence>
			<xsd:element name="messages" type="sm:message" minOccurs="0" maxOccurs="unbounded"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="jobtype">
		<xsd:sequence>
			<xsd:element name="id" type="xsd:int"></xsd:element>
			<xsd:element name="name" type="xsd:string"></xsd:element>
			<xsd:element name="info" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>

	<xsd:complexType name="ArrayofJobTypes">
		<xsd:sequence>
			<xsd:element name="jobtypes" type="sm:jobtype" minOccurs="0" maxOccurs="unbounded"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="ArrayofJobs">
		<xsd:sequence>
			<xsd:element name="jobs" type="sm:job" minOccurs="0" maxOccurs="unbounded"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>

	<xsd:complexType name="job">
		<xsd:sequence>
			<xsd:element name="id" type="xsd:int"></xsd:element>
			<xsd:element name="name" type="xsd:string"></xsd:element>
			<xsd:element name="description" type="xsd:string"></xsd:element>
			<xsd:element name="hasphone" type="xsd:string"></xsd:element>
			<xsd:element name="hasemail" type="xsd:string"></xsd:element>
			<xsd:element name="hasprint" type="xsd:string"></xsd:element>
			<xsd:element name="hassms" type="xsd:string"></xsd:element>
			<xsd:element name="phonetotal" type="xsd:string"></xsd:element>
			<xsd:element name="emailtotal" type="xsd:string"></xsd:element>
			<xsd:element name="printtotal" type="xsd:string"></xsd:element>
			<xsd:element name="smstotal" type="xsd:string"></xsd:element>
			<xsd:element name="phoneremaining" type="xsd:string"></xsd:element>
			<xsd:element name="emailremaining" type="xsd:string"></xsd:element>
			<xsd:element name="printremaining" type="xsd:string"></xsd:element>
			<xsd:element name="smsremaining" type="xsd:string"></xsd:element>
			<xsd:element name="startdate" type="xsd:string"></xsd:element>
			<xsd:element name="status" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>

	<xsd:complexType name="loginResult">
		<xsd:sequence>
			<xsd:element name="sessionid" type="xsd:string"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>

<xsd:complexType name="NewType"></xsd:complexType>
	<xsd:complexType name="getListsResult">
		<xsd:sequence>
			<xsd:element name="lists" type="sm:list" minOccurs="0" maxOccurs="unbounded"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="getMessagesResult">
		<xsd:sequence>
			<xsd:element name="messages" type="sm:message" minOccurs="0" maxOccurs="unbounded"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="setMessagebodyResult">
		<xsd:sequence>
			<xsd:element name="result" type="xsd:boolean"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="uploadAudioResult">
		<xsd:sequence>
			<xsd:element name="audioname" type="xsd:string"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="getJobTypesResult">
		<xsd:sequence>
			<xsd:element name="jobtypes" type="sm:jobtype" minOccurs="0" maxOccurs="unbounded"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="getJobsResult">
		<xsd:sequence>
			<xsd:element name="jobs" type="sm:job" minOccurs="0" maxOccurs="unbounded"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>


	<xsd:complexType name="getJobStatusResult">
		<xsd:sequence>
			<xsd:element name="job" type="sm:job"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType>
	<xsd:complexType name="sendJobResult">
		<xsd:sequence>
			<xsd:element name="jobid" type="xsd:int"></xsd:element>
			<xsd:element name="error" type="xsd:string"></xsd:element>
		</xsd:sequence>
	</xsd:complexType></xsd:schema></wsdl:types>
    <wsdl:message name="loginRequest">
    	<wsdl:part name="loginname" type="xsd:string"></wsdl:part>
    	<wsdl:part name="password" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="loginResponse">
    	<wsdl:part name="result" type="sm:loginResult"></wsdl:part>

    </wsdl:message>
    <wsdl:message name="getListsRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getListsResponse">
    	<wsdl:part name="result" type="sm:getListsResult"></wsdl:part>

    </wsdl:message>
    <wsdl:message name="getMessagesRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    	<wsdl:part name="type" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getMessagesResponse">


    	<wsdl:part name="result" type="sm:getMessagesResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="setMessageBodyRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    	<wsdl:part name="messageid" type="xsd:int"></wsdl:part>
    	<wsdl:part name="messagetext" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="setMessageBodyResponse">
    	<wsdl:part name="result" type="sm:setMessagebodyResult"></wsdl:part>

    </wsdl:message>
    <wsdl:message name="uploadAudioRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    	<wsdl:part name="name" type="xsd:string"></wsdl:part>
    	<wsdl:part name="audio" type="xsd:string"></wsdl:part>
    	<wsdl:part name="mimetype" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="uploadAudioResponse">


    	<wsdl:part name="result" type="sm:uploadAudioResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getJobTypesRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getJobTypesResponse">


    	<wsdl:part name="result" type="sm:getJobTypesResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getActiveJobsRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getActiveJobsResponse">


    	<wsdl:part name="result" type="sm:getJobsResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getRepeatingJobsRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getRepeatingJobsResponse">


    	<wsdl:part name="result" type="sm:getJobsResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="sendRepeatingJobRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    	<wsdl:part name="jobid" type="xsd:int"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="sendRepeatingJobResponse">


    	<wsdl:part name="result" type="sm:sendJobResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getJobStatusRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    	<wsdl:part name="jobid" type="xsd:int"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="getJobStatusResponse">


    	<wsdl:part name="result" type="sm:getJobStatusResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="sendJobRequest">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    	<wsdl:part name="name" type="xsd:string"></wsdl:part>
    	<wsdl:part name="description" type="xsd:string"></wsdl:part>
    	<wsdl:part name="listid" type="xsd:int"></wsdl:part>
    	<wsdl:part name="jobtypeid" type="xsd:int"></wsdl:part>

        <wsdl:part name="startdate" type="xsd:string"></wsdl:part>
        <wsdl:part name="starttime" type="xsd:string"></wsdl:part>
        <wsdl:part name="endtime" type="xsd:string"></wsdl:part>
        <wsdl:part name="daystorun" type="xsd:int"></wsdl:part>
        <wsdl:part name="phonemsgid" type="xsd:int"></wsdl:part>
        <wsdl:part name="emailmsgid" type="xsd:int"></wsdl:part>
    	<wsdl:part name="smsmsgid" type="xsd:int"></wsdl:part>


    	<wsdl:part name="maxcallattempts" type="xsd:int"></wsdl:part></wsdl:message>
    <wsdl:message name="sendJobResponse">


    	<wsdl:part name="result" type="sm:sendJobResult"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="login2Request">
    	<wsdl:part name="loginname" type="xsd:string"></wsdl:part>
    	<wsdl:part name="password" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="login2Response">
    	<wsdl:part name="sessionid" type="xsd:string"></wsdl:part>
    	<wsdl:part name="error" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="Request">
    	<wsdl:part name="Request" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:message name="Response">
    	<wsdl:part name="Response" type="xsd:string"></wsdl:part>
    </wsdl:message>
    <wsdl:portType name="SMClient">
    	<wsdl:operation name="login">
    		<wsdl:documentation>Given a valid loginname/password, a session id is generated and passed back.
If an error occurs, error will contain the error and sessionid will be empty string.

login:
	params: string loginname, string password
	returns: string error, string sessionid

</wsdl:documentation>
    		<wsdl:input message="sm:loginRequest"></wsdl:input>
    		<wsdl:output message="sm:loginResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="getLists">
    		<wsdl:documentation>Given a valid sessionid, an array of lists will be returned.
If only 1 list is found, a single list will be returned.
If error occurs, error will contain error string and lists will not be set.

getLists:
	params: string sessionid
	returns:
		lists: array of lists or a single list
		error: string</wsdl:documentation>
    		<wsdl:input message="sm:getListsRequest"></wsdl:input>
    		<wsdl:output message="sm:getListsResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="getMessages">
    		<wsdl:documentation>Given a valid sessionid and a valid message type, an array of messages will be returned.
If only 1 message is found, a single message will be returned.
If error occurs, error will contain error string and messages will not be set.

Valid messages types are:
	phone
	email
	sms

getMessages:
	params: string sessionid, string message type
	returns:
		messages: array of messages or a single message
		error: string
</wsdl:documentation>
    		<wsdl:input message="sm:getMessagesRequest"></wsdl:input>
    		<wsdl:output message="sm:getMessagesResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="setMessageBody">
    		<wsdl:documentation>Given a valid sessionid, messageid and message text, the specified message
will have its text replaced.  If successful, result will be true.
If error occurs, error will contain error string and result will be false.

setMessageBody:
	params: string sessionid, int message id, string message text
	returns: boolean result, string error
</wsdl:documentation>
    		<wsdl:input message="sm:setMessageBodyRequest"></wsdl:input>
    		<wsdl:output message="sm:setMessageBodyResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="uploadAudio">
    		<wsdl:documentation>Given a valid sessionid, name, mimetype, and audio file,
an audio file record will be generated and its resulting
name returned.
If an error occurs, error will contain the error string and audioname will be empty.

IMPORTANT: the audio data should be a base64 encoded string

uploadAudio:
	params: string sessionid, string name, string mimetype, base64 encoded string audio
	return:
		audioname: string
		error: string
</wsdl:documentation>
    		<wsdl:input message="sm:uploadAudioRequest"></wsdl:input>
    		<wsdl:output message="sm:uploadAudioResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="getJobTypes">
    		<wsdl:documentation>Given a valid sessionid, an array of jobtypes or a single jobtype will be returned.
If an error occurs, error will contain the error string and jobtypes will not be set.

getJobTypes:
	params: string sessionid
	return:
		jobtypes: array of jobtypes or a jobtype
		error: string
</wsdl:documentation>
    		<wsdl:input message="sm:getJobTypesRequest"></wsdl:input>
    		<wsdl:output message="sm:getJobTypesResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="getActiveJobs">
    		<wsdl:documentation>Given a valid sessionid, an array of jobs or a single job will be returned.
If an error occurs, error will contain the error string and jobs will not be set.

getActiveJobs:
params: string sessionid
return:
	jobs: array of job objects or a job object,
	error: string
</wsdl:documentation>
    		<wsdl:input message="sm:getActiveJobsRequest"></wsdl:input>
    		<wsdl:output message="sm:getActiveJobsResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="getRepeatingJobs">
    		<wsdl:documentation>Given a valid sessionid, an array of jobs or a single job will be returned.
If an error occurs, error will contain the error string and jobs will not be set.

getRepeatingJobs:
	params: string sessionid
	return:
		jobs: array of job objects or a job object,
		error: string
</wsdl:documentation>
    		<wsdl:input message="sm:getRepeatingJobsRequest"></wsdl:input>
    		<wsdl:output message="sm:getRepeatingJobsResponse"></wsdl:output>
        </wsdl:operation>
    	<wsdl:operation name="sendRepeatingJob">
    		<wsdl:documentation>Given a valid sessionid and jobid, the active job\'s id will be returned
If an error occurs, error will contain the error string and jobid will be 0.

sendRepeatingJob:
	params: string sessionid
	return: int jobid, string error
</wsdl:documentation>
    		<wsdl:input message="sm:sendRepeatingJobRequest"></wsdl:input>
    		<wsdl:output message="sm:sendRepeatingJobResponse"></wsdl:output>
    	</wsdl:operation>
    	<wsdl:operation name="getJobStatus">
    		<wsdl:documentation>Given a valid sessionid and jobid, a single job will be returned.
If an error occurs, error will contain the error string and job will not be set.

getJobStatus:
	params: string sessionid
	return:
		job: a job object,
		error: string</wsdl:documentation>
    		<wsdl:input message="sm:getJobStatusRequest"></wsdl:input>
    		<wsdl:output message="sm:getJobStatusResponse"></wsdl:output>
    	</wsdl:operation>
    	<wsdl:operation name="sendJob">
    		<wsdl:documentation>Given a valid sessionid, name, description, listid, jobtypeid,
startdate, starttime, endtime, number of days to run, optional phone message id,
optional email id, optional sms message id, and max call attempts,
a job will be created and set to active.  The job id will be returned.
If an error occurs, error will contain the error string and jobid will be 0.

sendJob:
	params: string sessionid
			string name
			string description
			int listid
			int jobtypeid
			string startdate
			string starttime
			string endtime
			int number of days to run
			int phone message id
			int email message id
			int sms message id
			int max call attempts
	return: int jobid, string error
</wsdl:documentation>
    		<wsdl:input message="sm:sendJobRequest"></wsdl:input>
    		<wsdl:output message="sm:sendJobResponse"></wsdl:output>
    	</wsdl:operation>

    	    </wsdl:portType>
    <wsdl:binding name="SMBinding" type="sm:SMClient">

    	<soap:binding style="rpc"
    		transport="http://schemas.xmlsoap.org/soap/http" />
    	<wsdl:operation name="login">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/login" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="getLists">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/getLists" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="getMessages">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/getMessages" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="setMessageBody">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/setMessageBody" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="uploadAudio">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/uploadAudio" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="getJobTypes">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/getJobTypes" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="getActiveJobs">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/getActiveJobs" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="getRepeatingJobs">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/getRepeatingJobs" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>

    	</wsdl:operation>
    	<wsdl:operation name="sendRepeatingJob">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/sendRepeatingJob" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>
    	</wsdl:operation>
    	<wsdl:operation name="getJobStatus">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/getJobStatus" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>
    	</wsdl:operation>
    	<wsdl:operation name="sendJob">

    		<soap:operation
    			soapAction="http://localhost/' . $CUSTOMERURL . '/api/sendJob" />
    		<wsdl:input>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:input>
    		<wsdl:output>

    			<soap:body use="literal"
    				namespace="http://localhost/' . $CUSTOMERURL . '/api" />
    		</wsdl:output>
    	</wsdl:operation>

    </wsdl:binding>
    <wsdl:service name="SMAPI">
		<wsdl:port name="SMAPIPort" binding="sm:SMBinding">
			<soap:address location="http://localhost/' . $CUSTOMERURL . '/api/smapi.php"></soap:address>
		</wsdl:port>
	</wsdl:service></wsdl:definitions>

';
?>