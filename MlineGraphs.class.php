<?php
/*------------------------------------------------------------*/
class MlineGraphs extends Mcontroller {
	/*------------------------------------------------------------*/
	public function lineGraph($values, $yTitle, $renderTo, $xAxisLabelsOrStartDate) {
		$lines = array();
		foreach ( $values as $value )
			$lines[] = array(
				$yTitle => (int)$value,
			);
		$this->lineGraphs($lines, $yTitle, $renderTo, $xAxisLabelsOrStartDate);
	}
	/*------------------------------------------------------------*/
	public function lineGraphs($lines, $yTitle, $renderTo, $xAxisLabelsOrStartDate) {
		foreach ( $lines as $key => $values ) {
			foreach ( $values as $name => $value ) {
				$lines[$key][$name] = (double)$value;
			}
		}
		$graphData = $this->graphData($lines, $yTitle, $renderTo, $xAxisLabelsOrStartDate);
		$chartJson = json_encode($graphData);
		$this->Mview->showTpl("lineGraph.tpl", array(
			'chartJson' => $chartJson,
			'renderTo' => $renderTo,
		));
	}
	/*------------------------------*/
	private function graphData($lines, $yTitle, $renderTo, $xAxisLabelsOrStartDate) {
		if ( ! is_array($xAxisLabelsOrStartDate) &&
			preg_match('/[0-9][0-9][0-9][0-9].*[0-9][0-9].*[0-9][0-9]/',
				$xAxisLabelsOrStartDate) )
			$categories = $this->dateLabels($xAxisLabelsOrStartDate, count($lines));
		elseif ( is_array($xAxisLabelsOrStartDate) )
			$categories = $xAxisLabelsOrStartDate;
		else {
			$this->Mview->error("MlineGraphs::graphData: no date nor labels for X Axis");
			return;
		}

		$numLines = count($lines);
		$step = round($numLines / 10); // ~ 10 labels
		$chart = array(
			'chart' => array(
				'renderTo' => $renderTo,
				'type' => 'line',
				'marginRight' => 130,
				'marginBottom' => 25
			),
			'title' => array(
				'text' => $yTitle,
				'x' => -20 //center
			),
			'subtitle' => array(
				'text' => "",
				'x' => -20
			),
			'xAxis' => array(
				'categories' => $categories,
				'labels' => array(
					'step' => $step,
					/*	'rotation' => -30,	*/
					'style' => array(
						'color' => "blue",
						'font-size' => "10px",
						'font-weight' => "800",
						'fontFamily' => "Arial, Helvetica, sans-serif",

					),
					/*	'y' => -20,	*/
				),
			),
			'yAxis' => array(
				'title' => array(
					'text' => "$yTitle",
				),
			),
			'legend' => array(
				'layout' => 'vertical',
				'align' => 'right',
				'verticalAlign' => 'top',
				'x' => -10,
				'y' => 100,
				'borderWidth' => 0
			),
			'series' => $this->series($lines),
		);
		return($chart);
	}
	/*------------------------------*/
	private function dateLabels($startDate, $num) {
		$dateLabels = array();
		$startDateEpoc = strtotime($startDate);
		$daySecs = 24*60*60;
		for ( $i = 0 ; $i < $num ; $i++) {
			$epoc = $startDateEpoc + $i * $daySecs;
			$date = date("m/d", $epoc);
			$dateLabels[] = $date;
		}
		return($dateLabels);
	}
	/*------------------------------*/
	private function series($lines) {
		$series = array();
		foreach ( $lines as $dateData ) {
			foreach ( $dateData as $name => $value ) {
				if ( ! $value )
					$value = 0;
				if ( ! isset($series[$name]) ) {
					$series[$name] = array(
						'name' => $name,
						'data' => array(),
					);
				}
				$series[$name]['data'][] = $value;
			}
		}
		$series = array_values($series);
		return($series);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
