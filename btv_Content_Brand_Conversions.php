<?php

require_once(DIR_CLASSES_ADMIN . 'brand_conversions_model.php');
require_once(DIR_CLASSES_ADMIN . 'products.php');
require_once('abstract_conversions.php');

/**
 * Class btv_Content_Brand_Conversions - - the implementation is taken out of context
 */
final class btv_Content_Brand_Conversions extends Conversions
{
    public $_module = 'brand_conversions';

    protected function setModel()
    {
        $this->model = new BrandConversionsModel();
    }

    public function indexConversions()
    {
        $conversions = $this->model->getConversions(null, $this->page, $this->search);

        $this->viewData['conversions'] = $conversions;
        $this->viewData['paging'] = $conversions->getBatchPageLinksAdmin('page', $this->getModule() . '&object=brand', false);

        $this->_page_contents = 'conversions_list.php';
        $this->_page_title = $this->language->get('heading_title');
    }

    public function editOrCreateConversionView()
    {
        $conversion = $this->model->getConversions($this->conversionId, $this->page, $this->search);

        $this->viewData['name'] = $conversion;

        $this->_page_contents = 'edit_conversion.php';

        if ($this->conversionId) {
            $this->_page_title = $this->language->get('heading_title_edit_vendor');
        } else {
            $this->_page_title = $this->language->get('heading_title_insert_vendor');
        }

        return true;
    }

    public function editOrCreateConversion()
    {
        if (!strlen(btv_Request::request('conversions_groups_name'))) {
            $this->messageStack->add_session($this->_module, $this->language->get('error_conversion_name_empty'), 'error');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=editOrCreateConversionView'));
        }

        $conversionId = $this->model->modifyConversion(btv_Request::request('conversions_groups_name'), $this->conversionId);

        if ($conversionId) {
            $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexConversions'));
        } else {
            $this->checkQuery();
        }
    }

    public function indexRules()
    {
        $rules = $this->model->getRules($this->conversionId, $this->search);
        $rules->setBatchLimit(!$this->page ? 1 : $this->page, MAX_DISPLAY_SEARCH_RESULTS);
        $rules->execute();

        $this->viewData['rules'] = $rules;
        $this->viewData['paging'] = $rules->getBatchPageLinksAdmin('page', $this->getModule() . '&action=indexRules&conversionId=' . $this->conversionId . '&page=' . $this->page . (!$this->search ? '' : '&search=' . $this->search), false);
        $this->viewData['pullDownPaging'] = $rules->getBatchPagesPullDownMenu('page', $this->getModule() . '&action=indexRules&conversionId=' . $this->conversionId . '&page=' . $this->page . (!$this->search ? '' : '&search=' . $this->search), false);
        $this->viewData['conversionId'] = $this->conversionId;

        $this->_page_title = $this->language->get('heading_title_edit_rules_vendor');
        $this->_page_contents = 'rules_list.php';
    }

    public function editOrCreateRuleView()
    {
        $detail = null;

        if ($this->ruleId) {
            $detail = $this->model->getRuleDetail($this->ruleId);
        }

        if (!empty($detail)) {
            $nextRule = $this->model->getRules($this->conversionId, '', $detail['external_value']);
            $nextRule->next();
            $this->viewData['nextRule'] = $nextRule->valueInt('conversions_id');
            $this->viewData['warehouseName'] = $this->getWarehouseName($detail['conversions_id'], $detail['external_value']);
        }

        $this->viewData['detail'] = $detail;
        $this->viewData['page'] = $this->page;
        $this->viewData['ruleId'] = $this->ruleId;
        $this->viewData['conversionId'] = $this->conversionId;
        $this->viewData['brandsList'] = $this->model->getBrandsList();
        $this->_page_contents = 'edit_rule.php';
        $this->_page_title = $this->language->get('action_heading_edit_rule_vendor');
    }

    public function editOrCreateRule()
    {
        if (!strlen($this->externalValue)) {
            $this->messageStack->add_session($this->_module, $this->language->get('error_rule_name_empty'), 'error');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=editOrCreateRuleView&conversionId=' . $this->conversionId . '&page=' . $this->page));
        }

        if ($last_id = $this->modifyRule()) {
            $this->messageStack->add_session($this->_module, $this->language->get('ms_success_action_performed'), 'success');
        } else {
            $this->checkQuery('&action=indexRules&conversionId=' . $this->conversionId . '&page=' . $this->page);
        }

        $this->conversionId = (empty($this->conversionId)) ? $last_id : $this->conversionId;

        if ($this->saveAndNext && $last_id) {
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=editOrCreateRuleView&conversionId=' . $this->conversionId . '&ruleId=' . $this->nextRuleId . '&page=' . $this->page));
        } else {
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId . '&page=' . $this->page));
        }
    }

    private function modifyRule()
    {
        if ($this->model->checkIfRuleExists($this->ruleId, $this->conversionId, $this->externalValue)) {
            $this->messageStack->add_session($this->_module, $this->language->get('error_rule_already_exists'), 'error');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=editOrCreateRuleView&conversionId=' . $this->conversionId . '&page=' . $this->page . '&ruleId=' . $this->ruleId));
        }

        if ($this->ruleId) {
            $this->model->updateRule($this->ruleId, $this->externalValue, $this->internalId);
        } else {
            $result = $this->model->insertRule($this->conversionId, $this->externalValue, $this->internalId);

            return $result;
        }

        return $this->saveAndNext && $this->nextRuleId ? $this->nextRuleId : true;
    }

    public function autoCreateRules()
    {
        $matched = $this->model->autoCreateRules($this->conversionId);

        if ($matched !== false) {
            $this->messageStack->add_session($this->_module, sprintf($this->language->get('message_conversions_added'), $matched['new']), 'success');
            $this->messageStack->add_session($this->_module, sprintf($this->language->get('message_conversions_matched'), $matched['total']), 'success');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId));
        } else {
            $this->messageStack->add_session($this->_module, $this->language->get('ms_error_action_not_performed'), 'error');
            btv_redirect_admin(btv_href_link_admin(FILENAME_DEFAULT, $this->_module . '&action=indexRules&conversionId=' . $this->conversionId));
        }
    }
}