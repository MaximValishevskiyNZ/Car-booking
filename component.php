<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
Loader::IncludeModule("iblock");
Loader::includeModule("highloadblock");
$TRIPS_HL_BLOCK_ID = 3;
$CARS_IBLOCK_ID = 5;
$time_start = strtotime($_GET['start']);
$time_finish = strtotime($_GET['finish']);

$dbUser = \Bitrix\Main\UserTable::getList(array(
    'select' => array('ID', 'NAME', 'WORK_POSITION'),
    'filter' => array('ID' => $USER->GetID()),
));
$arUser = $dbUser->fetch();

switch ($arUser['WORK_POSITION']) {
    case "Senior":
        $arFilter = array(
            "IBLOCK_ID" => $CARS_IBLOCK_ID,
            "PROPERTY_CLASS" => ['1', '2'],
        );
        break;
    case "Middle":
        $arFilter = array(
            "IBLOCK_ID" => $CARS_IBLOCK_ID,
            "PROPERTY_CLASS" => ['2', '3'],
        );
        break;
    case "Junior":
        $arFilter = array(
            "IBLOCK_ID" => $CARS_IBLOCK_ID,
            "PROPERTY_CLASS" => '3',
        );
        break;
}

$get_cars = CIBlockElement::GetList(
    array(),
    $arFilter,
    false,
    false,
    ['ID', 'NAME', 'PROPERTY_CLASS', 'PROPERTY_DRIVER_NAME']
);

$cars_list = [];

while (($car = $get_cars->GetNext()) == true) {
    $cars_list[] = $car;
};

$hldata = Bitrix\Highloadblock\HighloadBlockTable::getById($TRIPS_HL_BLOCK_ID)->fetch();
$hlentity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
$entity_data_class = $hlentity->getDataClass();

foreach ($cars_list as $key => $car) {
    $rsData = $entity_data_class::getList(array(
        "select" => array("*"),
        "filter" => array("UF_CAR_ID" => $car['ID']),
    ));

    while ($arData = $rsData->Fetch()) {
        
        $booked_trip_start = strtotime($arData["UF_TRIP_START"]->toString());
        $booked_trip_finish= strtotime($arData["UF_TRIP_FINISH"]->toString());

        if (($time_start >= $booked_trip_start) and ($time_start <= $booked_trip_finish) or
            ($time_finish >= $booked_trip_start) and ($time_finish <= $booked_trip_finish) or
            ($time_start <= $booked_trip_start) and ($time_finish >= $booked_trip_finish)) 
         {
            unset($cars_list[$key]);
         }
    }
}

$arResult['CARS_LIST'] = $cars_list;

$this->IncludeComponentTemplate();
?>