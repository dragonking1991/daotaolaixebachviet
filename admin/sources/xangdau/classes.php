<?php
if(!defined('SOURCES')) die("Error");

/* ============================================================
 * Bộ đọc XLSX dạng STREAM (bộ nhớ thấp) — dùng cho file .xlsb đã chuyển đổi.
 * File chuyển đổi từ LibreOffice có thể khai báo sheet tới 1.048.576 dòng khiến
 * PHPExcel (nạp cả DOM) cạn bộ nhớ. Đọc theo luồng bằng XMLReader để tránh OOM.
 * ============================================================ */
if(!class_exists('XdCell'))
{
	class XdCell
	{
		private $v;
		public function __construct($v){ $this->v = $v; }
		public function getValue(){ return $this->v; }
		public function getCalculatedValue(){ return $this->v; }
		public function getOldCalculatedValue(){ return $this->v; }
		public function getFormattedValue(){ return ($this->v === null) ? '' : (string)$this->v; }
	}
}
if(!class_exists('XdArraySheet'))
{
	class XdArraySheet
	{
		private $rows; private $maxRow; private $maxCol;
		public function __construct($rows, $maxRow, $maxCol){ $this->rows = $rows; $this->maxRow = (int)$maxRow; $this->maxCol = (int)$maxCol; }
		public function getCellByColumnAndRow($col, $row)
		{
			$v = isset($this->rows[$row][$col]) ? $this->rows[$row][$col] : '';
			return new XdCell($v);
		}
		public function getHighestRow(){ return max(1, $this->maxRow); }
		public function getHighestColumn(){ return PHPExcel_Cell::stringFromColumnIndex(max(0, $this->maxCol - 1)); }
		public function getTitle(){ return 'sheet'; }
	}
}
