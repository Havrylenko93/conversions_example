<?php
require_once(DIR_CLASSES_ADMIN . 'template.php');

/**
 * Class Conversions - the implementation is taken out of context
 */
abstract class Conversions extends btv_Template_Admin
{
    public $_page_title = '';
    public $_page_contents = '';
    protected $viewData = array();
    protected $model = null;
    protected $database = null;
    protected $language = null;
    protected $ruleId = null;
    protected $saveAndNext = null;
    protected $messageStack = null;
    protected $externalValue = null;
    protected $internalId = null;
    protected $nextRuleId = null;
    protected $batch = null;
    protected $conversionId = '';
    protected $search = '';
    protected $page = '';
    protected $action = '';

    abstract protected function setModel();

    public function __construct()
    {
        $this->setModel();
        $this->language = btv_Factory::singleton(DIR_UTILS . 'btv_Language_Admin');
        $this->database = btv_Factory::singleton('btv_Database_' . DB_DATABASE_CLASS);
        $this->messageStack = btv_Factory::singleton(DIR_UTILS . 'btv_MessageStack');
        $this->search = btv_Request::request('search', 'string');
        $this->page = btv_Request::request('page', 'string');
        $this->saveAndNext = btv_Request::request('save_and_next', 'int');
        $this->conversionId = btv_Request::request('conversionId', 'int');
        $this->batch = btv_Request::request('batch', 'array');
        $this->ruleId = btv_Request::request('ruleId', 'string');
        $this->externalValue = btv_Request::request('external_value');
        $this->nextRuleId = btv_Request::request('next_rule', 'int');
        $this->internalId = btv_Request::request('internal_id', 'string');
        $action = btv_Request::request('action', 'string');

        is_callable([$this, $action]) ? $this->$action() : btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, '404'));
    }

    public function getVariable($name)
    {
        $result = false;

        if (array_key_exists($name, $this->viewData)) {
            $result = $this->viewData[$name];
        }

        return $result;
    }

    protected function checkQuery($redirectLink = '')
    {
        $message = $this->model->databaseHasError();

        if ($message !== false) {
            $this->messageStack->add_session($this->_module, $message, 'error');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . $redirectLink));
        }
    }

    public function deleteConversion()
    {
        $this->model->deleteConversions($this->conversionId);
        $this->checkQuery('&action=indexConversions');

        $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');

        btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexConversions'));
    }

    public function batchDeleteConversions()
    {
        $this->model->deleteConversions($this->batch);
        $this->checkQuery('&action=indexConversions');

        $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');

        btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexConversions'));
    }

    public function deleteUnmappedRules()
    {
        if (!empty($this->conversionId)) {
            $this->model->deleteUnmappedRules($this->conversionId);

            $this->checkQuery('&action=indexRules&conversionId=' . $this->conversionId);
        }

        $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');

        btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId));
    }

    public function deleteRule()
    {
        $this->model->deleteRule($this->ruleId);
        $this->checkQuery('&action=indexRules&conversionId=' . $this->conversionId);
        $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');

        btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId));
    }

    public function batchDeleteRule()
    {
        $this->model->deleteRule($this->batch);
        $this->checkQuery('&action=indexRules');

        $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');

        btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId));
    }

    public function getWarehouseName($ruleId, $symbol)
    {
        if (intval($ruleId) === 0 || strpos($symbol, '___') === false) return false;

        $warehouseCode = reset(explode('___', $symbol));

        $result = $this->model->getWarehouseName($warehouseCode);

        return $result;
    }

    public function autoMatchRulesAction()
    {
        $matched = $this->model->autoMatchRules($this->conversionId);

        if ($matched === false) {
            $this->messageStack->add_session($this->_module, $this->language->get('ms_error_action_not_performed'), 'error');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId));
        }

        $this->messageStack->add_session($this->_module, sprintf($this->language->get('message_conversions_matched'), $matched), 'success');
        $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');

        btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId));
    }
}