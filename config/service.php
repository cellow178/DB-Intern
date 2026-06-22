<?php

return [
    [ "type" => "GET", "end_point" => "/get", "class" => "App\Services\Crud\Get"],
    [ "type" => "GET", "end_point" => "/dataset", "class" => "App\Services\Crud\Dataset"],
    [ "type" => "POST", "end_point" => "/create", "class" => "App\Services\Crud\Add"],
    [ "type" => "POST", "end_point" => "/update", "class" => "App\Services\Crud\Edit"],
    [ "type" => "POST", "end_point" => "/delete", "class" => "App\Services\Crud\Delete"],
    [ "type" => "GET", "end_point" => "/show", "class" => "App\Services\Crud\Find"],
    [ "type" => "GET", "end_point" => "/sample", "class" => "App\Services\Sample\SampleService"],

    [ "type" => "GET", "end_point" => "/me", "class" => "App\Services\Auth\Me"],
    [ "type" => "POST", "end_point" => "/login", "class" => "App\Services\Auth\DoLogin"], 

    /*
    |--------------------------------------------------------------------------
    | MAPPING ROLES PERMISSION
    |--------------------------------------------------------------------------
    */
    [ "type" => "GET", "end_point" => "/mapping-roles-permissions/list-modul", "class" => "App\Services\MappingRolesPermissions\MappingRolesPermissionsModulList"],
    [ "type" => "GET", "end_point" => "/mapping-roles-permissions/list", "class" => "App\Services\MappingRolesPermissions\MappingRolesPermissionsList"],
    [ "type" => "POST", "end_point" => "/mapping-roles-permissions/update", "class" => "App\Services\MappingRolesPermissions\MappingRolesPermissionsUpdate"],
];
