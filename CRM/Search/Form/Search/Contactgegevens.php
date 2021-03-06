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

    // contact barcode field
    $form->add('text', 'contact_barcode', 'Barcode contact', TRUE);
    $formElements[] = 'contact_barcode';

    // postal code
    $form->add('text', 'apo_postal_code', 'Postcode(s) apotheek', TRUE);
    $formElements[] = 'apo_postal_code';

    // pharmacy
    $form->addElement('checkbox', 'pharmacy_details', 'Apotheekgegevens');
    $formElements[] = 'pharmacy_details';

    // groothandel
    $form->addElement('checkbox', 'groothandel', 'incl. Groothandel');
    $formElements[] = 'groothandel';

    // customer/member type
    $contactTypeChoices = array(
      '1' => 'enkel klanten',
      '2' => 'enkel leden',
      '3' => 'klanten + leden',
      '4' => 'alle contacten',
    );
    $form->addRadio('contact_type', 'Klant/lid', $contactTypeChoices);
    $formElements[] = 'contact_type';

    // tarifiering
    $tarifChoices = array(
      '1' => 'TD3 + TD1',
      '2' => 'alleen TD3',
      '3' => 'alleen TD1',
      '4' => 'n.v.t.',
    );
    $form->addRadio('tarif', 'TD', $tarifChoices);
    $formElements[] = 'tarif';

    $form->setDefaults(array(
      'pharmacy_details' => '1',
      'contact_type' => '3',
      'groothandel' => '1',
      'tarif' => '4',
    ));

    $form->assign('elements', $formElements);
  }

  function summary() {
    return NULL;
  }

  function &columns() {
    $columns = array(
      'Name' => 'sort_name',
      'Voornaam' => 'first_name',
      'Achternaam' => 'last_name',
      'Roepnaam' => 'nick_name',
      'Telefoon' => 'pers_phone',
      'E-mail' => 'pers_email',
      'Riziv-nr' => 'riziv_nummer_14',
      'Bandagistnr' => 'bandagistnummer_17',
      'Civi ID' => 'contact_id',
      'Lid?' => 'member',
      'Taal' => 'lang',
      'Barcode' => 'barcode',
      'Straat' => 'pers_street',
      'Extra adreslijn' => 'pers_supplemental_address_1',
      'Postcode' => 'pers_postal_code',
      'Gemeente' => 'pers_city',
    );

    // see if checkbox "apotheekgegevens" is set
    if (CRM_Utils_Array::value('pharmacy_details', $this->_formValues)) {
      $columns['Is titularis van'] = 'apo_name';
      $columns['Apo telefoon'] = 'apo_phone';
      $columns['Apo email'] = 'apo_email';
      $columns['APB'] = 'apb_number';
      $columns['Overname'] = 'overname_number';
      $columns['Ronde'] = 'round_number';
      $columns['TD'] = 'tarif_name';
      $columns['BTW-nummer'] = 'vat_number';
      $columns['Apo barcode'] = 'apo_barcode';
      $columns['Apo straat'] = 'apo_street';
      $columns['Apo extra adreslijn'] = 'apo_supplemental_address_1';
      $columns['Apo postcode'] = 'apo_postal_code';
      $columns['Apo gemeente'] = 'apo_city';

      $columns['Apo eigenaar voornaam'] = 'owner_first_name';
      $columns['Apo eigenaar achternaam'] = 'owner_last_name';
      $columns['Apo eigenaar roepnaam'] = 'owner_nick_name';
      $columns['Apo eigenaar barcode'] = 'owner_barcode';
    }

    if (CRM_Utils_Array::value('groothandel', $this->_formValues)) {
      $columns['Groothandel'] = 'grooth_name';
    }

    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    //die($sql);
    return $sql;
  }

  function select() {
    $select = "
      contact_a.sort_name
      , contact_a.first_name
      , contact_a.last_name
      , contact_a.nick_name
      , persphone.phone pers_phone
      , persemail.email pers_email
      , ca.riziv_nummer_14
      , ca.bandagistnummer_17
      , contact_a.id as contact_id
      , if(
          wk_rel.id IS NOT NULL
          , 'Werkend lid',
          if (
            mwk_rel.id IS NOT NULL
            , 'Meewerkend lid'
            , if (
              jaar_rel.id IS NOT NULL
              , '1 jaar afgestud. lid'
              , if (
                corr_rel.id IS NOT NULL
                , 'Corresponderend lid'
                , if (
                  ere_rel.id IS NOT NULL
                  , 'erelid'
                  , if (
                    afgest_rel.id IS NOT NULL
                    , 'afgestud. lid'
                    , '-'
                  )
                )
              )
            )
          )
        ) member
      , substring(contact_a.preferred_language, 1, 2) lang
      , ce.barcode_60 barcode
      , pers_addr.street_address pers_street
      , pers_addr.supplemental_address_1 pesr_supplemental_address_1
      , pers_addr.postal_code pers_postal_code
      , pers_addr.city pers_city

      , apo_org.btw_nummer_24 vat_number
      , apo_ce.barcode_60 apo_barcode
      , apo.display_name apo_name
      , apophone.phone apo_phone
      , apoemail.email apo_email
      , apo_addr.street_address apo_street
      , apo_addr.supplemental_address_1 apo_supplemental_address_1
      , apo_addr.postal_code apo_postal_code
      , apo_addr.city apo_city
      , uitb.rondenummer_38 round_number
      , uitb.apb_nummer_43 apb_number
      , uitb.overname_44 overname_number
      , grooth.display_name grooth_name
      , tarif.display_name tarif_name
      
      , owner.first_name owner_first_name
      , owner.last_name owner_last_name
      , owner.nick_name owner_nick_name
      , owner_ce.barcode_60 owner_barcode
    ";

    return $select;
  }

  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
    // dirty hack to make the counter work
    $sort->_vars[1]['name'] = 'contact_a.sort_name';
    $sort->_vars[2]['name'] = 'contact_a.contact_id';
    $sort->_vars[3]['name'] = 'contact_a.lang';
    $sql = $this->sql('contact_a.id as contact_id', $offset, $rowcount, $sort);
    $this->validateUserSQL($sql);

    if ($returnSQL) {
      return $sql;
    }

    return CRM_Core_DAO::composeQuery($sql, array());
  }

  function from() {
    $reltypeTitularis = 35;
    $reltypeGroothandel = 60;
    $reltypeTarifieringsdienst = 36;
    $reltypeOwner = 56;

    $reltype1jaarLid = 49;
    $reltypeAfgestudeerdLid = 47;
    $reltypeCorrespLid = 46;
    $reltypeEreLid = 42;
    $reltypeMeewerkendLid = 44;
    $reltypeWerkendLid = 43;

    $from = "
      FROM
        civicrm_contact contact_a
      LEFT OUTER JOIN
        civicrm_email persemail on persemail.contact_id = contact_a.id and persemail.is_primary = 1 
      LEFT OUTER JOIN
        civicrm_phone persphone on persphone.contact_id = contact_a.id and persphone.is_primary = 1 
      LEFT OUTER JOIN
        civicrm_value_contact_extra ce on ce.entity_id = contact_a.id
      LEFT OUTER JOIN
        civicrm_value_contact_apotheker ca on ca.entity_id = contact_a.id
      LEFT OUTER JOIN
        civicrm_address pers_addr ON pers_addr.contact_id = contact_a.id AND pers_addr.is_primary = 1        
    ";

    // apotheek & uitbating
    $from .= "
      LEFT OUTER JOIN
        civicrm_relationship apo_rel ON apo_rel.contact_id_b = contact_a.id AND apo_rel.is_active = 1 and apo_rel.relationship_type_id = $reltypeTitularis
      LEFT OUTER JOIN
        civicrm_contact apo ON apo_rel.contact_id_a = apo.id
      LEFT OUTER JOIN
        civicrm_address apo_addr ON apo_addr.contact_id = apo.id AND apo_addr.location_type_id = 2
      LEFT OUTER JOIN
        civicrm_email apoemail on apoemail.contact_id = apo.id and apoemail.is_primary = 1 
      LEFT OUTER JOIN
        civicrm_phone apophone on apophone.contact_id = apo.id and apophone.is_primary = 1         
      LEFT OUTER JOIN
        civicrm_value_contact_apotheekuitbating uitb ON uitb.entity_id = apo.id
      LEFT OUTER JOIN
        civicrm_value_contact_organisation apo_org ON apo_org.entity_id = apo.id
      LEFT OUTER JOIN
        civicrm_value_contact_extra apo_ce on apo_ce.entity_id = apo.id        
    ";

    // groothandel
    $from .= "
      LEFT OUTER JOIN
        civicrm_relationship grooth_rel ON grooth_rel.contact_id_a = apo.id AND grooth_rel.is_active = 1 and grooth_rel.relationship_type_id = $reltypeGroothandel
      LEFT OUTER JOIN
        civicrm_contact grooth ON grooth_rel.contact_id_b = grooth.id
    ";

    // eigenaar
    $from .= "
      LEFT OUTER JOIN
        civicrm_relationship owner_rel ON owner_rel.contact_id_a = apo.id AND owner_rel.is_active = 1 and owner_rel.relationship_type_id = $reltypeOwner
      LEFT OUTER JOIN
        civicrm_contact owner ON owner_rel.contact_id_b = owner.id
      LEFT OUTER JOIN
        civicrm_value_contact_extra owner_ce on owner_ce.entity_id = owner.id                
    ";

    // Tarifieringsdienst
    $from .= "
      LEFT OUTER JOIN
        civicrm_relationship tarif_rel ON tarif_rel.contact_id_a = apo.id AND tarif_rel.is_active = 1 and tarif_rel.relationship_type_id = $reltypeTarifieringsdienst
      LEFT OUTER JOIN
        civicrm_contact tarif ON tarif_rel.contact_id_b = tarif.id
    ";

    // members
    $from .= "
      LEFT OUTER JOIN
        civicrm_relationship jaar_rel ON jaar_rel.contact_id_a = contact_a.id AND jaar_rel.is_active = 1 and jaar_rel.relationship_type_id = $reltype1jaarLid
      LEFT OUTER JOIN
        civicrm_relationship afgest_rel ON afgest_rel.contact_id_a = contact_a.id AND afgest_rel.is_active = 1 and afgest_rel.relationship_type_id = $reltypeAfgestudeerdLid                        
      LEFT OUTER JOIN
        civicrm_relationship wk_rel ON wk_rel.contact_id_a = contact_a.id AND wk_rel.is_active = 1 and wk_rel.relationship_type_id = $reltypeWerkendLid
      LEFT OUTER JOIN
        civicrm_relationship mwk_rel ON mwk_rel.contact_id_a = contact_a.id AND mwk_rel.is_active = 1 and mwk_rel.relationship_type_id = $reltypeMeewerkendLid
      LEFT OUTER JOIN
        civicrm_relationship corr_rel ON corr_rel.contact_id_a = contact_a.id AND corr_rel.is_active = 1 and corr_rel.relationship_type_id = $reltypeCorrespLid        
      LEFT OUTER JOIN
        civicrm_relationship ere_rel ON ere_rel.contact_id_a = contact_a.id AND ere_rel.is_active = 1 and ere_rel.relationship_type_id = $reltypeEreLid                        
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
    $name = CRM_Utils_Array::value('contact_first_name', $this->_formValues);
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $params[$count] = array($name, 'String');
      $clause[] = "contact_a.first_name LIKE %{$count}";
      $count++;
    }

    // process last name
    $name = CRM_Utils_Array::value('contact_last_name', $this->_formValues);
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $params[$count] = array($name, 'String');
      $clause[] = "contact_a.last_name LIKE %{$count}";
      $count++;
    }

    // process barcode
    $barcode = CRM_Utils_Array::value('contact_barcode', $this->_formValues);
    if ($barcode != NULL) {
      $params[$count] = array($barcode, 'String');
      $clause[] = "ce.barcode_60 = %{$count}";
      $count++;
    }

    // postal code(s) - can be comma separated
    $postal_codes = CRM_Utils_Array::value('apo_postal_code', $this->_formValues);
    if ($postal_codes != NULL) {
      $postal_codes_arr = explode(',', $postal_codes);
      if (count($postal_codes_arr) > 1) {
        $c = 'apo_addr.postal_code in (';
        foreach ($postal_codes_arr as $k => $postal_code) {
          if ($k <> 0) {
            $c .= ',';
          }
          $c .= "'" . trim($postal_code) . "'";
        }
        $c .= ')';
        $clause[] = $c;
        $count++;
      }
      else {
        $clause[] = "apo_addr.postal_code = '" . trim($postal_codes_arr[0]) . "'";
        $count++;
      }
    }

    // contact type
    $contact_type = CRM_Utils_Array::value('contact_type', $this->_formValues);
    if ($contact_type != NULL) {
      if ($contact_type == 1) {
        // klanten
        $clause[] = 'tarif_rel.id IS NOT NULL';
      }
      else if ($contact_type == 2) {
        // leden
        $clause[] = '(wk_rel.id IS NOT NULL OR mwk_rel.id IS NOT NULL or jaar_rel.id IS NOT NULL or afgest_rel.id IS NOT NULL or corr_rel.id IS NOT NULL or ere_rel.id IS NOT NULL)';
      }
      else if ($contact_type == 3) {
        // klanten of leden
        $clause[] = '(tarif_rel.id IS NOT NULL OR wk_rel.id IS NOT NULL OR mwk_rel.id IS NOT NULL or jaar_rel.id IS NOT NULL or afgest_rel.id IS NOT NULL or corr_rel.id IS NOT NULL or ere_rel.id IS NOT NULL)';
      }
      else if ($contact_type == 4) {
        // iedereen
      }
      $count++;
    }

    // tarifiering
    $tarif = CRM_Utils_Array::value('tarif', $this->_formValues);
    if ($tarif != NULL) {
      if ($tarif == 1) {
        // TD1+TD3
        $clause[] = "tarif.display_name in ('TD1', 'TD3')";
      }
      else if ($tarif == 2) {
        // TD3
        $clause[] = "tarif.display_name = 'TD3'";
      }
      else if ($tarif == 3) {
        // TD1
        $clause[] = "tarif.display_name = 'TD1'";
      }
      else if ($tarif == 4) {
        //
      }
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

  public function count() {
    return parent::count(); // TODO: Change the autogenerated stub
  }

  function alterRow(&$row) {
  }
}
