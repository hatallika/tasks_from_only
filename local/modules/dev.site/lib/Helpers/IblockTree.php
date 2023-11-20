<?php

namespace Dev\Site\Helpers;

use CIBlockElement;
use CIBlockSection;
use CModule;
use function Dev\Site\Handlers\getSectionsList;

class IblockTree
{
    private int $iblock_id;
    public array $map = [];

    public function __construct($IBLOCK_ID)
    {
        $this->iblock_id = $IBLOCK_ID;
        $this->getIBlockSectionList();
    }

    private function getIBlockSectionList()
    {
        CModule::IncludeModule('iblock');
        $map = [];
        $sort_by = 'NAME';  // поле, по которому нужно произвести сортировку разделов
        $sort_order = 'ASC';  // направление сортировки

        if (isset($this->iblock_id)) {
            $arFilter = [
                'IBLOCK_ID' => $this->iblock_id,
                'GLOBAL_ACTIVE' => 'Y'
            ];
            $db_list = CIBlockSection::GetList([$sort_by => $sort_order], $arFilter, true);
            while ($ar_result = $db_list->GetNext()) {
                $map['' . $ar_result['ID']] = $ar_result;
            }
        }
        $this->map = $map;

    }

    public function getIblocSectionListForElement($ELEMENT_ID): array
    {
        $groups = CIBlockElement::GetElementGroups($ELEMENT_ID, true);
        //по первой принадлежности к секции
        $first_group = $groups->Fetch();
        $SECTION_ID = ($first_group) ? $first_group['ID'] : false;

        $arrSections = [];
        // рекурсивный поиск
        $this->getSectionsList($SECTION_ID,$arrSections);
        return $arrSections;
    }

    private function getSectionsList($SECTION_ID, &$arrSections)
    {

        if ($this->map[$SECTION_ID]['IBLOCK_SECTION_ID'] == false) {
            $arrSections[] = $this->map[$SECTION_ID]['NAME'];

        } else {
            $this->getSectionsList($this->map[$SECTION_ID]['IBLOCK_SECTION_ID'], $arrSections);
            $arrSections[] = $this->map[$SECTION_ID]['NAME'];
        }
    }

}