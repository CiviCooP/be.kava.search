<?php
use CRM_Search_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Search_Form_Search_Contactgegevens extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle('Contacten');

    $formElements = array();

    // contact first name field
    $form->add('text', 'contact_first_name', 'Voornaam contact', TRUE);
    $formElements[] = 'contact_first_name';

    // contact last name field
    $form->add('text', 'contact_last_name', 'Naam contact', TRUE);
    $formElements[] = 'contact_last_name';

    // groothandel
    $form->addElement('checkbox', 'groothandel', 'incl. Groothandel');
    $formElements[] = 'groothandel';

    $form->assign('elements', $formElements);
  }

  function summary() {
    return NULL;
  }

  function &columns() {
    // return by reference
    $columns = array(
      E::ts('Name') => 'sort_name',
      E::ts('Contact Id') => 'contact_id',
    );
    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    return $sql;
  }

  function select() {
    $select = "
      contact_a.sort_name,
      contact_a.id as contact_id  ,
    ";

    return $select;
  }

  function from() {
    return "
      FROM
        civicrm_contact contact_a
    ";
  }

  function where($includeContactIDs = FALSE) {
    $params = array();
    $where = "
      contact_a.contact_type = 'Individual'
      and contact_a.is_deleted = 0
      and contact_a.is_deceased = 0
    ";

    $count  = 1;
    $clause = array();

    // process first name
    $name   = CRM_Utils_Array::value('contact_first_name', $this->_formValues);
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $params[$count] = array($name, 'String');
      $clause[] = "contact_a.first_name LIKE %{$count}";
      $count++;
    }

    // process last name
    $name   = CRM_Utils_Array::value('contact_last_name', $this->_formValues);
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $params[$count] = array($name, 'String');
      $clause[] = "contact_a.last_name LIKE %{$count}";
      $count++;
    }

    if (!empty($clause)) {
      $where .= ' AND ' . implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function alterRow(&$row) {
  }
}
