<?php

class AftVerzendlijst
extends CRM_Contact_Form_Search_Custom_Base
implements CRM_Contact_Form_Search_Interface {

	function __construct(&$formValue) {
		$this->_columns = array(
			'Contact ID' => 'contact_id',
			'Naam' => 'naam',
			'Voornaam' => 'voornaam',
			'Barcode' => 'bar_code',
			'Straat' => 'verzend_straat',
			'Nr' => 'verzend_straat_nr',
			'Postcode' => 'verzend_post_code',
			'Gemeente' => 'verzend_gemeente',
			'Adresbijschrift' => 'adresbijschrift',
		);
	}

	public function buildForm(&$form) {
		$this->setTitle('Aanmaken AFT verzendlijst');
	}

	public function all($offset=0, $rowcount=0, $sort=NULL, $includeContactIDs=FALSE, $jutIDs=FALSE) {
		$select = "
			contact_a.id as contact_id,
      			case when contact_a.contact_type='Organization' then contact_a.display_name else contact_a.last_name end as naam, 
			contact_a.first_name as voornaam,
			barcode.barcode_60 as bar_code,
			adres.street_name as verzend_straat,
			trim(concat(adres.street_number, ' ', ifnull(adres.street_unit, ''))) as verzend_straat_nr,
			adres.postal_code as verzend_post_code,
			adres.city as verzend_gemeente,
			bijschrift.adresbijschrift_140 as adresbijschrift";

		$sql = $this->sql($select, $offset, $rowcount, $sort, $includeContactIDs, NULL);
		return $sql;
	}
	
	public function from() {
		$from = "
			from  civicrm_contact as contact_a
			join  civicrm_value_contact_extra as barcode on barcode.entity_id = contact_a.id
			left  join civicrm_address as adres on adres.contact_id = contact_a.id and adres.is_primary=1
			join  civicrm_membership as active_membership on (
		                contact_a.id = active_membership.contact_id
		                and active_membership.membership_type_id=2
		                and NOW() between active_membership.start_date and active_membership.end_date
		              )
			left  join civicrm_value_aft_abonnement_26 as bijschrift on 
				bijschrift.entity_id = active_membership.id";

		return $from;
	}

	public function where($includeContactIDs=FALSE) {
		$where = "
			not exists (
		        	select
	        		        *
			        from
			                civicrm_contact as titularis
			                join civicrm_membership as active_membership on (
			                        titularis.id = active_membership.contact_id
			                        and active_membership.membership_type_id=2
			                        and NOW() between active_membership.start_date and active_membership.end_date
			                )
			                join civicrm_relationship on (
			                        civicrm_relationship.contact_id_b = titularis.id
			                        and civicrm_relationship.relationship_type_id = 35
			                        and NOW() between civicrm_relationship.start_date and civicrm_relationship.end_date
			                )
			        where
			                civicrm_relationship.contact_id_a = contact_a.id
			)";

		return $where;
	}
}
?>
