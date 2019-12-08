<?php

namespace Malanciault\Threelci\Libraries;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;

class Excel
{
    private $ci;

    public function __construct()
    {
        $this->ci = get_instance();
    }

    public function export($sheet_name, $columns, $data, $filename)
    {
        $filepath = FCPATH . 'uploads/' . $filename;
        $writer = WriterEntityFactory::createXLSXWriter();
        //$writer->openToFile($filepath); // write data to a file or to a PHP stream
        $writer->openToBrowser($filename); // stream data directly to the browser

        // columns headers
        $rowFromValues = WriterEntityFactory::createRowFromArray($columns);
        $writer->addRow($rowFromValues);
        foreach ($data as $row) {
            $singleRow = WriterEntityFactory::createRowFromArray($row);
            $writer->addRow($singleRow);
        }

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($sheet_name);

        $writer->close();
    }

    public function multipages_export($data)
    {

        $filename = 'export_all_' . date("YmdHms") . '.xlsx';

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToBrowser($filename); // stream data directly to the browser

        $newSheet = $writer->getCurrentSheet();

        foreach ($data as $category) {
            $newSheet->setName($category['name']);

            $rowFromValues = WriterEntityFactory::createRowFromArray($category['columns']);
            $writer->addRow($rowFromValues);

            foreach ($category['content'] as $row) {
                $singleRow = WriterEntityFactory::createRowFromArray($row);
                $writer->addRow($singleRow);
            }

            $newSheet = $writer->addNewSheetAndMakeItCurrent();
        }


        $writer->close();
    }
}