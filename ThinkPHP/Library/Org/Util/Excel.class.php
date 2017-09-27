<?php

namespace Org\Util;

class Excel {

	private $lines = array();
	
	private $sEncoding;
	
	private $bCovertTypes;
	
	private $sWorksheetTitle;
	
	private $bgColor;
	
	public function __construct($sWorksheetTitle = 'Table1', $bgColor = '#CC0033', $sEncoding = 'UTF-8', $bConvertTypes = false){

		$this->bConvertTypes = $bConvertTypes;
		$this->setEncoding($sEncoding);
		$this->setWorksheetTitle($sWorksheetTitle);
		$this->setThBgColor($bgColor);
	}
	
	public function setEncoding($sEncoding) {
		$this->sEncoding = $sEncoding;
	}
	
	public function setWorksheetTitle($title) {

		$title = preg_replace("", "", $title);
		$title = substr($title, 0, 31);
		$this->sWorksheetTitle = $title;
	}
	
	public function setThBgColor($bgColor) {
		$this->bgColor = $bgColor;
	}
	
	private function addRow($array) {
		$th = "";
		$cells = "";
		foreach ($array as $key => $value) {
			if ('title' == $key) {
				$colspan = isset($array['colspan']) ? $array['colspan'] : 2;
				foreach($value as $v) {
					$v = htmlentities($v, ENT_COMPAT, $this->sEncoding);
					$th .= "<th colspan={$colspan} style='backgroud-color:".$this->bgColor."'>{$v}</th>";
				}
				$this->lines[] = "<tr>{$th}</tr>";
			} elseif ('list_column' == $key) {
				foreach($value as $v) {
					$v = htmlentities($v, ENT_COMPAT, $this->sEncoding);
					$th .= "<th style='color:".$this->bgColor."'>{$v}</th>";
				}
				$this->lines[] = "<tr>{$th}</tr>";
			} elseif ('list_content' == $key) {
				foreach($value as $v) {
					$v = htmlentities($v, ENT_COMPAT, $this->sEncoding);
					$v=str_replace("\r\n","<br />",$v);
                    if (substr($v,0,strlen("http://")) == "http://") {//是否包含http://链接
                        $v='<a href="'.$v.'">'.$v.'</a>';
                    }
					$cells .= "<td>{$v}</td>";
				}
				$this->lines[] = "<tr>{$cells}</tr>";
			}
		}
	}
	
	public function addArray($array) {
		foreach ($array as $k => $v) {
			$this->addRow($v);
		}
	}
	
	public function generateXML($filename = 'excel-export') {
		//$filename = preg_replace('/[^aA-zZ0-9\_\-]/', '', $filename);
		
		header("Content-Type: application/vnd.ms-excel;charset=".$this->sEncoding);
		header("Content-Disposition: filename=\"".$filename.".xls\"");
		
		echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
		echo "<html xmlns='http://www.w3.org/1999/xhtml'><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8' /></head><body>";
		echo '<style>br {mso-data-placement:same-cell;} </style>';
		echo '<table border="1"';
		foreach ($this->lines as $line) {
			echo $line;
		}
		echo "</table>";
		echo "</body></html>";
	}
}