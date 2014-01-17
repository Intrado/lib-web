<?php
/**
 * class FeedCategoryMapping
 *
 * Simple page to map feeds (ex RSS) to a CMA category,
 * mainly used to support Push Notifications
 *
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @date: 1/15/2014
 */

require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once('inc/table.inc.php');
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");

require_once('obj/Form.obj.php');
require_once('obj/FormItem.obj.php');
require_once("inc/formatters.inc.php");
require_once("obj/Validator.obj.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

require_once('obj/CmaApiClient.obj.php');


class FeedCategoryMapping extends PageForm {

    private $cmaApi;
    private $formName = 'feedcategorymapping';
    private $pageNav = 'admin:settings';
    private $pageTitle = 'Map Feed to CMA Category(s)';

    public $formdata;
    public $helpsteps;

    private $feedId;
    private $cmaCategories;


    public function __construct($cmaApi) {
        $this->cmaApi = $cmaApi;
        parent::__construct();
    }

    // @override
    public function isAuthorized(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
        global $USER;

        // TODO; add proper authorization check
        if (true) {
            return true;
        }
        return false;
    }

    // @override
    public function initialize() {
        // override some options set in PageBase
        $this->options["page"]  = $this->pageNav;
        $this->options["title"] = $this->pageTitle;
    }

    // @override
    public function beforeLoad(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
        if (isset($get['id']) && $get['id']) {
            $this->feedId = $get['id'];
        }
    }

    // @override
    public function load() {
        // TODO: fetch/query FeedCategory data based on $this->feedId, so we can set the Feed Name label in the form, etc

        // fetch CMA Categories
        $this->cmaCategories = $this->cmaApi->getCategories();
    }

    // @override
    public function afterLoad() {

        $this->setFormData();
        $this->form = new Form($this->formName, $this->formdata, $this->helpsteps, array( submit_button(_L(' Map Feed'), 'mapfeed', 'pictos/p1/16/59')));
        $this->form->ajaxsubmit = true;

        $this->form->handleRequest();
        if ($this->form->getSubmit()) {
            $postData = $this->form->getData();

            // TODO: handle response

            if ($this->form->isAjaxSubmit()) {
                $this->form->sendTo("editfeedcategory.php");
            } else {
                redirect("editfeedcategory.php");
            }
        }
    }

    // @override
    public function render() {
        $html = parent::render();
        return $html;
    }

    public function setFormData() {
        $categoryMap = array();
        foreach ($this->cmaCategories as $category) {
            $categoryMap[$category->id] = $category->name;
        }

        // define help steps used in form
        $this->helpsteps = array(
            _L('The name of the feed'),
            _L('Select 1 or more CMA Categories to map feed to'),
        );

        $this->formdata = array(
            _L("Select 1 or more CMA Categories to map feed"),
            "feedname" => array(
                "label" => _L('Feed Name'),
                'control' => array(
                    "FormHtml",
                    "html" => '<span style="font-size:14px; vertical-align:sub; font-weight:bold;">TODO: Feed Name Here</span>'
                ),
                "helpstep" => 1
            ),
            "cmacategories" => array(
                "label" => _L('CMA Categories'),
                "fieldhelp" => $this->helpsteps[1],
                "value" => '',
                "validators" => array(),
                "control" => array('MultiCheckBox', 'values' => $categoryMap),
                "helpstep" => 2
            ),
        );
    }
}

// Initialize FeedCategoryMapping and render page
// ================================================================

$cmaApi = new CmaApiClient(
    array(
        // TODO: use CMA API url from $SETTINGS once CMA API ready
        // 'apiClient' => new ApiClient($SETTINGS['cmaserver']['apiurl']),

        // use CMA api stub until CMA API ready
        'apiClient' => new ApiClient("https://{$_SERVER['SERVER_NAME']}/".customerUrlComponent().'/_cma_api_stub.php'),
        'appId' => getCustomerSystemSetting("_cmaappid") ? getCustomerSystemSetting("_cmaappid") : 1 // TODO: add appropriate logic/handling
    )
);
executePage(new FeedCategoryMapping($cmaApi));

?>