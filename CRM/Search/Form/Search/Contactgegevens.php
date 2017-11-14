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

    // titularis
    $form->addElement('checkbox', 'titularis', 'apotheek (titularis)');
    $formElements[] = 'titularis';

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
      'Name' => 'sort_name',
    );

    if (CRM_Utils_Array::value('titularis', $this->_formValues)) {
      $columns['Is titularis van'] = 'titu_name';
      $columns['Titularis straat'] = 'titu_street';
      $columns['Titularis postcode'] = 'titu_postal_code';
      $columns['Titularis gemeente'] = 'titu_city';
    }

    if (CRM_Utils_Array::value('groothandel', $this->_formValues)) {
      $columns['Groothandel'] = 'grooth_name';
      $columns['Groothandel straat'] = 'grooth_street';
      $columns['Groothandel postcode'] = 'grooth_postal_code';
      $columns['Groothandel gemeente'] = 'grooth_city';
    }

    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    return $sql;
  }

  function select() {
    $select = "
      contact_a.sort_name
      , contact_a.id as contact_id
      , titu.display_name titu_name
      , titu_addr.street_address titu_street
      , titu_addr.postal_code titu_postal_code
      , titu_addr.city titu_city
      , grooth.display_name grooth_name
      , grooth_addr.street_address grooth_street
      , grooth_addr.postal_code grooth_postal_code
      , grooth_addr.city grooth_city
    ";

    return $select;
  }

  function from() {
    $reltypeTitularis = 35;
    $reltypeGroothandel = 60;

    $from = "
      FROM
        civicrm_contact contact_a
    ";

    // titularis
    $from .= "
      LEFT OUTER JOIN
        civicrm_relationship titu_rel ON titu_rel.contact_id_b = contact_a.id AND titu_rel.relationship_type_id = $reltypeTitularis
      LEFT OUTER JOIN
        civicrm_contact titu ON titu_rel.contact_id_a = titu.id
      LEFT OUTER JOIN
        civicrm_address titu_addr ON titu_addr.contact_id = titu.id AND titu_addr.location_type_id = 2
    ";

    // groothandel
    $from .= "
      LEFT OUTER JOIN
        civicrm_relationship grooth_rel ON grooth_rel.contact_id_a = titu.id AND grooth_rel.relationship_type_id = $reltypeGroothandel
      LEFT OUTER JOIN
        civicrm_contact grooth ON grooth_rel.contact_id_b = grooth.id
      LEFT OUTER JOIN
        civicrm_address grooth_addr ON grooth_addr.contact_id = grooth.id AND grooth_addr.location_type_id = 2
    ";

    return $from;
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
