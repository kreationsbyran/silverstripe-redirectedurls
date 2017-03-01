<?php

/**
 * Specifies one URL redirection
 *
 * This file has been hacked to enable for domain/language-specific redirects.
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURL extends DataObject implements PermissionProvider
{

    private static $singular_name = 'Redirected URL';

    private static $db = array(
        'FromBase' => 'Varchar(255)',
        'FromQuerystring' => 'Varchar(255)',
        'To' => 'Varchar(255)',
        'Locale' => 'Varchar(255)' //Renamed from "Country"
    );

    private static $has_one = array(
        'Subsite' => 'Subsite'
    );

    private static $indexes = array(
        'From' => array(
            'type' => 'unique',
            'value' => '"FromBase","FromQuerystring"',
        )
    );

    private static $summary_fields = array(
        'FromBase' => 'From URL base',
        'FromQuerystring' => 'From URL query parameters',
        'To' => 'To URL',
        'Region' => 'Region',
        'SubsiteTitle' => 'Subsite'
    );

    private static $searchable_fields = array(
        'FromBase',
        'FromQuerystring',
        'To',
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fromBaseField = $fields->fieldByName('Root.Main.FromBase');
        $fromBaseField->setDescription('e.g. /about-us.html');

        $fromQueryStringField = $fields->fieldByName('Root.Main.FromQuerystring');
        $fromQueryStringField->setDescription('e.g. page=1&num=5');

        $toField = $fields->fieldByName('Root.Main.To');
        $toField->setDescription('e.g. /about?something=5');

        $locales = Translatable::get_allowed_locales();
        foreach ($locales as $locale) {
            $dropdownData[$locale] = Locale::getDisplayRegion($locale);
        }

        $dropdownField = DropdownField::create(
            'Locale',
            'Region',
            $dropdownData
        );
        $fields->addFieldToTab('Root.Main', $dropdownField);

        if (class_exists('Subsite')) {
            $subsites = Subsite::all_sites();
            if ($subsites->exists()) {
                foreach ($subsites as $subsite) {
                    $subsiteData[$subsite->ID] = $subsite->Title;
                }

                $dropdownField = DropdownField::create(
                    'SubsiteID',
                    'Subsite',
                    $subsiteData
                );
                $fields->addFieldToTab('Root.Main', $dropdownField);
            }
        }

        return $fields;
    }

    /*public function populateDefaults() {
        $defaultLocales = Translatable::get_allowed_locales();
        if(isset($defaultLocales[0])){
            $this->Locale = $defaultLocales[0];
        }

        parent::populateDefaults();
    }*/

    public function getSubsiteTitle(){
        if (!class_exists('Subsite')) {
            return '';
        }
        return $this->Subsite() && $this->Subsite()->ID != 0 ? $this->Subsite()->Title : _t('admin.mainSiteTitle', 'Main site');
    }

    public function getRegion()
    {

        if ($this->Locale) {
            return Locale::getDisplayRegion($this->Locale);
        }
        return _t('admin.noRegion', 'None set');

    }

    public function setFrom($val)
    {
        if (strpos($val, '?') !== false) {
            list($base, $querystring) = explode('?', $val, 2);
        } else {
            $base = $val;
            $querystring = null;
        }
        $this->setFromBase($base);
        $this->setFromQuerystring($querystring);
    }

    public function getFrom()
    {
        $url = $this->FromBase;
        if ($this->FromQuerystring) {
            $url .= "?" . $this->FromQuerystring;
        }
        return $url;
    }

    public function setFromBase($val)
    {
        if ($val[0] != '/') {
            $val = "/$val";
        }
        if ($val != '/') {
            $val = rtrim($val, '/');
        }
        $val = rtrim($val, '?');
        $this->setField('FromBase', $val);
    }

    public function setFromQuerystring($val)
    {
        $val = rtrim($val, '?');
        $this->setField('FromQuerystring', $val);
    }

    public function setTo($val)
    {
        $val = rtrim($val, '?');
        if ($val != '/') {
            $val = rtrim($val, '/');
        }
        $this->setField('To', $val);
    }


    /**
     * Helper for bulkloader {@link: RedirectedURLAdmin.getModelImporters}
     *
     * @param string $from The From URL to search
     * @return DataObject {@link: RedirectedURL}
     */
    public function findByFrom($from)
    {
        if ($from[0] != '/') {
            $from = "/$from";
        }
        $from = rtrim($from, '?');

        if (strpos($from, '?') !== false) {
            list($base, $querystring) = explode('?', $from, 2);

        } else {
            $base = $from;
            $querystring = null;
        }

        $SQL_base = Convert::raw2sql($base);
        $SQL_querystring = Convert::raw2sql($querystring);

        if ($querystring) {
            $qsClause = "AND \"FromQuerystring\" = '$SQL_querystring'";
        } else {
            $qsClause = "AND \"FromQuerystring\" IS NULL";
        }

        return DataObject::get_one("RedirectedURL", "\"FromBase\" = '$SQL_base' $qsClause");
    }

    public function providePermissions()
    {
        return array(
            'REDIRECTEDURLS_CREATE' => array(
                'name' => 'Create a redirect',
                'category' => 'Redirects'
            ),
            'REDIRECTEDURLS_EDIT' => array(
                'name' => 'Edit a redirect',
                'category' => 'Redirects',
            ),
            'REDIRECTEDURLS_DELETE' => array(
                'name' => 'Delete a redirect',
                'category' => 'Redirects',
            )
        );
    }

    public function canView($member = null)
    {
        return true;
    }

    public function canCreate($member = null)
    {
        return Permission::check('REDIRECTEDURLS_CREATE');
    }

    public function canEdit($member = null)
    {
        return Permission::check('REDIRECTEDURLS_EDIT');
    }

    public function canDelete($member = null)
    {
        return Permission::check('REDIRECTEDURLS_DELETE');
    }

}
