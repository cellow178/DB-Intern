    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [
    //"nama field" => "float",
    ];
    const FIELD_VALIDATION_DATA = [];
    const CHILD_TABLE = [
    //"child_table" => [
    // "foreignField" => "field"
    //]
    ];
    const MAPPING_MULTIPLE_ADD = [
    //"contracts" => [ -- main table (contract_id)
    //    "dataIdTable" => "m_poc", -- data (mapping id)
    //    "fieldAdd" => [],
    //    "fieldUnique" => [],
    //],
    ];

    public static function beforeInsert($input)
    {
    return $input;
    }

    public static function afterInsert($object, $input)
    {
    return $input;
    }

    public static function beforeUpdate($input)
    {
    return $input;
    }

    public static function afterUpdate($object, $input)
    {
    return $input;
    }

    public static function beforeDelete($input)
    {
    return $input;
    }

    public static function afterDelete($object, $input)
    {
    return $input;
    }