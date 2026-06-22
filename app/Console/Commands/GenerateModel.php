<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:model {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info("Generating Model");
        $tableArgument = $this->argument('table');
        if (env("DB_CONNECTION") == "pgsql") {
            $tables = DB::select("
            SELECT table_name FROM information_schema.tables 
            WHERE table_catalog = '" .  env("DB_DATABASE")  . "' AND table_schema='public'
            AND table_name NOT IN ( 'mapping_roles_tasks', 'jobs',
                'migrations', 'password_resets', 'failed_jobs')");
        } else if (env("DB_CONNECTION") == "mysql") {
            $tables = DB::select("
            SELECT table_name FROM information_schema.tables 
            WHERE table_schema = '" .  env("DB_DATABASE")  . "' AND table_name NOT IN ('mapping_roles_tasks', 'jobs',
                'migrations', 'password_resets', 'failed_jobs')");
        }

        $prefix = ["fi_", "in_", "pu_", "sl_"];
        $fieldLeanguageID = [];
        $fieldLeanguageEN = [];
        $modulLeanguageID = [];
        $modulLeanguageEN = [];
        $modulLeanguage = [];
        foreach ($tables as $table) {
            if (!is_null($tableArgument) && $table->table_name != $tableArgument) continue;
            $tableNameOriginal = $table->table_name;
            $tableName = $table->table_name;
            foreach ($prefix as $pre) {
                if (substr($tableName, 0, strlen($pre)) == $pre) {
                    $tableName = str_replace($pre, "", $tableName);
                }
            }
            $modulLeanguage = ucwords(str_replace("_", " ", $tableName));
            $modulLeanguageID[$tableName] =
                __("modul." . $tableName, [], "id") == "modul." . $tableName ?
                $modulLeanguage : __("modul." . $tableName, [], "id");

            $modulLeanguageEN[$tableName] =
                __("modul." . $tableName, [], "en") == "modul." . $tableName ?
                $modulLeanguage : __("modul." . $tableName, [], "en");

            $modelName = Str::ucfirst(Str::camel($tableName));
            $fileName = base_path("app/Models/" . $modelName . ".php");
            if (env("DB_CONNECTION") == "pgsql") {
                $sql = "
                WITH summary_fk AS (
                    select kcu.table_name as foreign_table,
                    rel_kcu.table_name as primary_table,
                    kcu.column_name as fk_column,
                    rel_kcu.column_name as pk_column,
                    pgc.confdeltype,
                    kcu.constraint_name
                from information_schema.table_constraints tco
                join information_schema.key_column_usage kcu
                        on tco.constraint_schema = kcu.constraint_schema
                        and tco.constraint_name = kcu.constraint_name
                join information_schema.referential_constraints rco
                        on tco.constraint_schema = rco.constraint_schema
                        and tco.constraint_name = rco.constraint_name
                join information_schema.key_column_usage rel_kcu
                        on rco.unique_constraint_schema = rel_kcu.constraint_schema
                        and rco.unique_constraint_name = rel_kcu.constraint_name
                        and kcu.ordinal_position = rel_kcu.ordinal_position
                join pg_constraint pgc ON pgc.conname = kcu.constraint_name
                where tco.constraint_type = 'FOREIGN KEY' AND kcu.constraint_catalog='" . env("DB_DATABASE") . "'
                    AND kcu.table_name = '$tableNameOriginal'
                order by kcu.table_schema,
                        kcu.table_name,
                        kcu.ordinal_position
                ), summary_comment AS (
                    SELECT
                        cols.column_name, cols.table_name, (
                            SELECT
                                pg_catalog.col_description(c.oid, cols.ordinal_position::int)
                            FROM
                                pg_catalog.pg_class c
                            WHERE
                                c.oid = (SELECT ('\"' || cols.table_name || '\"')::regclass::oid)
                                AND c.relname = cols.table_name
                        ) AS column_comment
                    FROM
                        information_schema.columns cols
                    WHERE
                        cols.table_catalog    = '" . env("DB_DATABASE") . "'
                        AND cols.table_name   = '$tableNameOriginal'
                ) SELECT A.column_name, A.data_type, A.character_maximum_length, 
                B.primary_table AS ref_table, B.pk_column AS ref_column, C.column_comment, 
                A.is_nullable, A.column_default
                FROM information_schema.columns A 
                LEFT JOIN summary_fk B ON B.foreign_table = A.table_name AND 
                    B.fk_column = A.column_name 
                LEFT JOIN summary_comment C ON C.table_name = A.table_name AND C.column_name = A.column_name 
                WHERE A.table_catalog = '" . env("DB_DATABASE") . "' AND A.table_name = '$tableNameOriginal'";

                $sqlIndex = "select
                    i.relname as index_name,
                    string_agg(a.attname, ',') as column_list,
                    regexp_replace(regexp_replace(i.relname, '[^[:alnum:]]+', ' ', 'g'), '^.* ', '','g') as index_type
                from
                    pg_class t,
                    pg_class i,
                    pg_index ix,
                    pg_attribute a,
                    pg_namespace n 
                where
                    t.oid = ix.indrelid
                    and i.oid = ix.indexrelid
                    and a.attrelid = t.oid
                    and i.relnamespace = n.oid
                    and a.attnum = ANY(ix.indkey)
                    and ix.indisprimary = false
                    and t.relkind = 'r'
                    and n.nspname = 'public'
                    and t.relname = '" . $tableNameOriginal . "'
                group by 
                    i.relname, t.relname
                order by
                    t.relname,
                    i.relname
                ";
            } elseif (env("DB_CONNECTION") == "mysql") {
                $sql = "SELECT A.column_name, A.data_type, A.character_maximum_length, B.ref_table, B.ref_column, A.column_comment,
                A.is_nullable, A.column_default
                FROM information_schema.columns A
            LEFT JOIN (
                SELECT table_name,column_name, REFERENCED_TABLE_NAME AS ref_table, REFERENCED_COLUMN_NAME AS ref_column
                  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                  WHERE table_schema='" . env("DB_DATABASE") . "' AND table_name = '$tableNameOriginal' AND REFERENCED_TABLE_NAME IS NOT NULL
            ) B ON B.table_name = A.table_name AND B.column_name = A.column_name
            WHERE A.table_name = '$tableNameOriginal' AND A.table_schema = '" . env("DB_DATABASE") . "'";

                $sqlIndex = "select index_name,
                    group_concat(column_name order by seq_in_index) as column_list
                from information_schema.statistics
                    where index_schema = '" . env("DB_DATABASE") . "' AND non_unique = 0 AND index_name <> 'PRIMARY' AND table_name = '$tableNameOriginal'
                group by index_name";
            }

            $fields = DB::select($sql);
            $uniques = DB::select($sqlIndex);
            $fillableBackList = ["id", "created_at", "updated_at"];
            $filedPattern = [
                'imgField' => 'img_',
                'docField' => 'doc_',
                'fileField' => 'file_',
                'arrayField' => 'array_'
            ];
            $fileRoot = "/" . $tableName;
            $fieldList = [];
            $fieldAdd = [];
            $fieldEdit = [];
            $fieldView = [];
            $fieldReadonly = [];
            $fieldFilterable = [];
            $fieldSearchable = [];
            $fieldSortable = [];
            $fieldType = [];
            $fieldUnique = [];
            $fieldValidation = [];
            $fieldRelation = [];
            $fieldArray = [];
            $fieldUpload = [];
            $parentChild = [];
            $fieldDefaultValue = [];
            $customSelect = "";
            foreach ($uniques as $unique) {
                if ($unique->index_type == 'unique') {
                    array_push($fieldUnique, explode(",", $unique->column_list));
                }
            }
            $aTincrement = 0;
            foreach ($fields as $field) {

                $fieldLeanguageID[$field->column_name] =
                    __("field." . $field->column_name, [], "id") == "field." . $field->column_name ?
                    ucwords(str_replace("_", " ", $field->column_name)) : __("field." . $field->column_name, [], "id");

                $fieldLeanguageEN[$field->column_name] =
                    __("field." . $field->column_name, [], "en") == "field." . $field->column_name ?
                    ucwords(str_replace("_", " ", $field->column_name)) : __("field." . $field->column_name, [], "en");

                array_push($fieldList, $field->column_name);
                if (!in_array($field->column_name, $fillableBackList))
                    array_push($fieldAdd, $field->column_name);

                if (!in_array($field->column_name, $fillableBackList) && $field->column_name != "created_by")
                    array_push($fieldEdit, $field->column_name);

                array_push($fieldView, $field->column_name);
                array_push($fieldSortable, $field->column_name);

                if ($field->data_type == "character varying" and $field->column_name != 'status_code')
                    array_push($fieldSearchable, $field->column_name);

                if (!in_array($field->column_name, $fillableBackList)) {
                }
                //
                $operator = "=";
                $fieldFilterable[$field->column_name] =  [
                    "operator" => $operator
                ];
                //
                $fieldType[$field->column_name] = $field->data_type;

                if ($field->column_name !== 'id') $fieldDefaultValue[$field->column_name] = $field->column_default;


                if ($field->column_name !== 'id') {
                    $fieldValidation[$field->column_name] = ($field->is_nullable == "YES") ? "nullable" : "required";

                    if ($field->data_type == "bigint" || $field->data_type == "integer")
                        $fieldValidation[$field->column_name] .= "|integer";

                    if ($field->data_type == "timestamp with time zone" || $field->data_type == "timestamp")
                        $fieldValidation[$field->column_name] .= "|date";

                    if ($field->data_type == "character varying" || $field->data_type == "text")
                        $fieldValidation[$field->column_name] .= "|string";

                    if ($field->character_maximum_length != null)
                        $fieldValidation[$field->column_name] .= "|max:" . $field->character_maximum_length;
                }

                // START FIELD UPLOAD
                // $pattern = array_keys($filedPattern);
                foreach ($filedPattern as $c => $val) {
                    if (preg_match("/" . $val . "/i", $field->column_name)) {
                        switch ($c) {
                            case 'imgField':
                                $fieldUpload[] = $field->column_name;
                                $fieldValidation[$field->column_name] .= "|exists_file";
                                break;
                            case 'docField':
                                $fieldUpload[] = $field->column_name;
                                $fieldValidation[$field->column_name] .= "|exists_file";
                                break;
                            case 'fileField':
                                $fieldUpload[] = $field->column_name;
                                $fieldValidation[$field->column_name] .= "|exists_file";
                                break;
                            case 'arrayField':
                                $fieldArray[] = $field->column_name;
                                break;
                        }
                    }
                }
                // $this->info($fieldUpload);
                // END FIELD UPLOAD
                //

                //
                $relationSelectedFields = ["id"];
                if ($field->ref_table != null) {
                    $aliasTable = toAlpha($aTincrement + 1);
                    $aliasDisplayName = "rel_" . $field->column_name;
                    $fieldRelation[$field->column_name] =  [
                        "linkTable" => $field->ref_table,
                        "aliasTable" => $aliasTable,
                        "linkField" => $field->ref_column,
                        "displayName" => $aliasDisplayName,
                        "selectFields" => $relationSelectedFields,
                        "selectValue" => "id AS " . $aliasDisplayName
                    ];

                    if ($field->column_name == "created_by") {
                        $fieldRelation[$field->column_name]["selectFields"] = ["username"];
                    }

                    if ($field->column_name == "updated_by") {
                        $fieldRelation[$field->column_name]["selectFields"] = ["username"];
                    }
                    $aTincrement++;
                }
            }
            $beforeInsert = "\n        return \$input;\n    ";
            $beforeUpdate = "\n        return \$input;\n    ";
            $afterInsert = "\n    ";
            $afterUpdate = "\n    ";
            $customContent = "\n    " . view("generate.custom");
            $params = [
                'fileRoot' => $fileRoot,
                'list' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
                'view' => true,
                'fieldList' => $fieldList,
                'fieldAdd' => $fieldAdd,
                'fieldEdit' => $fieldEdit,
                'fieldView' => $fieldView,
                'fieldReadonly' => $fieldReadonly,
                'fieldFilterable' => $fieldFilterable,
                'fieldSearchable' => $fieldSearchable,
                'fieldUnique' => $fieldUnique,
                'fieldSortable' => $fieldSortable,
                'fieldType' => str_replace(" ", "_", $fieldType),
                'parentChild' => $parentChild,
                'fieldValidation' => $fieldValidation,
                'fieldRelation' => $fieldRelation,
                'fieldDefaultValue' => $fieldDefaultValue,
                'fieldArray' => $fieldArray,
                'fieldUpload' => $fieldUpload,
                'table_name' => $tableNameOriginal,
                'studly_caps' => Str::ucfirst(Str::camel($tableName)),
                'customContent' => $customContent,
                'customSelect' => $customSelect
            ];

            if (!is_file($fileName)) {

                $fileContent = view('generate.model', $params);
                file_put_contents($fileName, "<?php \n\n" . $fileContent);

                $this->info("Success Generate Model " . $modelName);
            } else {
                $contents = file_get_contents($fileName);
                // echo $contents;
                $classModel = "\\App\\Models\\" . $modelName;
                $customContent = get_string_between($contents, "// start custom", "// end custom");

                preg_match_all("/public static function beforeInsert\([\$]input\)\n    {([^}]*)}/", $contents, $matches);
                // $beforeInsert = $matches[1][0];
                preg_match_all("/public static function afterInsert\([\$]object, [\$]input\)\n    {([^}]*)}/", $contents, $matches);
                // $afterInsert = $matches[1][0];
                preg_match_all("/public static function beforeUpdate\([\$]input\)\n    {([^}]*)}/", $contents, $matches);
                // $beforeUpdate = $matches[1][0];
                preg_match_all("/public static function afterUpdate\([\$]object, [\$]input\)\n    {([^}]*)}/", $contents, $matches);
                // $afterUpdate = $matches[1][0];

                // Berubah Select Value Field Relation by coding
                foreach ($fieldRelation as $key => $relation) {
                    if (isset($classModel::FIELD_RELATION[$key]["selectFields"])) {
                        $fieldRelation[$key]["selectFields"] = $classModel::FIELD_RELATION[$key]["selectFields"];
                    }

                    if (isset($classModel::FIELD_RELATION[$key]["selectValue"])) {
                        $fieldRelation[$key]["selectValue"] = $classModel::FIELD_RELATION[$key]["selectValue"];
                    }
                }

                $customSelect = defined("$classModel::CUSTOM_SELECT") ? $classModel::CUSTOM_SELECT : $customSelect;

                $params = [
                    'fileRoot' => $fileRoot,
                    'list' => $classModel::IS_LIST,
                    'add' => $classModel::IS_ADD,
                    'edit' => $classModel::IS_EDIT,
                    'delete' => $classModel::IS_DELETE,
                    'view' => $classModel::IS_VIEW,
                    'fieldList' => $fieldList,
                    'fieldAdd' => $fieldAdd,
                    'fieldEdit' => $fieldEdit,
                    'fieldView' => $fieldView,
                    'fieldReadonly' => $fieldReadonly,
                    'fieldFilterable' => $fieldFilterable,
                    'fieldSearchable' => $fieldSearchable,
                    'fieldSortable' => $fieldSortable,
                    'fieldUnique' => $fieldUnique,
                    'fieldType' => str_replace(" ", "_", $fieldType),
                    'parentChild' => $classModel::PARENT_CHILD,
                    'fieldValidation' => $fieldValidation,
                    'fieldRelation' => $fieldRelation,
                    'fieldDefaultValue' => $fieldDefaultValue,
                    'fieldArray' => $fieldArray,
                    'fieldUpload' => $fieldUpload,
                    'table_name' => $tableNameOriginal,
                    'studly_caps' => Str::ucfirst(Str::camel($tableName)),
                    'customContent' => $customContent,
                    'customSelect' => $customSelect
                ];

                $fileContent = view('generate.model', $params);


                file_put_contents($fileName, "<?php \n\n" . $fileContent);
                $this->info("Success Generated Model " . $modelName);
            }
        }

        $fileName = base_path("resources/lang/en/field.php");
        $fileContent = view('generate.field', ["fields" => $fieldLeanguageEN]);
        file_put_contents($fileName, "<?php \n\n" . $fileContent);

        $fileName = base_path("resources/lang/id/field.php");
        $fileContent = view('generate.field', ["fields" => $fieldLeanguageID]);
        file_put_contents($fileName, "<?php \n\n" . $fileContent);

        $fileName = base_path("resources/lang/en/modul.php");
        $fileContent = view('generate.field', ["fields" => $modulLeanguageEN]);
        file_put_contents($fileName, "<?php \n\n" . $fileContent);

        $fileName = base_path("resources/lang/id/modul.php");
        $fileContent = view('generate.field', ["fields" => $modulLeanguageID]);
        file_put_contents($fileName, "<?php \n\n" . $fileContent);
    }
}
